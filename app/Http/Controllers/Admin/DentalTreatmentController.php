<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dental\StoreDentalTreatmentRequest;
use App\Http\Requests\Dental\UpdateDentalTreatmentRequest;
use App\Models\DentalTreatment;
use App\Models\Doctor;
use App\Services\AuditLogger;
use App\Services\DentalTreatmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalTreatmentController extends Controller
{
    public function __construct(
        protected DentalTreatmentService $treatmentService,
    ) {}

    public function index(Request $request)
    {
        $query = DentalTreatment::with([
            'patient:id,full_name,file_number',
            'doctor:id,name_ar,name_en',
            'treatmentPlan:id,title_ar,title_en',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('treatment_type')) {
            $query->where('treatment_type', $request->treatment_type);
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%");
                })
                ->orWhere('tooth_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        if ($request->filled('tooth_number')) {
            $query->where('tooth_number', $request->tooth_number);
        }
        if ($request->filled('date_from')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('treatment_date', '>=', $request->date_from)
                  ->orWhere(function ($q2) use ($request) {
                      $q2->whereNull('treatment_date')
                         ->whereDate('created_at', '>=', $request->date_from);
                  });
            });
        }
        if ($request->filled('date_to')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('treatment_date', '<=', $request->date_to)
                  ->orWhere(function ($q2) use ($request) {
                      $q2->whereNull('treatment_date')
                         ->whereDate('created_at', '<=', $request->date_to);
                  });
            });
        }

        $treatments = $query->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Dental/Treatments/Index', [
            'treatments' => $treatments,
            'filters' => $request->only(['status', 'treatment_type', 'doctor_id', 'patient_id', 'search', 'tooth_number', 'date_from', 'date_to']),
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
            'treatmentTypes' => DentalTreatment::TYPES,
            'supplies' => \App\Models\Supply::orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'unit', 'quantity']),
        ]);
    }

    public function store(StoreDentalTreatmentRequest $request)
    {
        $treatment = $this->treatmentService->createTreatment($request->validated());

        AuditLogger::log('created', $treatment);

        return redirect()->back()->with('success', 'تم إضافة العلاج بنجاح');
    }

    public function update(UpdateDentalTreatmentRequest $request, DentalTreatment $treatment)
    {
        $this->treatmentService->updateTreatment($treatment, $request->validated());

        AuditLogger::log('updated', $treatment);

        return redirect()->back()->with('success', 'تم تحديث العلاج بنجاح');
    }

    public function destroy(DentalTreatment $treatment)
    {
        AuditLogger::log('deleted', $treatment);

        $this->treatmentService->deleteTreatment($treatment);

        return redirect()->back()->with('success', 'تم حذف العلاج بنجاح');
    }
}
