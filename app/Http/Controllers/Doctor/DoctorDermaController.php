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

        $monthRevenue = fn ($m) => (float) Invoice::where('module', 'derma')
            ->whereMonth('invoice_date', $m->month)->whereYear('invoice_date', $m->year)->sum('total');
        $revenueMonth = $monthRevenue(now());
        $revenuePrevMonth = $monthRevenue(now()->copy()->subMonthNoOverflow());

        $recentSessions = DermaSession::where('doctor_id', $doctorId)
            ->with('patient:id,full_name,photo')
            ->latest('completed_at')->limit(8)->get();

        // 14-day session trend (derma + cosmetic) for the cockpit chart.
        $start = now()->copy()->subDays(13)->startOfDay();
        $dermaByDay = DermaSession::where('doctor_id', $doctorId)->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->selectRaw('DATE(completed_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');
        $cosmeticByDay = CosmeticSession::where('doctor_id', $doctorId)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');
        $trend = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $trend[] = ['x' => $i + 1, 'y' => (int) (($dermaByDay[$day] ?? 0) + ($cosmeticByDay[$day] ?? 0)), 'label' => $day];
        }

        // Resume plans: active courses with progress.
        $resumePlans = DermaTreatmentPlan::where('doctor_id', $doctorId)
            ->whereColumn('completed_sessions', '<', 'estimated_sessions')
            ->with('patient:id,full_name,photo')
            ->latest('updated_at')->limit(8)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'patient' => $p->patient ? $p->patient->only(['id', 'full_name', 'photo']) : null,
                'title' => $p->title_en ?? $p->title_ar,
                'completed_sessions' => (int) $p->completed_sessions,
                'estimated_sessions' => (int) $p->estimated_sessions,
                'progress' => $p->estimated_sessions > 0 ? min(100, (int) round($p->completed_sessions / $p->estimated_sessions * 100)) : 0,
            ]);

        return Inertia::render('Doctor/Derma/Dashboard', [
            'stats' => [
                'visits_today' => $todayVisits->count(),
                'sessions_this_month' => $sessionsThisMonth,
                'active_plans' => $activePlans,
                'revenue_this_month' => $revenueMonth,
                'revenue_prev_month' => $revenuePrevMonth,
            ],
            'todayVisits' => $todayVisits,
            'recentSessions' => $recentSessions,
            'sessionsTrend' => $trend,
            'resumePlans' => $resumePlans,
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
            'lesions' => \App\Models\DermaLesion::where('patient_id', $patient->id)->latest('id')->get(),
            'lesionTypes' => \App\Models\DermaLesion::TYPES,
            'procedures' => CosmeticProcedure::where('is_active', true)->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en', 'default_price']),
        ]);
    }

    public function storeLesion(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'view' => 'required|in:'.implode(',', \App\Models\DermaLesion::VIEWS),
            'x' => 'required|numeric|min:0|max:100',
            'y' => 'required|numeric|min:0|max:100',
            'lesion_type' => 'nullable|in:'.implode(',', \App\Models\DermaLesion::TYPES),
            'size_mm' => 'nullable|numeric|min:0|max:9999',
            'note' => 'nullable|string|max:255',
        ]);

        \App\Models\DermaLesion::create(array_merge($data, [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctorId($request),
            'recorded_at' => now()->toDateString(),
        ]));

        return back()->with('success', $this->msg('Lesion added.', 'تمت إضافة الآفة.'));
    }

    public function destroyLesion(Request $request, \App\Models\DermaLesion $lesion): RedirectResponse
    {
        $lesion->delete();

        return back()->with('success', $this->msg('Lesion removed.', 'تم حذف الآفة.'));
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
