<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Requests\Dental\StoreDentalTreatmentRequest;
use App\Http\Requests\Dental\UpdateDentalTreatmentRequest;
use App\Models\DentalTreatment;
use App\Services\DentalTreatmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDentalTreatmentController extends BaseDoctorController
{
    public function __construct(
        protected DentalTreatmentService $treatmentService,
    ) {}

    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = DentalTreatment::with([
            'patient:id,full_name,file_number',
            'treatmentPlan:id,title_ar,title_en',
        ])->where('doctor_id', $doctorId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('treatment_type')) {
            $query->where('treatment_type', $request->treatment_type);
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

        $treatments = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Doctor/Dental/Treatments/Index', [
            'treatments' => $treatments,
            'filters' => $request->only(['status', 'treatment_type', 'search', 'tooth_number', 'date_from', 'date_to']),
            'treatmentTypes' => DentalTreatment::TYPES,
        ]);
    }

    public function store(StoreDentalTreatmentRequest $request)
    {
        $data = $request->validated();
        $data['doctor_id'] = $this->doctorId($request);

        $this->treatmentService->createTreatment($data);

        return redirect()->back()->with('success', $this->msg('Treatment added successfully.', 'تم إضافة العلاج بنجاح.'));
    }

    public function update(UpdateDentalTreatmentRequest $request, DentalTreatment $treatment)
    {
        if ($treatment->doctor_id !== $this->doctorId($request)) {
            abort(403);
        }

        $this->treatmentService->updateTreatment($treatment, $request->validated());

        return redirect()->back()->with('success', $this->msg('Treatment updated successfully.', 'تم تحديث العلاج بنجاح.'));
    }
}
