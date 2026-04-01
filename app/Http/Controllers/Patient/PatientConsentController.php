<?php

namespace App\Http\Controllers\Patient;

use App\Http\Requests\Dental\SignConsentRequest;
use App\Models\TreatmentPlanConsent;
use App\Models\User;
use App\Notifications\ConsentRequestNotification;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PatientConsentController extends BasePatientController
{
    /**
     * Show the consent signing page
     */
    public function show(Request $request, string $locale, TreatmentPlanConsent $consent): Response
    {
        $this->authorizePatientConsent($request, $consent);

        // Check if expired
        $consent->isExpired();
        $consent->refresh();

        $consent->load([
            'treatmentPlan.doctor:id,name_ar,name_en,specialization_ar,specialization_en',
            'treatmentPlan.treatments',
        ]);

        return Inertia::render('Patient/Dental/Consent/Sign', [
            'consent' => $consent,
        ]);
    }

    /**
     * Process the consent action (sign or decline)
     */
    public function sign(SignConsentRequest $request, string $locale, TreatmentPlanConsent $consent)
    {
        $this->authorizePatientConsent($request, $consent);

        if (!$consent->isPending()) {
            return redirect()->back()->with('error', 'هذا الطلب غير قابل للتوقيع حالياً / This consent request is no longer valid');
        }

        $data = $request->validated();

        if ($data['action'] === 'sign') {
            return $this->processSign($request, $consent, $data);
        }

        return $this->processDecline($consent, $data);
    }

    /**
     * Show the confirmation page after signing
     */
    public function signed(Request $request, string $locale, TreatmentPlanConsent $consent): Response
    {
        $this->authorizePatientConsent($request, $consent);

        $consent->load([
            'treatmentPlan.doctor:id,name_ar,name_en',
        ]);

        return Inertia::render('Patient/Dental/Consent/Signed', [
            'consent' => $consent,
        ]);
    }

    /**
     * Process signing the consent
     */
    private function processSign(Request $request, TreatmentPlanConsent $consent, array $data)
    {
        DB::transaction(function () use ($request, $consent, $data) {
            // Save signature image from base64
            $signatureData = $data['signature'];
            $signaturePath = $this->saveSignatureImage($signatureData, $consent);

            // Generate and save PDF
            $pdfPath = $this->generateConsentPdf($consent);

            // Update consent atomically with all paths
            $consent->update([
                'status' => TreatmentPlanConsent::STATUS_SIGNED,
                'signed_at' => now(),
                'signature_image_path' => $signaturePath,
                'patient_ip' => $request->ip(),
                'patient_user_agent' => $request->userAgent(),
                'pdf_path' => $pdfPath,
            ]);

            AuditLogger::log('updated', $consent, null, 'Patient signed consent');
        });

        // Notify admin/doctor
        $this->notifyStaff($consent, 'signed');

        return redirect()->route('patient.dental.consent.signed', [
            'locale' => app()->getLocale(),
            'consent' => $consent->id,
        ])->with('success', 'تم التوقيع بنجاح / Consent signed successfully');
    }

    /**
     * Process declining the consent
     */
    private function processDecline(TreatmentPlanConsent $consent, array $data)
    {
        $consent->update([
            'status' => TreatmentPlanConsent::STATUS_DECLINED,
            'declined_at' => now(),
            'declined_reason' => $data['declined_reason'] ?? null,
        ]);

        AuditLogger::log('updated', $consent, null, 'Patient declined consent');

        $this->notifyStaff($consent, 'declined');

        return redirect()->route('patient.dental.consent.signed', [
            'locale' => app()->getLocale(),
            'consent' => $consent->id,
        ])->with('info', 'تم رفض الموافقة / Consent declined');
    }

    /**
     * Save the base64 signature image to storage (PRIVATE disk for security)
     */
    private function saveSignatureImage(string $base64Data, TreatmentPlanConsent $consent): string
    {
        // Validate data URL prefix
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $base64Data)) {
            throw new \InvalidArgumentException('Invalid signature image format. Only PNG/JPEG allowed.');
        }

        // Remove data URL prefix
        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        $imageData = base64_decode($base64Data);

        // Prevent DoS: limit signature image size to 2MB
        if (strlen($imageData) > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException('Signature image too large. Maximum 2MB allowed.');
        }

        $directory = 'consents/signatures';
        $filename = "consent-{$consent->id}-" . now()->format('YmdHis') . '.png';
        $path = $directory . '/' . $filename;

        // Store on LOCAL (private) disk — not publicly accessible
        Storage::disk('local')->put($path, $imageData);

        return $path;
    }

    /**
     * Generate consent PDF and save to storage (PRIVATE disk for security)
     */
    private function generateConsentPdf(TreatmentPlanConsent $consent): ?string
    {
        try {
            $consent->load(['treatmentPlan.patient', 'treatmentPlan.doctor', 'treatmentPlan.treatments']);

            $pdf = Pdf::loadView('pdf.dental-consent', ['consent' => $consent]);
            $pdf->setPaper('a4', 'portrait');

            $directory = 'consents/pdfs';
            $filename = "consent-{$consent->id}-" . now()->format('YmdHis') . '.pdf';
            $path = $directory . '/' . $filename;

            // Store on LOCAL (private) disk — not publicly accessible
            Storage::disk('local')->put($path, $pdf->output());

            return $path;
        } catch (\Exception $e) {
            \Log::error('Failed to generate consent PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Notify staff (admin/doctor) about consent status
     */
    private function notifyStaff(TreatmentPlanConsent $consent, string $type): void
    {
        $plan = $consent->treatmentPlan;
        $recipients = User::where('is_active', true)
            ->where(function ($q) use ($plan) {
                $q->whereHas('role', fn ($r) => $r->whereIn('name', ['admin', 'super_admin']))
                  ->orWhereHas('doctor', fn ($d) => $d->where('id', $plan->doctor_id));
            })->get();

        \App\Jobs\SendNotificationJob::dispatch($recipients, new ConsentRequestNotification($consent, $type), 'consent_' . $type);
    }

    /**
     * Authorize that the consent belongs to the authenticated patient
     */
    private function authorizePatientConsent(Request $request, TreatmentPlanConsent $consent): void
    {
        if ($consent->patient_id !== $this->patientId($request)) {
            abort(403, 'This consent does not belong to you.');
        }
    }
}
