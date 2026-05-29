<?php

namespace App\Http\Controllers\Patient;

use App\Models\CosmeticConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient-facing cosmetic consents: view consents on file and sign a pending
 * one with a drawn (canvas) e-signature. Patients only ever see/sign their
 * own consents.
 */
class PatientCosmeticConsentController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);
        $locale = app()->getLocale();

        $consents = CosmeticConsent::where('patient_id', $patient->id)
            ->with(['procedure:id,name_ar,name_en', 'template:id,title_ar,title_en'])
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id'        => $c->id,
                'title'     => $c->template
                    ? ($locale === 'ar' ? $c->template->title_ar : ($c->template->title_en ?: $c->template->title_ar))
                    : ($c->procedure ? ($locale === 'ar' ? $c->procedure->name_ar : $c->procedure->name_en) : ($locale === 'ar' ? 'موافقة تجميل' : 'Cosmetic consent')),
                'procedure' => $c->procedure ? ($locale === 'ar' ? $c->procedure->name_ar : $c->procedure->name_en) : null,
                'text'      => $c->consent_text,
                'signed'    => $c->signed_at !== null,
                'signed_at' => $c->signed_at?->toDateString(),
                'signature' => $c->signature_path ? '/storage/' . $c->signature_path : null,
            ]);

        return Inertia::render('Patient/Derma/Consents', [
            'consents' => $consents,
        ]);
    }

    public function sign(Request $request, CosmeticConsent $consent): \Illuminate\Http\RedirectResponse
    {
        $this->authorizePatient($request, $consent);

        if ($consent->signed_at) {
            return back()->with('error', app()->getLocale() === 'ar' ? 'الموافقة موقّعة بالفعل.' : 'This consent is already signed.');
        }

        $data = $request->validate([
            'signature' => 'required|string|starts_with:data:image/png;base64,|max:2000000',
        ]);

        $b64 = substr($data['signature'], strlen('data:image/png;base64,'));
        $binary = base64_decode($b64, true);
        if ($binary === false) {
            return back()->with('error', app()->getLocale() === 'ar' ? 'توقيع غير صالح.' : 'Invalid signature.');
        }

        $path = 'cosmetic/signatures/sig_' . $consent->id . '_' . substr(md5($b64), 0, 8) . '.png';
        Storage::disk('public')->put($path, $binary);

        $consent->update(['signature_path' => $path, 'signed_at' => now()]);

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم توقيع الموافقة بنجاح.' : 'Consent signed successfully.');
    }
}
