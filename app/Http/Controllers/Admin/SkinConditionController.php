<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\SkinCondition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SkinConditionController extends Controller
{
    public function index(Request $request)
    {
        $query = SkinCondition::with('patient:id,full_name,phone');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                  ->orWhere('name_en', 'like', "%$s%")
                  ->orWhereHas('patient', fn($q) => $q->where('full_name', 'like', "%$s%"));
            });
        }
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('status')) $query->where('status', $request->status);

        $conditions = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Derma/Conditions/Index', [
            'conditions' => $conditions,
            'filters' => $request->only(['search', 'category', 'status']),
            'categories' => SkinCondition::CATEGORIES,
            'severities' => SkinCondition::SEVERITIES,
            'statuses' => SkinCondition::STATUSES,
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        SkinCondition::create($data);
        return back()->with('success', 'تم إضافة الحالة');
    }

    public function update(Request $request, SkinCondition $condition)
    {
        $condition->update($this->validated($request));
        return back()->with('success', 'تم التحديث');
    }

    public function destroy(SkinCondition $condition)
    {
        $condition->delete();
        return back()->with('success', 'تم الحذف');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'category' => 'required|in:' . implode(',', SkinCondition::CATEGORIES),
            'severity' => 'required|in:' . implode(',', SkinCondition::SEVERITIES),
            'body_area' => 'nullable|string|max:255',
            'diagnosed_at' => 'nullable|date',
            'status' => 'required|in:' . implode(',', SkinCondition::STATUSES),
            'notes' => 'nullable|string',
        ]);
    }
}
