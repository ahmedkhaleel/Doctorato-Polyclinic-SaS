<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlanTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalTreatmentPlanTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = DentalTreatmentPlanTemplate::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('description_ar', 'like', "%{$search}%")
                    ->orWhere('description_en', 'like', "%{$search}%");
            });
        }

        // Auto-seed defaults if empty
        if (DentalTreatmentPlanTemplate::count() === 0) {
            DentalTreatmentPlanTemplate::seedDefaults();
        }

        $templates = $query->orderBy('sort_order')
            ->orderBy('category')
            ->orderBy('name_en')
            ->get();

        return Inertia::render('Admin/Dental/TreatmentPlans/Templates', [
            'templates' => $templates,
            'categories' => DentalTreatmentPlanTemplate::CATEGORIES,
            'treatmentTypes' => DentalTreatment::TYPES,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', DentalTreatmentPlanTemplate::CATEGORIES),
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'treatments' => 'required|array|min:1',
            'treatments.*.treatment_type' => 'required|string|in:' . implode(',', DentalTreatment::TYPES),
            'treatments.*.tooth_number' => 'nullable|integer|min:11|max:85',
            'treatments.*.surfaces' => 'nullable|array',
            'treatments.*.description' => 'nullable|string|max:500',
            'treatments.*.cost' => 'nullable|numeric|min:0',
            'treatments.*.lab_cost' => 'nullable|numeric|min:0',
            'treatments.*.sort_order' => 'nullable|integer',
            'estimated_sessions' => 'nullable|integer|min:1',
            'priority' => 'nullable|string|in:urgent,high,normal,low',
            'notes' => 'nullable|string',
        ]);

        $totalCost = collect($data['treatments'])->sum(fn ($t) => ($t['cost'] ?? 0) + ($t['lab_cost'] ?? 0));

        $template = DentalTreatmentPlanTemplate::create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'category' => $data['category'],
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'treatments' => $data['treatments'],
            'estimated_cost' => $totalCost,
            'estimated_sessions' => $data['estimated_sessions'] ?? 1,
            'priority' => $data['priority'] ?? 'normal',
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::log('created', $template);

        return redirect()->back()->with('success', 'تم إنشاء القالب بنجاح');
    }

    public function update(Request $request, DentalTreatmentPlanTemplate $template)
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', DentalTreatmentPlanTemplate::CATEGORIES),
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'treatments' => 'required|array|min:1',
            'treatments.*.treatment_type' => 'required|string|in:' . implode(',', DentalTreatment::TYPES),
            'treatments.*.tooth_number' => 'nullable|integer|min:11|max:85',
            'treatments.*.surfaces' => 'nullable|array',
            'treatments.*.description' => 'nullable|string|max:500',
            'treatments.*.cost' => 'nullable|numeric|min:0',
            'treatments.*.lab_cost' => 'nullable|numeric|min:0',
            'treatments.*.sort_order' => 'nullable|integer',
            'estimated_sessions' => 'nullable|integer|min:1',
            'priority' => 'nullable|string|in:urgent,high,normal,low',
            'notes' => 'nullable|string',
        ]);

        $totalCost = collect($data['treatments'])->sum(fn ($t) => ($t['cost'] ?? 0) + ($t['lab_cost'] ?? 0));

        $template->update([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'category' => $data['category'],
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'treatments' => $data['treatments'],
            'estimated_cost' => $totalCost,
            'estimated_sessions' => $data['estimated_sessions'] ?? 1,
            'priority' => $data['priority'] ?? 'normal',
            'notes' => $data['notes'] ?? null,
        ]);

        AuditLogger::log('updated', $template);

        return redirect()->back()->with('success', 'تم تحديث القالب بنجاح');
    }

    public function toggleActive(DentalTreatmentPlanTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);

        return redirect()->back()->with('success', $template->is_active ? 'تم تفعيل القالب' : 'تم تعطيل القالب');
    }

    public function destroy(DentalTreatmentPlanTemplate $template)
    {
        AuditLogger::log('deleted', $template);
        $template->delete();

        return redirect()->back()->with('success', 'تم حذف القالب');
    }

    public function seedDefaults()
    {
        DentalTreatmentPlanTemplate::seedDefaults();

        return redirect()->back()->with('success', 'تم إعادة تحميل القوالب الافتراضية');
    }

    /**
     * Duplicate an existing template.
     */
    public function duplicate(DentalTreatmentPlanTemplate $template)
    {
        $new = $template->replicate();
        $new->name_ar = $template->name_ar . ' (نسخة)';
        $new->name_en = $template->name_en . ' (Copy)';
        $new->usage_count = 0;
        $new->created_by = auth()->id();
        $new->save();

        AuditLogger::log('created', $new);

        return redirect()->back()->with('success', 'تم نسخ القالب بنجاح');
    }
}
