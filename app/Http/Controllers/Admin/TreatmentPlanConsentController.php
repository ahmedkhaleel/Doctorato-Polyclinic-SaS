<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dental\SendConsentRequest;
use App\Models\DentalTreatmentPlan;
use App\Models\TreatmentPlanConsent;
use App\Notifications\ConsentRequestNotification;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TreatmentPlanConsentController extends Controller
{
    /**
     * Send a consent request to the patient
     */
    public function send(SendConsentRequest $request, DentalTreatmentPlan $treatmentPlan)
    {
        $data = $request->validated();

        // Build consent text snapshot
        $treatmentPlan->load(['patient', 'doctor', 'treatments']);
        $snapshot = $this->buildConsentSnapshot($treatmentPlan);

        // Expire any existing pending consents
        $treatmentPlan->consents()
            ->where('status', TreatmentPlanConsent::STATUS_PENDING)
            ->update(['status' => TreatmentPlanConsent::STATUS_EXPIRED]);

        $expiresInDays = $data['expires_in_days'] ?? 7;

        $consent = TreatmentPlanConsent::create([
            'dental_treatment_plan_id' => $treatmentPlan->id,
            'patient_id' => $treatmentPlan->patient_id,
            'sent_by' => $request->user()->id,
            'status' => TreatmentPlanConsent::STATUS_PENDING,
            'sent_at' => now(),
            'expires_at' => now()->addDays($expiresInDays),
            'consent_text_snapshot' => $snapshot,
            'risks_notes' => $data['risks_notes'] ?? null,
        ]);

        AuditLogger::log('created', $consent, null, 'Consent request sent to patient');

        // Notify the patient (via database notification)
        if ($treatmentPlan->patient?->user) {
            $treatmentPlan->patient->user->notify(new ConsentRequestNotification($consent, 'request'));
        }

        return redirect()->back()->with('success', 'تم إرسال طلب الموافقة بنجاح / Consent request sent successfully');
    }

    /**
     * Resend a consent request
     */
    public function resend(Request $request, TreatmentPlanConsent $consent)
    {
        if (!$consent->isPending() && !$consent->isExpired() && !$consent->isDeclined()) {
            return redirect()->back()->with('error', 'لا يمكن إعادة إرسال هذا الطلب / Cannot resend this request');
        }

        // Expire old consent
        $consent->update(['status' => TreatmentPlanConsent::STATUS_EXPIRED]);

        // Create new consent
        $treatmentPlan = $consent->treatmentPlan;
        $treatmentPlan->load(['patient', 'doctor', 'treatments']);

        $newConsent = TreatmentPlanConsent::create([
            'dental_treatment_plan_id' => $treatmentPlan->id,
            'patient_id' => $treatmentPlan->patient_id,
            'sent_by' => $request->user()->id,
            'status' => TreatmentPlanConsent::STATUS_PENDING,
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
            'consent_text_snapshot' => $this->buildConsentSnapshot($treatmentPlan),
            'risks_notes' => $consent->risks_notes,
        ]);

        AuditLogger::log('created', $newConsent, null, 'Consent request resent to patient');

        if ($treatmentPlan->patient?->user) {
            $treatmentPlan->patient->user->notify(new ConsentRequestNotification($newConsent, 'request'));
        }

        return redirect()->back()->with('success', 'تم إعادة إرسال طلب الموافقة / Consent request resent');
    }

    /**
     * Download the signed consent PDF
     */
    public function downloadPdf(TreatmentPlanConsent $consent)
    {
        if ($consent->pdf_path && Storage::disk('public')->exists($consent->pdf_path)) {
            return response()->download(Storage::disk('public')->path($consent->pdf_path));
        }

        // Generate PDF on-the-fly if not stored
        $consent->load(['treatmentPlan.patient', 'treatmentPlan.doctor', 'treatmentPlan.treatments']);

        $pdf = Pdf::loadView('pdf.dental-consent', ['consent' => $consent]);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'consent-' . $consent->id . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Build consent text snapshot from treatment plan data
     */
    private function buildConsentSnapshot(DentalTreatmentPlan $plan): array
    {
        return [
            'plan_id' => $plan->id,
            'plan_title_ar' => $plan->title_ar,
            'plan_title_en' => $plan->title_en,
            'plan_description' => $plan->description,
            'estimated_cost' => $plan->estimated_cost,
            'estimated_sessions' => $plan->estimated_sessions,
            'doctor_name_ar' => $plan->doctor?->name_ar,
            'doctor_name_en' => $plan->doctor?->name_en,
            'patient_name' => $plan->patient?->full_name,
            'patient_file_number' => $plan->patient?->file_number,
            'treatments' => $plan->treatments->map(fn ($t) => [
                'treatment_type' => $t->treatment_type,
                'tooth_number' => $t->tooth_number,
                'surfaces' => $t->surfaces,
                'description' => $t->description,
                'cost' => $t->cost,
                'lab_cost' => $t->lab_cost,
            ])->toArray(),
            'snapshot_date' => now()->toISOString(),
        ];
    }
}
