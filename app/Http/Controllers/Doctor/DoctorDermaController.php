<?php

namespace App\Http\Controllers\Doctor;

use App\Models\CosmeticConsent;
use App\Models\CosmeticProcedure;
use App\Models\CosmeticSession;
use App\Models\DermaPhoto;
use App\Models\DermaSession;
use App\Models\DermaTreatmentPlan;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\CosmeticDermaInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dermatology & Cosmetic clinical workspace for the treating doctor — the
 * dedicated counterpart to the admin derma controllers. Lets the doctor log
 * derma/cosmetic sessions (billed via CosmeticDermaInvoiceService), review a
 * patient's treatment plans, and browse before/after photos.
 */
class DoctorDermaController extends BaseDoctorController
{
    public function __construct(private CosmeticDermaInvoiceService $invoicing) {}

    public function dashboard(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $todayVisits = Visit::where('doctor_id', $doctorId)
            ->where('module', 'derma')
            ->whereDate('visit_date', today())
            ->with('patient:id,full_name,photo')
            ->orderBy('scheduled_time')
            ->get();

        $sessionsThisMonth = DermaSession::where('doctor_id', $doctorId)
            ->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->count()
            + CosmeticSession::where('doctor_id', $doctorId)
                ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $activePlans = DermaTreatmentPlan::where('doctor_id', $doctorId)
            ->whereColumn('completed_sessions', '<', 'estimated_sessions')->count();

        $revenueMonth = Invoice::where('module', 'derma')
            ->whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year)->sum('total');

        $recentSessions = DermaSession::where('doctor_id', $doctorId)
            ->with('patient:id,full_name,photo')
            ->latest('completed_at')->limit(8)->get();

        return Inertia::render('Doctor/Derma/Dashboard', [
            'stats' => [
                'visits_today' => $todayVisits->count(),
                'sessions_this_month' => $sessionsThisMonth,
                'active_plans' => $activePlans,
                'revenue_this_month' => (float) $revenueMonth,
            ],
            'todayVisits' => $todayVisits,
            'recentSessions' => $recentSessions,
        ]);
    }

    public function patients(Request $request): Response
    {
        $doctorId = $this->doctorId($request);
        $search = $request->input('search');

        $patients = Patient::query()
            ->where(fn ($q) => $q
                ->whereHas('visits', fn ($v) => $v->where('doctor_id', $doctorId)->where('module', 'derma'))
                ->orWhereHas('dermaSessions', fn ($s) => $s->where('doctor_id', $doctorId))
                ->orWhereHas('cosmeticSessions', fn ($s) => $s->where('doctor_id', $doctorId)))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->withCount([
                'dermaSessions as derma_sessions_count' => fn ($s) => $s->where('doctor_id', $doctorId),
                'cosmeticSessions as cosmetic_sessions_count' => fn ($s) => $s->where('doctor_id', $doctorId),
            ])
            ->addSelect(['last_visit_date' => Visit::select('visit_date')
                ->whereColumn('visits.patient_id', 'patients.id')
                ->where('doctor_id', $doctorId)->where('module', 'derma')
                ->latest('visit_date')->limit(1),
            ])
            ->orderBy('full_name')
            ->paginate(20)->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
                'file_number' => $p->file_number,
                'gender' => $p->gender,
                'sessions' => (int) ($p->derma_sessions_count ?? 0) + (int) ($p->cosmetic_sessions_count ?? 0),
                'last_visit' => $p->last_visit_date ? \Illuminate\Support\Carbon::parse($p->last_visit_date)->toDateString() : null,
            ]);

        return Inertia::render('Doctor/Derma/Patients/Index', [
            'patients' => $patients,
            'filters' => ['search' => $search],
        ]);
    }

    public function patientShow(Request $request, Patient $patient): Response
    {
        $dermaSessions = DermaSession::where('patient_id', $patient->id)
            ->with('doctor:id,name_ar,name_en')->latest('completed_at')->get();
        $cosmeticSessions = CosmeticSession::where('patient_id', $patient->id)
            ->with('procedure:id,name_ar,name_en')->latest('created_at')->get();
        $plans = DermaTreatmentPlan::where('patient_id', $patient->id)->latest()->get();
        $photos = DermaPhoto::where('patient_id', $patient->id)->latest('taken_at')->get();
        $consents = CosmeticConsent::where('patient_id', $patient->id)
            ->with('procedure:id,name_ar,name_en')->latest('signed_at')->get();

        return Inertia::render('Doctor/Derma/Patients/Show', [
            'patient' => $patient->only(['id', 'full_name', 'phone', 'file_number', 'gender']),
            'dermaSessions' => $dermaSessions,
            'cosmeticSessions' => $cosmeticSessions,
            'plans' => $plans,
            'photos' => $photos,
            'consents' => $consents,
            'sessionTypes' => DermaSession::TYPES,
            'procedures' => CosmeticProcedure::where('is_active', true)->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en', 'default_price']),
        ]);
    }

    public function storeCosmeticSession(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'procedure_id' => 'nullable|exists:cosmetic_procedures,id',
            'area_treated' => 'nullable|string|max:255',
            'product_used' => 'nullable|string|max:255',
            'dose_units' => 'nullable|numeric|min:0',
            'session_number' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $data['patient_id'] = $patient->id;
        $data['doctor_id'] = $this->doctorId($request);
        $data['completed_at'] ??= now();

        $session = CosmeticSession::create($data);
        $this->invoicing->generateForCosmeticSession($session);
        $this->invoicing->consumeInventoryForCosmeticSession($session->fresh());

        AuditLogger::log('created', $session, ['patient_id' => $patient->id], 'Logged cosmetic session');

        return back()->with('success', $this->msg('Cosmetic session logged.', 'تم تسجيل جلسة التجميل.'));
    }

    public function uploadPhoto(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'category' => 'required|in:before,after,progress',
            'body_area' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'image' => 'required|image|max:8192',
        ]);

        $path = $request->file('image')->store('derma/photos', 'local');
        DermaPhoto::create([
            'patient_id' => $patient->id,
            'category' => $data['category'],
            'body_area' => $data['body_area'] ?? null,
            'notes' => $data['notes'] ?? null,
            'taken_at' => now(),
            'image_path' => $path,
        ]);

        AuditLogger::log('created', $patient, ['category' => $data['category']], 'Uploaded derma photo');

        return back()->with('success', $this->msg('Photo uploaded.', 'تم رفع الصورة.'));
    }

    public function storeDermaSession(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'session_type' => 'required|in:'.implode(',', DermaSession::TYPES),
            'treatment_plan_id' => 'nullable|exists:derma_treatment_plans,id',
            'area_treated' => 'nullable|string|max:255',
            'product_used' => 'nullable|string|max:255',
            'session_number' => 'nullable|integer|min:1',
            'total_sessions' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'completed_at' => 'nullable|date',
            'next_session_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $data['patient_id'] = $patient->id;
        $data['doctor_id'] = $this->doctorId($request);
        $data['completed_at'] ??= now();

        $session = DermaSession::create($data);
        $this->invoicing->generateForDermaSession($session);

        AuditLogger::log('created', $session, ['patient_id' => $patient->id], 'Logged derma session');

        return back()->with('success', $this->msg('Session logged.', 'تم تسجيل الجلسة.'));
    }

    public function treatmentPlans(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $plans = DermaTreatmentPlan::where('doctor_id', $doctorId)
            ->with('patient:id,full_name,phone')
            ->latest()
            ->paginate(20)->withQueryString();

        $all = DermaTreatmentPlan::where('doctor_id', $doctorId);
        $active = (clone $all)->whereColumn('completed_sessions', '<', 'estimated_sessions')->count();

        return Inertia::render('Doctor/Derma/TreatmentPlans/Index', [
            'plans' => $plans,
            'stats' => [
                'total' => (clone $all)->count(),
                'active' => $active,
                'completed' => (clone $all)->whereColumn('completed_sessions', '>=', 'estimated_sessions')->count(),
            ],
        ]);
    }
}
