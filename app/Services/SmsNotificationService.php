<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Visit;
use App\Services\Notifications\Notifier;

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
        $phone = $booking->phone ?? $booking->patient?->phone;
        if (! $phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $patientName = $booking->full_name ?? $booking->patient?->full_name ?? '';
        $bookingNumber = $booking->booking_number ?? '#'.$booking->id;
        $date = $booking->preferred_date?->format('d/m/Y') ?? '';
        $time = $booking->preferred_time ?? '';
        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $moduleLabel = match ($booking->module) {
            'dental' => 'أسنان',
            'pediatric' => 'أطفال',
            'obgyn' => 'نساء وتوليد',
            'psychiatry' => 'نفسية',
            'neurology' => 'أعصاب',
            default => 'جلدية',
        };

        $message = "مرحباً {$patientName} 👋\n"
            ."تم تأكيد حجزك في {$clinicName}\n"
            ."رقم الحجز: {$bookingNumber}\n"
            .($date ? "التاريخ: {$date}\n" : '')
            .($time ? "الوقت: {$time}\n" : '')
            ."القسم: {$moduleLabel}\n"
            .($clinicPhone ? "للاستفسار: {$clinicPhone}\n" : '')
            .'شكراً لاختياركم 🌟';

        // Unified hub: routing (whatsapp→sms→in_app), consent, logging, fallback.
        // A stored template (if any) wins over $body; otherwise this rich body sends.
        Notifier::event('booking.confirmed', $booking->patient, [
            'to' => $phone, 'body' => $message,
            'name' => $patientName, 'booking_number' => $bookingNumber,
            'date' => $date, 'time' => $time,
            'clinic_name' => $clinicName, 'clinic_phone' => $clinicPhone, 'module' => $moduleLabel,
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }

    /**
     * Send booking reminder SMS (1 day before appointment).
     */
    public static function bookingReminder(Booking $booking): array
    {
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
            ."مرحباً {$patientName}\n"
            ."لديك موعد غداً في {$clinicName}\n"
            .($time ? "الوقت: {$time}\n" : '')
            .($clinicPhone ? "لإعادة الجدولة: {$clinicPhone}\n" : '')
            .'نتطلع لرؤيتك! 😊';

        // Reminder category → respects per-channel consent in the hub.
        Notifier::event('appointment.reminder.day_before', $booking->patient, [
            'to' => $phone, 'body' => $message,
            'name' => $patientName, 'date' => $date, 'time' => $time,
            'clinic_name' => $clinicName, 'clinic_phone' => $clinicPhone,
            'dedup_key' => "appointment.reminder.day_before:booking:{$booking->id}",
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }

    /**
     * Send visit completion SMS with invoice info.
     */
    public static function visitCompleted(Visit $visit): array
    {
        $patient = $visit->patient;
        if (! $patient?->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));

        $message = "شكراً لزيارتك {$clinicName} 🙏\n"
            ."نتمنى لك دوام الصحة والعافية.\n"
            .'لأي استفسار لا تتردد بالتواصل معنا.';

        Notifier::event('visit.completed', $patient, [
            'to' => $patient->phone, 'body' => $message,
            'name' => $patient->full_name, 'clinic_name' => $clinicName,
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }

    /**
     * Send dental lab order ready notification.
     */
    public static function dentalLabOrderReady(Patient $patient, string $itemType = ''): array
    {
        if (! $patient->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحباً {$patient->full_name}\n"
            .'نود إعلامكم أن طلبكم من المعمل جاهز';
        if ($itemType) {
            $message .= " ({$itemType})";
        }
        $message .= ".\n"
            ."يرجى التواصل لتحديد موعد التركيب.\n"
            .($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            ."{$clinicName}";

        Notifier::event('dental.lab_ready', $patient, [
            'to' => $patient->phone, 'body' => $message,
            'name' => $patient->full_name, 'clinic_name' => $clinicName, 'clinic_phone' => $clinicPhone,
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }

    /**
     * Send dental treatment plan approval notification.
     */
    public static function treatmentPlanApproved(Patient $patient, string $planTitle = ''): array
    {
        if (! $patient->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحباً {$patient->full_name}\n"
            .'تمت الموافقة على خطة علاجك';
        if ($planTitle) {
            $message .= ": {$planTitle}";
        }
        $message .= ".\n"
            ."يرجى التواصل لبدء جلسات العلاج.\n"
            .($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            ."{$clinicName}";

        Notifier::event('dental.treatment_plan_approved', $patient, [
            'to' => $patient->phone, 'body' => $message,
            'name' => $patient->full_name, 'clinic_name' => $clinicName, 'clinic_phone' => $clinicPhone,
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }

    /**
     * Send same-day appointment reminder SMS.
     */
    public static function sameDayReminder(Booking $booking): array
    {
        $phone = $booking->phone ?? $booking->patient?->phone;
        if (! $phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $patientName = $booking->full_name ?? $booking->patient?->full_name ?? '';
        $time = $booking->preferred_time ?? '';
        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحبا {$patientName}\n"
            ."نذكركم بموعدكم اليوم في {$clinicName}\n"
            .($time ? "الوقت: {$time}\n" : '')
            .($clinicPhone ? "للاستفسار: {$clinicPhone}\n" : '')
            .'نتمنى لكم زيارة طيبة.';

        Notifier::event('appointment.reminder.same_day', $booking->patient, [
            'to' => $phone, 'body' => $message,
            'name' => $patientName, 'time' => $time,
            'clinic_name' => $clinicName, 'clinic_phone' => $clinicPhone,
            'dedup_key' => "appointment.reminder.same_day:booking:{$booking->id}",
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }

    /**
     * Send dental recall reminder SMS (periodic checkup reminder).
     */
    public static function dentalRecallReminder(Patient $patient, string $recallType = 'checkup', int $monthsSinceVisit = 6): array
    {
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
            ."مضى {$monthsSinceVisit} اشهر منذ اخر زيارة لك.\n"
            ."ننصحك بحجز موعد {$typeLabel} للحفاظ على صحة اسنانك.\n"
            .($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            ."{$clinicName}";

        // Recall = reminder category → respects per-channel consent.
        Notifier::event('dental.followup', $patient, [
            'to' => $patient->phone, 'body' => $message,
            'name' => $patient->full_name, 'clinic_name' => $clinicName, 'clinic_phone' => $clinicPhone,
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }

    /**
     * Send derma recall reminder SMS.
     */
    public static function dermaRecallReminder(Patient $patient, int $monthsSinceVisit = 6): array
    {
        if (! $patient->phone) {
            return ['success' => false, 'message' => 'No phone number available.'];
        }

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        $message = "مرحبا {$patient->full_name}\n"
            ."مضى {$monthsSinceVisit} اشهر منذ اخر زيارة لك.\n"
            ."ننصحك بحجز موعد متابعة للحفاظ على صحة بشرتك.\n"
            .($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
            ."{$clinicName}";

        Notifier::event('derma.recall', $patient, [
            'to' => $patient->phone, 'body' => $message,
            'name' => $patient->full_name, 'clinic_name' => $clinicName, 'clinic_phone' => $clinicPhone,
        ]);

        return ['success' => true, 'message' => 'Queued via notifications hub.'];
    }
}
