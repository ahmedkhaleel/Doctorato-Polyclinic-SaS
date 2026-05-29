<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticConsentTemplate;
use App\Models\CosmeticProcedure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CosmeticConsentTemplateController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Cosmetic/ConsentTemplates/Index', [
            'templates'  => CosmeticConsentTemplate::with('procedure:id,name_ar,name_en')
                ->latest()
                ->get(),
            'procedures' => CosmeticProcedure::where('is_active', true)
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function store(Request $request)
    {
        CosmeticConsentTemplate::create($this->validated($request));

        return back()->with('success', 'تم إنشاء قالب الموافقة');
    }

    public function update(Request $request, CosmeticConsentTemplate $template)
    {
        $template->update($this->validated($request));

        return back()->with('success', 'تم تحديث القالب');
    }

    public function destroy(CosmeticConsentTemplate $template)
    {
        $template->delete();

        return back()->with('success', 'تم حذف القالب');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'procedure_id'       => 'nullable|exists:cosmetic_procedures,id',
            'title_ar'           => 'required|string|max:255',
            'title_en'           => 'nullable|string|max:255',
            'body_ar'            => 'required|string',
            'body_en'            => 'nullable|string',
            'requires_signature' => 'boolean',
            'is_active'          => 'boolean',
        ]);
    }
}
