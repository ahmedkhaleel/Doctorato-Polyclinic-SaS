<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Visit;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS notifications for bookings, appointments, and patient events.
 * All messages are bilingual (Arabic primary, English fallback).
 */
class SmsNotificationService
{
    /**
     * Send booking confirmation SMS to the patient.
     */
    public static function bookingConfirmed(Booking $booking): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        $phone = $booking->phone ?? $booking->patient?->phone;
        if (! $phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $patientName = $booking->full_name ?? $booking->patient?->full_name ?? '';
        $bookingNumber = $booking->booking_number ?? '#' . $booking->id;
        $date = $booking->preferred_date?->format('d/m/Y') ?? '';
        $time = $booking->preferred_time ?? '';
        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $moduleLabel = $booking->module === 'dental' ? 'أسنان' : 'جلدية';

        $message = "مرحباً {$patientName} 👋\n"
            . "تم تأكيد حجزك في {$clinicName}\n"
            . "رقم الحجز: {$bookingNumber}\n"
            . ($date ? "التاريخ: {$date}\n" : '')
            . ($time ? "الوقت: {$time}\n" : '')
            . "القسم: {$moduleLabel}\n"
            . ($clinicPhone ? "للاستفسار: {$clinicPhone}\n" : '')
            . "شكراً لاختياركم 🌟";

        \App\Jobs\SendSmsJob::dispatch($phone, $message, null, 'booking_confirmed');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }

    /**
     * Send booking reminder SMS (1 day before appointment).
     */
    public static function bookingReminder(Booking $booking): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        $phone = $booking->phone ?? $booking->patient?->phone;
        if (! $phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $patientName = $booking->full_name ?? $booking->patient?->full_name ?? '';
        $date = $booking->preferred_date?->format('d/m/Y') ?? 'غداً';
        $time = $booking->preferred_time ?? '';
        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "تذكير 📋\n"
            . "مرحباً {$patientName}\n"
            . "لديك موعد غداً في {$clinicName}\n"
            . ($time ? "الوقت: {$time}\n" : '')
            . ($clinicPhone ? "لإعادة الجدولة: {$clinicPhone}\n" : '')
            . "نتطلع لرؤيتك! 😊";

        \App\Jobs\SendSmsJob::dispatch($phone, $message, null, 'booking_reminder');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }

    /**
     * Send visit completion SMS with invoice info.
     */
    public static function visitCompleted(Visit $visit): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        $patient = $visit->patient;
        if (! $patient?->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));

        $message = "شكراً لزيارتك {$clinicName} 🙏\n"
            . "نتمنى لك دوام الصحة والعافية.\n"
            . "لأي استفسار لا تتردد بالتواصل معنا.";

        \App\Jobs\SendSmsJob::dispatch($patient->phone, $message, null, 'visit_completed');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }

    /**
     * Send dental lab order ready notification.
     */
    public static function dentalLabOrderReady(Patient $patient, string $itemType = ''): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        if (! $patient->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحباً {$patient->full_name}\n"
            . "نود إعلامكم أن طلبكم من المعمل جاهز";
        if ($itemType) {
            $message .= " ({$itemType})";
        }
        $message .= ".\n"
            . "يرجى التواصل لتحديد موعد التركيب.\n"
            . ($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            . "{$clinicName}";

        \App\Jobs\SendSmsJob::dispatch($patient->phone, $message, null, 'dental_lab_ready');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }

    /**
     * Send dental treatment plan approval notification.
     */
    public static function treatmentPlanApproved(Patient $patient, string $planTitle = ''): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        if (! $patient->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحباً {$patient->full_name}\n"
            . "تمت الموافقة على خطة علاجك";
        if ($planTitle) {
            $message .= ": {$planTitle}";
        }
        $message .= ".\n"
            . "يرجى التواصل لبدء جلسات العلاج.\n"
            . ($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            . "{$clinicName}";

        \App\Jobs\SendSmsJob::dispatch($patient->phone, $message, null, 'treatment_plan_approved');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }

    /**
     * Send same-day appointment reminder SMS.
     */
    public static function sameDayReminder(Booking $booking): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        $phone = $booking->phone ?? $booking->patient?->phone;
        if (! $phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $patientName = $booking->full_name ?? $booking->patient?->full_name ?? '';
        $time = $booking->preferred_time ?? '';
        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحبا {$patientName}\n"
            . "نذكركم بموعدكم اليوم في {$clinicName}\n"
            . ($time ? "الوقت: {$time}\n" : '')
            . ($clinicPhone ? "للاستفسار: {$clinicPhone}\n" : '')
            . "نتمنى لكم زيارة طيبة.";

        \App\Jobs\SendSmsJob::dispatch($phone, $message, null, 'same_day_reminder');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }

    /**
     * Send dental recall reminder SMS (periodic checkup reminder).
     */
    public static function dentalRecallReminder(Patient $patient, string $recallType = 'checkup', int $monthsSinceVisit = 6): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        if (! $patient->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $typeLabel = match ($recallType) {
            'cleaning' => 'تنظيف الاسنان',
            'follow_up' => 'المتابعة',
            default => 'الفحص الدوري',
        };

        $message = "مرحبا {$patient->full_name}\n"
            . "مضى {$monthsSinceVisit} اشهر منذ اخر زيارة لك.\n"
            . "ننصحك بحجز موعد {$typeLabel} للحفاظ على صحة اسنانك.\n"
            . ($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            . "{$clinicName}";

        \App\Jobs\SendSmsJob::dispatch($patient->phone, $message, null, 'dental_recall');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }

    /**
     * Send derma recall reminder SMS.
     */
    public static function dermaRecallReminder(Patient $patient, int $monthsSinceVisit = 6): array
    {
        if (! SmsService::isEnabled()) {
            return ['success' => false, 'message' => 'SMS not enabled.'];
        }

        if (! $patient->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحبا {$patient->full_name}\n"
            . "مضى {$monthsSinceVisit} اشهر منذ اخر زيارة لك.\n"
            . "ننصحك بحجز موعد متابعة للحفاظ على صحة بشرتك.\n"
            . ($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            . "{$clinicName}";

        \App\Jobs\SendSmsJob::dispatch($patient->phone, $message, null, 'derma_recall');
        return ['success' => true, 'message' => 'SMS queued for delivery.'];
    }
}
