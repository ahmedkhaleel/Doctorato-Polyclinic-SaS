<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Requests\Dental\StoreDentalComparisonRequest;
use App\Models\DentalComparison;
use App\Models\DentalTreatmentPlan;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDentalComparisonController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = DentalComparison::with(['patient:id,full_name,file_number'])
            ->where('doctor_id', $doctorId);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%"))
                  ->orWhere('title_ar', 'like', "%{$search}%")
                  ->orWhere('title_en', 'like', "%{$search}%");
            });
        }

        $comparisons = $query->latest()->paginate(16)->withQueryString();

        return Inertia::render('Doctor/Dental/Comparisons/Index', [
            'comparisons' => $comparisons,
            'filters' => $request->only(['category', 'search']),
            'categories' => DentalComparison::CATEGORIES,
        ]);
    }

    public function store(StoreDentalComparisonRequest $request)
    {
        $doctorId = $this->doctorId($request);
        $data = $request->validated();
        $data['doctor_id'] = $doctorId;

        if ($request->hasFile('before_image')) {
            $data['before_image_path'] = $request->file('before_image')->store('dental-comparisons', 'public');
        }
        if ($request->hasFile('after_image')) {
            $data['after_image_path'] = $request->file('after_image')->store('dental-comparisons', 'public');
        }

        unset($data['before_image'], $data['after_image']);
        $data['is_visible_to_patient'] = $data['is_visible_to_patient'] ?? true;

        $comparison = DentalComparison::create($data);
        AuditLogger::log('created', $comparison);

        return redirect()->back()->with('success', $this->msg('Comparison created successfully.', 'تم إنشاء المقارنة بنجاح.'));
    }

    public function show(Request $request, DentalComparison $comparison): Response
    {
        if ((int) $comparison->doctor_id !== (int) $this->doctorId($request)) {
            abort(403);
        }

        $comparison->load(['patient:id,full_name,file_number', 'treatmentPlan:id,title_ar,title_en,status']);

        return Inertia::render('Doctor/Dental/Comparisons/Show', [
            'comparison' => $comparison,
        ]);
    }
}
