<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DermaPrescriptionTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DermaPrescriptionTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = DermaPrescriptionTemplate::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('name_en', 'like', "%$s%");
            });
        }
        if ($request->filled('condition_category')) {
            $query->where('condition_category', $request->condition_category);
        }

        $templates = $query->orderBy('sort_order')->orderBy('name_ar')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Derma/PrescriptionTemplates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'condition_category']),
            'categories' => \App\Models\SkinCondition::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        DermaPrescriptionTemplate::create($this->validated($request));
        return back()->with('success', 'تم الإنشاء');
    }

    public function update(Request $request, DermaPrescriptionTemplate $template)
    {
        $template->update($this->validated($request));
        return back()->with('success', 'تم التحديث');
    }

    public function destroy(DermaPrescriptionTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'تم الحذف');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'condition_category' => 'nullable|string|max:100',
            'diagnosis_ar' => 'nullable|string',
            'diagnosis_en' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.medication_name' => 'required_with:items|string',
            'items.*.dosage' => 'nullable|string',
            'items.*.frequency' => 'nullable|string',
            'items.*.duration' => 'nullable|string',
            'items.*.instructions' => 'nullable|string',
            'notes_ar' => 'nullable|string',
            'notes_en' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
