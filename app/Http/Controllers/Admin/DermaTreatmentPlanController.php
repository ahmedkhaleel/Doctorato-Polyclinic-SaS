<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DermaTreatmentPlan;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\SkinCondition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DermaTreatmentPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = DermaTreatmentPlan::with([
            'patient:id,full_name,phone,file_number',
            'doctor:id,name_ar,name_en',
            'skinCondition:id,name_ar,name_en',
        ])->withCount('sessions');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title_ar', 'like', "%$s%")
                  ->orWhere('title_en', 'like', "%$s%")
                  ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%$s%")
                      ->orWhere('phone', 'like', "%$s%")
                      ->orWhere('file_number', 'like', "%$s%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Derma/TreatmentPlans/Index', [
            'plans'    => $query->latest()->paginate(20)->withQueryString(),
            'filters'  => $request->only(['search', 'status']),
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone', 'file_number']),
            'doctors'  => Doctor::where('module', 'derma')->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'types'    => DermaTreatmentPlan::TYPES,
            'stats'    => [
                'active'    => DermaTreatmentPlan::active()->count(),
                'completed' => DermaTreatmentPlan::where('status', 'completed')->count(),
                'planned_value' => (float) DermaTreatmentPlan::whereIn('status', ['planned', 'in_progress'])->sum('estimated_cost'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        DermaTreatmentPlan::create($this->validated($request) + ['created_by' => auth()->id()]);

        return back()->with('success', 'تم إنشاء خطة العلاج');
    }

    public function update(Request $request, DermaTreatmentPlan $plan)
    {
        $plan->update($this->validated($request));
        $plan->syncProgress(); // keep status coherent if estimated_sessions changed

        return back()->with('success', 'تم تحديث الخطة');
    }

    public function destroy(DermaTreatmentPlan $plan)
    {
        $plan->delete();

        return back()->with('success', 'تم حذف الخطة');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'patient_id'         => 'required|exists:patients,id',
            'doctor_id'          => 'nullable|exists:doctors,id',
            'skin_condition_id'  => 'nullable|exists:skin_conditions,id',
            'title_ar'           => 'required|string|max:255',
            'title_en'           => 'nullable|string|max:255',
            'session_type'       => 'required|in:' . implode(',', DermaTreatmentPlan::TYPES),
            'estimated_sessions' => 'required|integer|min:1|max:100',
            'interval_days'      => 'nullable|integer|min:0|max:365',
            'estimated_cost'     => 'nullable|numeric|min:0',
            'status'             => 'nullable|in:planned,in_progress,completed,cancelled',
            'start_date'         => 'nullable|date',
            'notes'              => 'nullable|string|max:2000',
        ]);
    }
}
