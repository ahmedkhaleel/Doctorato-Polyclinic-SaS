<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Requests\Dental\SendConsentRequest;
use App\Models\DentalTreatmentPlan;
use App\Models\TreatmentPlanConsent;
use App\Notifications\ConsentRequestNotification;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorTreatmentPlanConsentController extends BaseDoctorController
{
    /**
     * Send a consent request to the patient
     */
    public function send(SendConsentRequest $request, DentalTreatmentPlan $treatmentPlan)
    {
        if ((int) $treatmentPlan->doctor_id !== (int) $this->doctorId($request)) {
            abort(403);
        }

        $data = $request->validated();

        $treatmentPlan->load(['patient', 'doctor', 'treatments']);

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
            'consent_text_snapshot' => $this->buildConsentSnapshot($treatmentPlan),
            'risks_notes' => $data['risks_notes'] ?? null,
        ]);

        AuditLogger::log('created', $consent, null, 'Doctor sent consent request to patient');

        if ($treatmentPlan->patient?->user) {
            \App\Jobs\SendNotificationJob::dispatch($treatmentPlan->patient->user, new ConsentRequestNotification($consent, 'request'), 'consent_request');
        }

        return redirect()->back()->with('success', $this->msg('Consent request sent successfully.', 'تم إرسال طلب الموافقة بنجاح.'));
    }

    /**
     * Download the signed consent PDF
     */
    public function downloadPdf(Request $request, TreatmentPlanConsent $consent)
    {
        if ((int) $consent->treatmentPlan->doctor_id !== (int) $this->doctorId($request)) {
            abort(403);
        }

        // PDF is written to the private disk (see PatientConsentController::generateConsentPdf);
        // SecureMedia::download also falls back to the legacy public disk during transition.
        if ($consent->pdf_path && \App\Support\SecureMedia::exists($consent->pdf_path)) {
            return \App\Support\SecureMedia::download($consent->pdf_path);
        }

        $consent->load(['treatmentPlan.patient', 'treatmentPlan.doctor', 'treatmentPlan.treatments']);

        $pdf = Pdf::loadView('pdf.dental-consent', ['consent' => $consent]);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'consent-' . $consent->id . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

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
