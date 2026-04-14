<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dental\StoreDentalTreatmentPlanRequest;
use App\Http\Requests\Dental\UpdateDentalTreatmentPlanRequest;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\DentalTreatmentPlanTemplate;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\DentalTreatmentPlanStatusNotification;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class DentalTreatmentPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = DentalTreatmentPlan::with([
            'patient:id,full_name,file_number,phone',
            'doctor:id,name_ar,name_en',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%");
                })
                ->orWhere('title_ar', 'like', "%{$search}%")
                ->orWhere('title_en', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $plans = $query->withCount('treatments')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Dental/TreatmentPlans/Index', [
            'plans' => $plans,
            'filters' => $request->only(['status', 'doctor_id', 'search', 'date_from', 'date_to']),
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::find($request->patient_id);
        }

        // Auto-seed templates if empty
        if (DentalTreatmentPlanTemplate::count() === 0) {
            DentalTreatmentPlanTemplate::seedDefaults();
        }

        $templates = DentalTreatmentPlanTemplate::active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Dental/TreatmentPlans/Create', [
            'patient' => $patient,
            'patients' => Patient::active()->select('id', 'full_name', 'file_number', 'phone')->limit(100)->get(),
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
            'treatmentTypes' => DentalTreatment::TYPES,
            'templates' => $templates,
        ]);
    }

    public function store(StoreDentalTreatmentPlanRequest $request)
    {
        $data = $request->validated();

        $plan = DB::transaction(function () use ($data) {
            $plan = DentalTreatmentPlan::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'title_ar' => $data['title_ar'] ?? null,
                'title_en' => $data['title_en'] ?? null,
                'description' => $data['description'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? 0,
                'estimated_sessions' => $data['estimated_sessions'] ?? 1,
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'draft',
                'start_date' => $data['start_date'] ?? null,
                'expected_end_date' => $data['expected_end_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create treatments if provided
            if (!empty($data['treatments'])) {
                foreach ($data['treatments'] as $treatment) {
                    DentalTreatment::create([
                        'patient_id' => $data['patient_id'],
                        'doctor_id' => $data['doctor_id'],
                        'treatment_plan_id' => $plan->id,
                        'tooth_number' => $treatment['tooth_number'] ?? null,
                        'treatment_type' => $treatment['treatment_type'],
                        'surfaces' => $treatment['surfaces'] ?? null,
                        'description' => $treatment['description'] ?? null,
                        'cost' => $treatment['cost'] ?? 0,
                        'lab_cost' => $treatment['lab_cost'] ?? 0,
                        'status' => 'planned',
                    ]);
                }

                // Update estimated cost
                $totalCost = $plan->treatments()->sum(DB::raw('cost + lab_cost'));
                $plan->update(['estimated_cost' => $totalCost]);
            }

            return $plan;
        });

        // Track template usage (outside transaction — non-critical)
        if (!empty($data['template_id'])) {
            $usedTemplate = DentalTreatmentPlanTemplate::find($data['template_id']);
            $usedTemplate?->incrementUsage();
        }

        AuditLogger::log('created', $plan);

        return redirect()->route('admin.dental.treatment-plans.show', $plan)
            ->with('success', 'تم إنشاء خطة العلاج بنجاح');
    }

    public function show(DentalTreatmentPlan $treatmentPlan)
    {
        $treatmentPlan->load([
            'patient:id,full_name,file_number,phone,date_of_birth,gender',
            'doctor:id,name_ar,name_en',
            'treatments' => function ($q) {
                $q->with('doctor:id,name_ar,name_en', 'labOrder')->orderBy('tooth_number');
            },
            'consent',
            'consents' => fn ($q) => $q->latest()->limit(5),
        ]);

        return Inertia::render('Admin/Dental/TreatmentPlans/Show', [
            'plan' => $treatmentPlan,
            'treatmentTypes' => DentalTreatment::TYPES,
        ]);
    }

    public function update(UpdateDentalTreatmentPlanRequest $request, DentalTreatmentPlan $treatmentPlan)
    {
        $data = $request->validated();

        // Enforce consent before starting treatment
        if (isset($data['status']) && $data['status'] === 'in_progress' && !$treatmentPlan->hasSignedConsent()) {
            return redirect()->back()->with('error', 'لا يمكن بدء الخطة بدون موافقة المريض الموقعة / Cannot start plan without signed patient consent');
        }

        if (isset($data['status']) && $data['status'] === 'completed') {
            $data['completed_at'] = now();
        }

        $oldStatus = $treatmentPlan->status;
        $treatmentPlan->update($data);

        $description = null;
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $description = "Treatment plan status changed: {$oldStatus} → {$data['status']}";
        }

        AuditLogger::log('updated', $treatmentPlan, null, $description);

        // Notify staff when plan status changes
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $recipients = User::where('is_active', true)
                ->where(function ($q) use ($treatmentPlan) {
                    $q->whereHas('role', fn ($r) => $r->whereIn('name', ['admin', 'super_admin', 'secretary']))
                      ->orWhereHas('doctor', fn ($d) => $d->where('id', $treatmentPlan->doctor_id));
                })->get();
            Notification::send($recipients, new DentalTreatmentPlanStatusNotification($treatmentPlan, $oldStatus, $data['status']));
        }

        return redirect()->back()->with('success', 'تم تحديث خطة العلاج بنجاح');
    }

    public function destroy(DentalTreatmentPlan $treatmentPlan)
    {
        AuditLogger::log('deleted', $treatmentPlan);

        DB::transaction(function () use ($treatmentPlan) {
            $treatmentPlan->treatments()->delete();
            $treatmentPlan->delete();
        });

        return redirect()->route('admin.dental.treatment-plans.index')
            ->with('success', 'تم حذف خطة العلاج بنجاح');
    }

    public function downloadPdf(DentalTreatmentPlan $treatmentPlan)
    {
        $treatmentPlan->load([
            'patient:id,full_name,file_number,phone,date_of_birth,gender',
            'doctor:id,name_ar,name_en',
            'treatments' => fn ($q) => $q->with('doctor:id,name_ar,name_en')->orderBy('tooth_number'),
        ]);

        $pdf = Pdf::loadView('pdf.dental-treatment-plan', ['plan' => $treatmentPlan]);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'dental-plan-' . $treatmentPlan->id . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
