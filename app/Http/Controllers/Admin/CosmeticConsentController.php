<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticConsent;
use App\Models\CosmeticConsentTemplate;
use App\Models\CosmeticProcedure;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CosmeticConsentController extends Controller
{
    public function index(Request $request)
    {
        $query = CosmeticConsent::with(['patient:id,full_name,phone', 'procedure:id,name_ar,name_en']);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('patient', fn($q) => $q->where('full_name', 'like', "%$s%"));
        }
        if ($request->filled('procedure_id')) $query->where('procedure_id', $request->procedure_id);

        return Inertia::render('Admin/Cosmetic/Consents/Index', [
            'consents' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'procedure_id']),
            'procedures' => CosmeticProcedure::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'procedure_id' => 'nullable|exists:cosmetic_procedures,id',
            'template_id' => 'nullable|exists:cosmetic_consent_templates,id',
            'session_id' => 'nullable|exists:cosmetic_sessions,id',
            'consent_text' => 'nullable|string',
            'signed_at' => 'nullable|date',
            'signature' => 'nullable|image|max:4096',
            'witnessed_by' => 'nullable|string|max:255',
        ]);

        // Prefill the consent text from the chosen template when not supplied.
        if (empty($data['consent_text']) && ! empty($data['template_id'])) {
            $tpl = CosmeticConsentTemplate::find($data['template_id']);
            if ($tpl) {
                $data['consent_text'] = app()->getLocale() === 'ar'
                    ? ($tpl->body_ar ?: $tpl->body_en)
                    : ($tpl->body_en ?: $tpl->body_ar);
                if (empty($data['procedure_id']) && $tpl->procedure_id) {
                    $data['procedure_id'] = $tpl->procedure_id;
                }
            }
        }

        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('cosmetic/signatures', 'public');
        }
        unset($data['signature']);

        CosmeticConsent::create($data);
        return back()->with('success', 'تم حفظ الموافقة');
    }

    public function update(Request $request, CosmeticConsent $consent)
    {
        $data = $request->validate([
            'consent_text' => 'nullable|string',
            'signed_at' => 'nullable|date',
            'witnessed_by' => 'nullable|string|max:255',
        ]);
        $consent->update($data);
        return back()->with('success', 'تم التحديث');
    }

    public function destroy(CosmeticConsent $consent)
    {
        if ($consent->signature_path && Storage::disk('public')->exists($consent->signature_path)) {
            Storage::disk('public')->delete($consent->signature_path);
        }
        $consent->delete();
        return back()->with('success', 'تم الحذف');
    }
}
