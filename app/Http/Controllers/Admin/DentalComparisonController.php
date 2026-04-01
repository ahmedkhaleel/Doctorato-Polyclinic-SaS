<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dental\StoreDentalComparisonRequest;
use App\Http\Requests\Dental\UpdateDentalComparisonRequest;
use App\Models\DentalComparison;
use App\Models\DentalTreatmentPlan;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DentalComparisonController extends Controller
{
    public function index(Request $request)
    {
        $query = DentalComparison::with([
            'patient:id,full_name,file_number',
            'doctor:id,name_ar,name_en',
        ]);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
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
                ->orWhere('title_ar', 'like', "%{$search}%")
                ->orWhere('title_en', 'like', "%{$search}%")
                ->orWhere('tooth_numbers', 'like', "%{$search}%");
            });
        }
        if ($request->boolean('featured_only')) {
            $query->featured();
        }

        $comparisons = $query->latest()
            ->paginate(16)
            ->withQueryString();

        return Inertia::render('Admin/Dental/Comparisons/Index', [
            'comparisons' => $comparisons,
            'filters' => $request->only(['category', 'patient_id', 'search', 'featured_only']),
            'categories' => DentalComparison::CATEGORIES,
        ]);
    }

    public function create(Request $request)
    {
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::find($request->patient_id);
        }

        return Inertia::render('Admin/Dental/Comparisons/Create', [
            'patient' => $patient,
            'patients' => Patient::active()->select('id', 'full_name', 'file_number', 'phone')->limit(100)->get(),
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
            'categories' => DentalComparison::CATEGORIES,
            'treatmentPlans' => $request->filled('patient_id')
                ? DentalTreatmentPlan::where('patient_id', $request->patient_id)->select('id', 'title_ar', 'title_en')->get()
                : [],
        ]);
    }

    public function store(StoreDentalComparisonRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('before_image')) {
            $data['before_image_path'] = $request->file('before_image')->store('dental-comparisons', 'public');
        }
        if ($request->hasFile('after_image')) {
            $data['after_image_path'] = $request->file('after_image')->store('dental-comparisons', 'public');
        }

        unset($data['before_image'], $data['after_image']);

        $data['is_visible_to_patient'] = $data['is_visible_to_patient'] ?? true;
        $data['is_featured'] = $data['is_featured'] ?? false;

        $comparison = DentalComparison::create($data);

        AuditLogger::log('created', $comparison);

        return redirect()->route('admin.dental.comparisons.index')
            ->with('success', 'تم إنشاء المقارنة بنجاح / Comparison created successfully');
    }

    public function show(DentalComparison $comparison)
    {
        $comparison->load([
            'patient:id,full_name,file_number,phone',
            'doctor:id,name_ar,name_en',
            'treatmentPlan:id,title_ar,title_en,status',
        ]);

        return Inertia::render('Admin/Dental/Comparisons/Show', [
            'comparison' => $comparison,
        ]);
    }

    public function update(UpdateDentalComparisonRequest $request, DentalComparison $comparison)
    {
        $data = $request->validated();

        if ($request->hasFile('before_image')) {
            if ($comparison->before_image_path) {
                Storage::disk('public')->delete($comparison->before_image_path);
            }
            $data['before_image_path'] = $request->file('before_image')->store('dental-comparisons', 'public');
        }
        if ($request->hasFile('after_image')) {
            if ($comparison->after_image_path) {
                Storage::disk('public')->delete($comparison->after_image_path);
            }
            $data['after_image_path'] = $request->file('after_image')->store('dental-comparisons', 'public');
        }

        unset($data['before_image'], $data['after_image']);

        $comparison->update($data);

        AuditLogger::log('updated', $comparison);

        return redirect()->back()->with('success', 'تم تحديث المقارنة بنجاح / Comparison updated');
    }

    public function destroy(DentalComparison $comparison)
    {
        AuditLogger::log('deleted', $comparison);

        if ($comparison->before_image_path) {
            Storage::disk('public')->delete($comparison->before_image_path);
        }
        if ($comparison->after_image_path) {
            Storage::disk('public')->delete($comparison->after_image_path);
        }

        $comparison->delete();

        return redirect()->route('admin.dental.comparisons.index')
            ->with('success', 'تم حذف المقارنة / Comparison deleted');
    }

    public function toggleVisibility(DentalComparison $comparison)
    {
        $comparison->update([
            'is_visible_to_patient' => !$comparison->is_visible_to_patient,
        ]);

        return redirect()->back()->with('success', 'تم تحديث الإعداد / Visibility updated');
    }

    public function toggleFeatured(DentalComparison $comparison)
    {
        $comparison->update([
            'is_featured' => !$comparison->is_featured,
        ]);

        return redirect()->back()->with('success', 'تم تحديث الإعداد / Featured status updated');
    }
}
