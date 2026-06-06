<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OnlineConsultation;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class OnlineConsultationService
{
    public function __construct(
        private AgoraService $agora,
    ) {}

    public function createConsultation(array $data): OnlineConsultation
    {
        return DB::transaction(function () use ($data) {
            $consultation = OnlineConsultation::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'scheduled_date' => $data['scheduled_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'module' => $data['module'] ?? 'derma',
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'fee' => $data['fee'],
                'status' => OnlineConsultation::STATUS_SCHEDULED,
                'payment_status' => 'pending',
            ]);

            if ($this->agora->isConfigured()) {
                $this->agora->generateTokensForConsultation($consultation);
            }

            return $consultation;
        });
    }

    public function markPaid(OnlineConsultation $consultation, string $gatewayRef): void
    {
        DB::transaction(function () use ($consultation, $gatewayRef) {
            $consultation->update([
                'payment_status' => 'paid',
                'payment_gateway_reference' => $gatewayRef,
            ]);

            // Record the gateway-collected fee as a real Invoice + Payment so
            // telemedicine income appears in revenue reports and the patient's
            // invoices (previously only payment_status was flipped → invisible).
            $this->recordConsultationIncome($consultation->fresh(), $gatewayRef);
        });
    }

    /**
     * Turn a paid consultation's fee into an Invoice (module-tagged) settled
     * by a Payment under the "Online Payment" method. Idempotent — skips if an
     * invoice is already linked or the fee is zero.
     */
    protected function recordConsultationIncome(OnlineConsultation $consultation, string $gatewayRef): void
    {
        if ($consultation->invoice_id || (float) $consultation->fee <= 0) {
            return;
        }

        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $consultation->patient_id,
            'visit_id' => $consultation->visit_id,
            'module' => $consultation->module,
            'subtotal' => (float) $consultation->fee,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => (float) $consultation->fee,
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description_en' => 'Online consultation',
            'description_ar' => 'استشارة أونلاين',
            'quantity' => 1,
            'unit_price' => (float) $consultation->fee,
            'discount' => 0,
            'total' => (float) $consultation->fee,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $consultation->patient_id,
            'payment_method_id' => PaymentMethod::firstOrCreate(['name_en' => 'Online Payment'], ['name_ar' => 'دفع أونلاين', 'is_active' => true])->id,
            'amount' => (float) $consultation->fee,
            'payment_date' => now()->toDateString(),
            'reference_number' => $gatewayRef,
            'notes' => 'Telemedicine consultation '.$consultation->consultation_number,
        ]);

        $invoice->recalculateStatus();
        $consultation->update(['invoice_id' => $invoice->id]);
    }

    public function cancel(OnlineConsultation $consultation, User $by, string $reason): void
    {
        $consultation->update([
            'status' => OnlineConsultation::STATUS_CANCELLED,
            'cancelled_by' => $by->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function markParticipantJoined(OnlineConsultation $consultation, string $role): void
    {
        $field = $role === 'doctor' ? 'doctor_joined_at' : 'patient_joined_at';
        if (! $consultation->$field) {
            $consultation->update([$field => now()]);
            try {
                event(new \App\Events\OnlineConsultation\ParticipantJoined($consultation, $role));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Broadcast failed: ParticipantJoined', ['err' => $e->getMessage()]);
            }
        }

        if ($consultation->doctor_joined_at && $consultation->patient_joined_at
            && ! $consultation->session_started_at) {
            $consultation->update([
                'session_started_at' => now(),
                'status' => OnlineConsultation::STATUS_IN_PROGRESS,
            ]);
            try {
                event(new \App\Events\OnlineConsultation\SessionStarted($consultation->fresh()));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Broadcast failed: SessionStarted', ['err' => $e->getMessage()]);
            }
        }
    }

    /**
     * End the session and create the Visit record.
     */
    public function completeSession(OnlineConsultation $consultation, array $sessionData = []): Visit
    {
        $visit = DB::transaction(function () use ($consultation, $sessionData) {
            $endedAt = now();
            $duration = $consultation->session_started_at
                ? $endedAt->diffInSeconds($consultation->session_started_at)
                : 0;

            $consultation->update([
                'session_ended_at' => $endedAt,
                'duration_seconds' => $duration,
                'status' => OnlineConsultation::STATUS_COMPLETED,
                'diagnosis' => $sessionData['diagnosis'] ?? $consultation->diagnosis,
                'doctor_notes' => $sessionData['doctor_notes'] ?? $consultation->doctor_notes,
            ]);

            // Create or link Visit
            $visit = Visit::create([
                'patient_id' => $consultation->patient_id,
                'doctor_id' => $consultation->doctor_id,
                'booking_id' => $consultation->booking_id,
                'booking_appointment_id' => $consultation->booking_appointment_id,
                'module' => $consultation->module,
                'visit_type' => 'consultation',
                'consultation_type' => 'online',
                'status' => 'completed',
                'diagnosis' => $consultation->diagnosis,
                'doctor_notes' => $consultation->doctor_notes,
                'started_at' => $consultation->session_started_at,
                'completed_at' => $endedAt,
                'visit_date' => $consultation->scheduled_date,
            ]);

            $consultation->update(['visit_id' => $visit->id]);

            // Telemedicine ⟶ Derma: a completed derma online consultation also
            // produces a derma session record (type 'other', cost 0 — the
            // consultation fee is billed separately) so the encounter appears
            // in the derma session log and the patient's portal timeline.
            if ($consultation->module === 'derma') {
                \App\Models\DermaSession::create([
                    'patient_id' => $consultation->patient_id,
                    'doctor_id' => $consultation->doctor_id,
                    'visit_id' => $visit->id,
                    'session_type' => 'other',
                    'cost' => 0,
                    'completed_at' => $endedAt,
                    'notes' => $consultation->diagnosis ?: $consultation->doctor_notes,
                ]);
            }

            try {
                event(new \App\Events\OnlineConsultation\SessionEnded($consultation->fresh()));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Broadcast failed: SessionEnded', ['err' => $e->getMessage()]);
            }

            return $visit;
        });

        // P3-3: route the telemedicine visit through the SAME post-visit pipeline
        // as an in-clinic visit (summary email, completion SMS, loyalty), so the
        // patient receives a consultation summary after the call. Fired after
        // commit; listeners guard on the patient's notification preferences.
        try {
            event(new \App\Events\VisitCompleted($visit->fresh() ?? $visit));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('VisitCompleted dispatch failed (online consultation)', [
                'visit_id' => $visit->id, 'error' => $e->getMessage(),
            ]);
        }

        return $visit;
    }

    public function handleMissedSession(OnlineConsultation $consultation): void
    {
        if ($consultation->status === OnlineConsultation::STATUS_COMPLETED) {
            return;
        }

        $missed = null;
        if (! $consultation->doctor_joined_at && $consultation->patient_joined_at) {
            $missed = OnlineConsultation::STATUS_MISSED_DOCTOR;
        } elseif ($consultation->doctor_joined_at && ! $consultation->patient_joined_at) {
            $missed = OnlineConsultation::STATUS_MISSED_PATIENT;
        } elseif (! $consultation->doctor_joined_at && ! $consultation->patient_joined_at) {
            $missed = OnlineConsultation::STATUS_MISSED_PATIENT; // both missed = blame patient
        }

        if ($missed) {
            $consultation->update(['status' => $missed]);
        }
    }
}
