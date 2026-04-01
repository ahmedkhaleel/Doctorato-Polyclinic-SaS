<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Requests\Dental\StoreDentalXrayRequest;
use App\Models\DentalXray;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDentalXrayController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = DentalXray::with([
            'patient:id,full_name,file_number',
        ])->where('doctor_id', $doctorId);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('file_number', 'like', "%{$request->search}%");
            });
        }

        $xrays = $query->latest('taken_date')->paginate(20)->withQueryString();

        return Inertia::render('Doctor/Dental/Xrays/Index', [
            'xrays' => $xrays,
            'filters' => $request->only(['type', 'search']),
            'xrayTypes' => DentalXray::TYPES,
        ]);
    }

    public function store(StoreDentalXrayRequest $request)
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validated();

        $data['doctor_id'] = $doctorId;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('dental-xrays', 'public');
        }
        unset($data['image']);

        DentalXray::create($data);

        return redirect()->back()->with('success', 'X-ray uploaded successfully.');
    }

    public function patientXrays(Request $request, Patient $patient): Response
    {
        $xrays = DentalXray::where('patient_id', $patient->id)
            ->with('doctor:id,name_ar,name_en')
            ->latest('taken_date')
            ->get();

        return Inertia::render('Doctor/Dental/Xrays/PatientXrays', [
            'patient' => $patient->only(['id', 'full_name', 'file_number']),
            'xrays' => $xrays,
            'xrayTypes' => DentalXray::TYPES,
        ]);
    }
}
