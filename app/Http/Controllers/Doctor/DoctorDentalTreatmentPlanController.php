<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Requests\Dental\StoreDentalTreatmentPlanRequest;
use App\Http\Requests\Dental\UpdateDentalTreatmentPlanRequest;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\DentalTreatmentPlanStatusNotification;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDentalTreatmentPlanController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = DentalTreatmentPlan::with([
            'patient:id,full_name,file_number,phone',
        ])->where('doctor_id', $doctorId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        return Inertia::render('Doctor/Dental/TreatmentPlans/Index', [
            'plans' => $plans,
            'filters' => $request->only(['status', 'search', 'date_from', 'date_to']),
        ]);
    }

    public function show(Request $request, DentalTreatmentPlan $treatmentPlan): Response
    {
        if ($treatmentPlan->doctor_id !== $this->doctorId($request)) {
            abort(403);
        }

        $treatmentPlan->load([
            'patient:id,full_name,file_number,phone,date_of_birth,gender',
            'doctor:id,name_ar,name_en',
            'treatments' => function ($q) {
                $q->with('doctor:id,name_ar,name_en', 'labOrder')->orderBy('tooth_number');
            },
            'consent',
            'consents' => fn ($q) => $q->latest()->limit(5),
        ]);

        return Inertia::render('Doctor/Dental/TreatmentPlans/Show', [
            'plan' => $treatmentPlan,
            'treatmentTypes' => DentalTreatment::TYPES,
        ]);
    }

    public function store(StoreDentalTreatmentPlanRequest $request)
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validated();

        $plan = DentalTreatmentPlan::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctorId,
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

        if (!empty($data['treatments'])) {
            foreach ($data['treatments'] as $treatment) {
                DentalTreatment::create([
                    'patient_id' => $data['patient_id'],
                    'doctor_id' => $doctorId,
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

            $totalCost = $plan->treatments()->sum(\DB::raw('cost + lab_cost'));
            $plan->update(['estimated_cost' => $totalCost]);
        }

        AuditLogger::log('created', $plan);

        return redirect()->route('doctor.dental.treatment-plans.show', $plan)
            ->with('success', 'Treatment plan created successfully.');
    }

    public function update(UpdateDentalTreatmentPlanRequest $request, DentalTreatmentPlan $treatmentPlan)
    {
        if ($treatmentPlan->doctor_id !== $this->doctorId($request)) {
            abort(403);
        }

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

        return redirect()->back()->with('success', 'Treatment plan updated.');
    }
}
