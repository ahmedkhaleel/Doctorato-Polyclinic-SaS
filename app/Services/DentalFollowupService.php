<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DentalFollowupRule;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatment;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\DentalFollowUpReminderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DentalFollowupService
{
    /**
     * Schedule a follow-up when a treatment is completed.
     * Called from DentalTreatmentService::handlePostSave().
     */
    public function scheduleForCompletedTreatment(DentalTreatment $treatment): ?DentalScheduledFollowup
    {
        // Check if auto-followup is enabled
        if (Setting::get('dental_auto_followup_enabled', '1') !== '1') {
            return null;
        }

        // Find active rule for this treatment type
        $rule = DentalFollowupRule::active()
            ->forType($treatment->treatment_type)
            ->first();

        if (!$rule) {
            return null;
        }

        // Don't create duplicate for same treatment
        $exists = DentalScheduledFollowup::where('dental_treatment_id', $treatment->id)
            ->whereIn('status', ['pending', 'booking_created', 'sms_sent'])
            ->exists();

        if ($exists) {
            return null;
        }

        return DB::transaction(function () use ($treatment, $rule) {
            $scheduledDate = $treatment->completed_at
                ? $treatment->completed_at->addDays($rule->followup_days)
                : now()->addDays($rule->followup_days);

            // Skip if date falls on a Friday (weekend in Iraq)
            if ($scheduledDate->isFriday()) {
                $scheduledDate = $scheduledDate->addDay(); // move to Saturday
            }

            $followup = DentalScheduledFollowup::create([
                'dental_treatment_id' => $treatment->id,
                'patient_id'          => $treatment->patient_id,
                'doctor_id'           => $treatment->doctor_id,
                'followup_rule_id'    => $rule->id,
                'scheduled_date'      => $scheduledDate,
                'status'              => 'pending',
                'notes'               => $rule->label_ar . ' - ' . $treatment->treatment_type,
            ]);

            // Update the treatment's followup_reminder_at
            $treatment->update(['followup_reminder_at' => $scheduledDate]);

            // Auto-create booking if enabled
            if ($rule->auto_create_booking) {
                $this->createFollowupBooking($followup, $treatment, $rule);
            }

            // Notify staff immediately
            if ($rule->notify_doctor || $rule->notify_secretary) {
                $this->notifyStaff($followup, $treatment, $rule);
            }

            AuditLogger::log('created', $followup, null,
                "Auto follow-up scheduled: {$treatment->treatment_type} → {$scheduledDate->format('Y-m-d')} ({$rule->followup_days} days)");

            return $followup;
        });
    }

    /**
     * Create a booking for a scheduled follow-up.
     */
    public function createFollowupBooking(
        DentalScheduledFollowup $followup,
        DentalTreatment $treatment,
        DentalFollowupRule $rule
    ): ?Booking {
        $patient = $treatment->patient;
        if (!$patient) return null;

        try {
            $booking = Booking::create([
                'patient_id'     => $patient->id,
                'doctor_id'      => $treatment->doctor_id,
                'booking_number' => Booking::generateBookingNumber(),
                'source'         => 'system',
                'module'         => 'dental',
                'booking_type'   => 'follow_up',
                'full_name'      => $patient->full_name,
                'phone'          => $patient->phone,
                'email'          => $patient->email,
                'preferred_date' => $followup->scheduled_date,
                'preferred_time' => '09:00',
                'status'         => 'confirmed',
                'notes'          => "متابعة تلقائية: {$rule->label_ar} (سن #{$treatment->tooth_number})",
                'admin_notes'    => "Auto follow-up for {$treatment->treatment_type} treatment #{$treatment->id}",
            ]);

            $followup->update([
                'booking_id'         => $booking->id,
                'status'             => 'booking_created',
                'booking_created_at' => now(),
            ]);

            return $booking;
        } catch (\Throwable $e) {
            Log::error('Failed to create follow-up booking', [
                'followup_id' => $followup->id,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send SMS reminder to patient for upcoming follow-up.
     */
    public function sendSmsReminder(DentalScheduledFollowup $followup): array
    {
        $patient = $followup->patient;
        if (!$patient || !$patient->phone) {
            return ['success' => false, 'message' => 'No phone'];
        }

        $clinicName = Setting::get('clinic_name_ar', 'Aura Derma Clinic');
        $clinicPhone = Setting::get('clinic_phone', '');
        $rule = $followup->rule;

        $treatmentLabel = $rule ? $rule->label_ar : 'متابعة';
        $dateFormatted = $followup->scheduled_date->format('d/m/Y');
        $doctorName = $followup->doctor?->name_ar ?? '';

        $message = "مرحباً {$patient->full_name}\n"
            . "تذكير بموعد {$treatmentLabel}\n"
            . "التاريخ: {$dateFormatted}\n";

        if ($doctorName) {
            $message .= "الطبيب: د. {$doctorName}\n";
        }

        $message .= ($clinicPhone ? "للتأكيد: {$clinicPhone}\n" : '')
            . $clinicName;

        try {
            $result = SmsService::send($patient->phone, $message);

            if ($result['success'] ?? false) {
                $followup->update([
                    'reminder_sent_at' => now(),
                    'status' => $followup->status === 'pending' ? 'sms_sent' : $followup->status,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Follow-up SMS failed', [
                'followup_id' => $followup->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send initial confirmation SMS when follow-up is scheduled.
     */
    public function sendScheduledSms(DentalScheduledFollowup $followup): array
    {
        $patient = $followup->patient;
        if (!$patient || !$patient->phone) {
            return ['success' => false, 'message' => 'No phone'];
        }

        $clinicName = Setting::get('clinic_name_ar', 'Aura Derma Clinic');
        $clinicPhone = Setting::get('clinic_phone', '');
        $rule = $followup->rule;

        $treatmentLabel = $rule ? $rule->label_ar : 'متابعة';
        $dateFormatted = $followup->scheduled_date->format('d/m/Y');

        $message = "مرحباً {$patient->full_name}\n"
            . "تم جدولة موعد {$treatmentLabel} لك\n"
            . "التاريخ: {$dateFormatted}\n"
            . ($clinicPhone ? "للاستفسار: {$clinicPhone}\n" : '')
            . $clinicName;

        try {
            $result = SmsService::send($patient->phone, $message);

            if ($result['success'] ?? false) {
                $followup->update(['sms_sent_at' => now()]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Follow-up scheduled SMS failed', [
                'followup_id' => $followup->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Notify staff (doctor + secretaries) about the scheduled follow-up.
     */
    protected function notifyStaff(
        DentalScheduledFollowup $followup,
        DentalTreatment $treatment,
        DentalFollowupRule $rule
    ): void {
        $recipients = collect();

        if ($rule->notify_secretary) {
            $secretaries = User::whereHas('role', fn ($q) => $q->where('name', 'secretary'))
                ->where('is_active', true)->get();
            $recipients = $recipients->merge($secretaries);
        }

        if ($rule->notify_doctor && $treatment->doctor?->user) {
            $recipients->push($treatment->doctor->user);
        }

        $recipients = $recipients->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new DentalFollowUpReminderNotification($treatment));
        }
    }

    /**
     * Seed default rules (used in migration or seeder).
     */
    public static function seedDefaultRules(): void
    {
        foreach (DentalFollowupRule::DEFAULTS as $type => $config) {
            DentalFollowupRule::updateOrCreate(
                ['treatment_type' => $type],
                [
                    'label_ar'            => $config['ar'],
                    'label_en'            => $config['en'],
                    'followup_days'       => $config['days'],
                    'auto_create_booking' => true,
                    'sms_patient'         => true,
                    'notify_doctor'       => true,
                    'notify_secretary'    => true,
                    'sms_days_before'     => 1,
                    'is_active'           => true,
                    'sort_order'          => 0,
                ]
            );
        }
    }
}
