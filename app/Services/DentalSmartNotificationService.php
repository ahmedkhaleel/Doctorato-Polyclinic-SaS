<?php

namespace App\Services;

use App\Models\DentalLabOrder;
use App\Models\DentalSmartNotification;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Visit;
use Illuminate\Support\Facades\Log;

/**
 * Smart Patient Notification Service
 *
 * Handles 5 notification types:
 * 1. Follow-up Reminder         — تذكير بموعد المتابعة
 * 2. Lab Order Ready             — تنبيه بجاهزية طلب المعمل
 * 3. Stalled Plan Reminder       — تذكير بإكمال خطة العلاج المتوقفة
 * 4. Post-Treatment Check        — رسالة بعد العلاج "كيف حالك؟"
 * 5. Pre-Appointment Alert       — تنبيه طبي قبل الموعد (مرضى عالية الخطورة)
 */
class DentalSmartNotificationService
{
    // ──────────────────────────────────────────────────────
    // 1. FOLLOW-UP REMINDER — تذكير بموعد المتابعة
    // ──────────────────────────────────────────────────────

    /**
     * Scan for upcoming follow-up appointments and queue reminders.
     * Triggered by scheduled command, checks bookings due in 1-2 days.
     */
    public static function scanFollowUpReminders(): int
    {
        $count = 0;

        // Find treatments completed in last 7-14 days with no follow-up booking
        $treatments = DentalTreatment::with(['patient', 'doctor'])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [now()->subDays(14), now()->subDays(7)])
            ->whereDoesntHave('scheduledFollowups', function ($q) {
                $q->whereIn('status', ['pending', 'booking_created', 'sms_sent']);
            })
            ->get();

        foreach ($treatments as $treatment) {
            if (!$treatment->patient?->phone) continue;

            $dedupKey = DentalSmartNotification::dedupKey(
                DentalSmartNotification::TYPE_FOLLOWUP_REMINDER,
                $treatment->patient_id,
                DentalTreatment::class,
                $treatment->id
            );

            if (DentalSmartNotification::alreadySent($dedupKey)) continue;

            $clinicName = Setting::get('clinic_name_ar', 'عيادة دكتوراتو');
            $clinicPhone = Setting::get('clinic_phone', '');
            $treatmentLabel = self::getTreatmentLabel($treatment->treatment_type);
            $daysSince = now()->diffInDays($treatment->completed_at);

            $messageAr = "مرحباً {$treatment->patient->full_name} 👋\n"
                . "مضى {$daysSince} يوم على علاج {$treatmentLabel}.\n"
                . "ننصحك بحجز موعد متابعة للاطمئنان على صحة أسنانك.\n"
                . ($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
                . "{$clinicName}";

            $messageEn = "Hello {$treatment->patient->full_name},\n"
                . "It has been {$daysSince} days since your {$treatment->treatment_type} treatment.\n"
                . "We recommend scheduling a follow-up appointment.\n"
                . ($clinicPhone ? "To book: {$clinicPhone}\n" : '')
                . $clinicName;

            DentalSmartNotification::create([
                'patient_id' => $treatment->patient_id,
                'doctor_id' => $treatment->doctor_id,
                'type' => DentalSmartNotification::TYPE_FOLLOWUP_REMINDER,
                'channel' => 'sms',
                'notifiable_type' => DentalTreatment::class,
                'notifiable_id' => $treatment->id,
                'message_ar' => $messageAr,
                'message_en' => $messageEn,
                'status' => 'pending',
                'scheduled_at' => now(),
                'phone' => $treatment->patient->phone,
                'is_auto' => true,
                'dedup_key' => $dedupKey,
            ]);

            $count++;
        }

        return $count;
    }

    // ──────────────────────────────────────────────────────
    // 2. LAB ORDER READY — تنبيه بجاهزية طلب المعمل
    // ──────────────────────────────────────────────────────

    /**
     * Queue notification when a lab order status changes to 'ready' or 'delivered'.
     * Called directly from the controller when lab order status is updated.
     */
    public static function queueLabOrderReady(DentalLabOrder $labOrder): ?DentalSmartNotification
    {
        $patient = $labOrder->patient;
        if (!$patient?->phone) return null;

        $dedupKey = DentalSmartNotification::dedupKey(
            DentalSmartNotification::TYPE_LAB_ORDER_READY,
            $patient->id,
            DentalLabOrder::class,
            $labOrder->id
        );

        if (DentalSmartNotification::alreadySent($dedupKey)) return null;

        $clinicName = Setting::get('clinic_name_ar', 'عيادة دكتوراتو');
        $clinicPhone = Setting::get('clinic_phone', '');

        $itemLabels = [
            'crown' => 'التاج',
            'bridge' => 'الجسر',
            'denture' => 'طقم الأسنان',
            'retainer' => 'المثبت',
            'aligner' => 'التقويم الشفاف',
            'veneer' => 'الفينير',
            'implant_abutment' => 'دعامة الزرعة',
            'night_guard' => 'الحارس الليلي',
            'inlay_onlay' => 'الحشوة التجميلية',
        ];

        $itemLabel = $itemLabels[$labOrder->item_type] ?? $labOrder->item_type;

        $messageAr = "مرحباً {$patient->full_name} 🎉\n"
            . "يسرنا إبلاغكم أن {$itemLabel} جاهز!\n"
            . "يرجى التواصل معنا لتحديد موعد التركيب في أقرب وقت.\n"
            . ($clinicPhone ? "📞 للحجز: {$clinicPhone}\n" : '')
            . "{$clinicName}";

        $messageEn = "Hello {$patient->full_name},\n"
            . "Great news! Your {$labOrder->item_type} is ready.\n"
            . "Please contact us to schedule your fitting appointment.\n"
            . ($clinicPhone ? "Book: {$clinicPhone}\n" : '')
            . $clinicName;

        return DentalSmartNotification::create([
            'patient_id' => $patient->id,
            'doctor_id' => $labOrder->doctor_id,
            'type' => DentalSmartNotification::TYPE_LAB_ORDER_READY,
            'channel' => 'sms',
            'notifiable_type' => DentalLabOrder::class,
            'notifiable_id' => $labOrder->id,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'status' => 'pending',
            'scheduled_at' => now(),
            'phone' => $patient->phone,
            'is_auto' => true,
            'dedup_key' => $dedupKey,
        ]);
    }

    // ──────────────────────────────────────────────────────
    // 3. STALLED PLAN REMINDER — تذكير بإكمال خطة العلاج المتوقفة
    // ──────────────────────────────────────────────────────

    /**
     * Scan for treatment plans that have stalled (no activity in X days).
     * A plan is "stalled" if: in_progress + no treatment completed in last 14 days.
     */
    public static function scanStalledPlans(): int
    {
        $count = 0;

        $plans = DentalTreatmentPlan::with(['patient', 'doctor', 'treatments'])
            ->where('status', 'in_progress')
            ->get();

        foreach ($plans as $plan) {
            if (!$plan->patient?->phone) continue;

            // Check if any treatment was completed in last 14 days
            $recentActivity = $plan->treatments()
                ->where(function ($q) {
                    $q->where('updated_at', '>=', now()->subDays(14))
                      ->where('status', 'completed');
                })
                ->exists();

            if ($recentActivity) continue;

            // Check if plan has been updated recently
            if ($plan->updated_at >= now()->subDays(14)) continue;

            $dedupKey = DentalSmartNotification::dedupKey(
                DentalSmartNotification::TYPE_STALLED_PLAN,
                $plan->patient_id,
                DentalTreatmentPlan::class,
                $plan->id
            );

            if (DentalSmartNotification::alreadySent($dedupKey)) continue;

            $clinicName = Setting::get('clinic_name_ar', 'عيادة دكتوراتو');
            $clinicPhone = Setting::get('clinic_phone', '');
            $planTitle = $plan->title_ar ?: $plan->title_en ?: 'خطة العلاج';
            $completedCount = $plan->treatments()->where('status', 'completed')->count();
            $totalCount = $plan->treatments()->count();
            $remaining = $totalCount - $completedCount;

            $messageAr = "مرحباً {$plan->patient->full_name} 💙\n"
                . "نلاحظ أن {$planTitle} متوقفة.\n"
                . "تم إنجاز {$completedCount} من أصل {$totalCount} إجراء";
            if ($remaining > 0) {
                $messageAr .= " (متبقي {$remaining})";
            }
            $messageAr .= ".\n"
                . "إكمال العلاج مهم لصحة أسنانك.\n"
                . ($clinicPhone ? "📞 لاستئناف العلاج: {$clinicPhone}\n" : '')
                . "{$clinicName}";

            $messageEn = "Hello {$plan->patient->full_name},\n"
                . "We noticed your treatment plan \"{$plan->title_en}\" has been inactive.\n"
                . "{$completedCount} of {$totalCount} procedures completed ({$remaining} remaining).\n"
                . "Completing your treatment is important for your dental health.\n"
                . ($clinicPhone ? "To resume: {$clinicPhone}\n" : '')
                . $clinicName;

            DentalSmartNotification::create([
                'patient_id' => $plan->patient_id,
                'doctor_id' => $plan->doctor_id,
                'type' => DentalSmartNotification::TYPE_STALLED_PLAN,
                'channel' => 'sms',
                'notifiable_type' => DentalTreatmentPlan::class,
                'notifiable_id' => $plan->id,
                'message_ar' => $messageAr,
                'message_en' => $messageEn,
                'status' => 'pending',
                'scheduled_at' => now(),
                'phone' => $plan->patient->phone,
                'is_auto' => true,
                'dedup_key' => $dedupKey,
            ]);

            $count++;
        }

        return $count;
    }

    // ──────────────────────────────────────────────────────
    // 4. POST-TREATMENT CHECK — رسالة بعد العلاج "كيف حالك؟"
    // ──────────────────────────────────────────────────────

    /**
     * Queue a post-treatment wellness check message.
     * Called when a treatment is marked as completed.
     * Scheduled to send 48 hours after completion.
     */
    public static function queuePostTreatmentCheck(DentalTreatment $treatment): ?DentalSmartNotification
    {
        $patient = $treatment->patient;
        if (!$patient?->phone) return null;

        $dedupKey = DentalSmartNotification::dedupKey(
            DentalSmartNotification::TYPE_POST_TREATMENT,
            $patient->id,
            DentalTreatment::class,
            $treatment->id
        );

        if (DentalSmartNotification::alreadySent($dedupKey)) return null;

        $clinicName = Setting::get('clinic_name_ar', 'عيادة دكتوراتو');
        $clinicPhone = Setting::get('clinic_phone', '');
        $treatmentLabel = self::getTreatmentLabel($treatment->treatment_type);
        $doctorName = $treatment->doctor?->name_ar ?? $treatment->doctor?->name_en ?? '';

        $messageAr = "مرحباً {$patient->full_name} 😊\n"
            . "نتمنى أنك بخير بعد علاج {$treatmentLabel}";
        if ($treatment->tooth_number) {
            $messageAr .= " (سن {$treatment->tooth_number})";
        }
        $messageAr .= ".\n"
            . "كيف حالك؟ هل تشعر بأي ألم أو انزعاج؟\n"
            . "إذا كان لديك أي استفسار، لا تتردد بالتواصل معنا.\n"
            . ($clinicPhone ? "📞 {$clinicPhone}\n" : '')
            . ($doctorName ? "د. {$doctorName} - " : '')
            . "{$clinicName}";

        $messageEn = "Hello {$patient->full_name},\n"
            . "We hope you're doing well after your {$treatment->treatment_type} treatment";
        if ($treatment->tooth_number) {
            $messageEn .= " (tooth #{$treatment->tooth_number})";
        }
        $messageEn .= ".\n"
            . "How are you feeling? Any pain or discomfort?\n"
            . "Don't hesitate to contact us if you have any concerns.\n"
            . ($clinicPhone ? "Call: {$clinicPhone}\n" : '')
            . $clinicName;

        $delayHours = DentalSmartNotification::DEFAULT_DELAYS[DentalSmartNotification::TYPE_POST_TREATMENT];

        return DentalSmartNotification::create([
            'patient_id' => $patient->id,
            'doctor_id' => $treatment->doctor_id,
            'type' => DentalSmartNotification::TYPE_POST_TREATMENT,
            'channel' => 'sms',
            'notifiable_type' => DentalTreatment::class,
            'notifiable_id' => $treatment->id,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'status' => 'pending',
            'scheduled_at' => now()->addHours($delayHours),
            'phone' => $patient->phone,
            'delay_hours' => $delayHours,
            'is_auto' => true,
            'dedup_key' => $dedupKey,
        ]);
    }

    // ──────────────────────────────────────────────────────
    // 5. PRE-APPOINTMENT MEDICAL ALERT — تنبيه طبي قبل الموعد
    // ──────────────────────────────────────────────────────

    /**
     * Scan for upcoming dental appointments (tomorrow) where the patient has
     * high-risk medical flags. Sends SMS to patient reminding them of
     * medication instructions AND creates a database alert for the doctor.
     *
     * High-risk conditions checked:
     * - Blood thinners (stop before surgery)
     * - Bleeding disorder
     * - Heart condition (antibiotic prophylaxis)
     * - Latex allergy (prepare latex-free supplies)
     * - Anesthesia complications
     * - Pregnant/breastfeeding (limit medications)
     */
    public static function scanPreAppointmentAlerts(): int
    {
        $count = 0;
        $tomorrow = now()->addDay()->toDateString();

        // Get dental visits scheduled for tomorrow
        $visits = Visit::with(['patient', 'doctor'])
            ->where('module', 'dental')
            ->whereDate('visit_date', $tomorrow)
            ->whereIn('status', ['booked', 'confirmed', 'checked_in'])
            ->get();

        foreach ($visits as $visit) {
            $patient = $visit->patient;
            if (!$patient) continue;

            // Check for high-risk flags
            $riskFlags = $patient->getDentalRiskFlags();
            $highRiskFlags = array_filter($riskFlags, fn ($f) => $f['severity'] === 'high');

            if (empty($highRiskFlags)) continue;

            $dedupKey = DentalSmartNotification::dedupKey(
                DentalSmartNotification::TYPE_PRE_APPOINTMENT_ALERT,
                $patient->id,
                Visit::class,
                $visit->id
            );

            if (DentalSmartNotification::alreadySent($dedupKey)) continue;

            $clinicName = Setting::get('clinic_name_ar', 'عيادة دكتوراتو');
            $clinicPhone = Setting::get('clinic_phone', '');

            // Build specific medical instructions
            $instructionsAr = [];
            $instructionsEn = [];

            foreach ($highRiskFlags as $flag) {
                switch ($flag['key']) {
                    case 'takes_blood_thinners':
                        $med = $patient->blood_thinner_name ?? 'أدوية مسيلة الدم';
                        $instructionsAr[] = "⚠ يرجى استشارة طبيبك بخصوص إيقاف {$med} قبل الموعد";
                        $instructionsEn[] = "Consult your physician about stopping {$med} before appointment";
                        break;
                    case 'has_heart_condition':
                        $instructionsAr[] = "⚠ قد تحتاج مضاد حيوي وقائي قبل العلاج — سيحدد الطبيب";
                        $instructionsEn[] = "You may need prophylactic antibiotics — doctor will advise";
                        break;
                    case 'latex_allergy':
                        $instructionsAr[] = "⚠ تم تسجيل حساسية اللاتكس — سنوفر مواد بديلة";
                        $instructionsEn[] = "Latex allergy noted — latex-free supplies will be prepared";
                        break;
                    case 'has_bleeding_disorder':
                        $instructionsAr[] = "⚠ اضطراب نزيف — يرجى إبلاغنا بأي أدوية جديدة";
                        $instructionsEn[] = "Bleeding disorder — please inform us of any new medications";
                        break;
                    case 'is_pregnant':
                        $instructionsAr[] = "⚠ حمل — سيتم تعديل العلاج والأدوية وفقاً لذلك";
                        $instructionsEn[] = "Pregnancy noted — treatment and medications will be adjusted";
                        break;
                    case 'anesthesia_complications':
                        $instructionsAr[] = "⚠ مضاعفات تخدير سابقة — سيتم اتخاذ احتياطات إضافية";
                        $instructionsEn[] = "Previous anesthesia complications — extra precautions will be taken";
                        break;
                    case 'has_hepatitis':
                    case 'has_hiv':
                        $instructionsAr[] = "⚠ يرجى إبلاغ الفريق الطبي بأي تغييرات في حالتك الصحية";
                        $instructionsEn[] = "Please inform the medical team of any changes in your condition";
                        break;
                }
            }

            $riskLabelsAr = implode('، ', array_map(fn ($f) => $f['label_ar'], $highRiskFlags));
            $riskLabelsEn = implode(', ', array_map(fn ($f) => $f['label_en'], $highRiskFlags));

            $messageAr = "مرحباً {$patient->full_name} 🏥\n"
                . "تذكير: لديك موعد أسنان غداً.\n"
                . "تنبيهات طبية مهمة: {$riskLabelsAr}\n";
            if (!empty($instructionsAr)) {
                $messageAr .= "\n" . implode("\n", $instructionsAr) . "\n";
            }
            $messageAr .= "\n" . ($clinicPhone ? "📞 للاستفسار: {$clinicPhone}\n" : '')
                . "{$clinicName}";

            $messageEn = "Hello {$patient->full_name},\n"
                . "Reminder: You have a dental appointment tomorrow.\n"
                . "Medical alerts: {$riskLabelsEn}\n";
            if (!empty($instructionsEn)) {
                $messageEn .= "\n" . implode("\n", $instructionsEn) . "\n";
            }
            $messageEn .= "\n" . ($clinicPhone ? "Inquiries: {$clinicPhone}\n" : '')
                . $clinicName;

            DentalSmartNotification::create([
                'patient_id' => $patient->id,
                'doctor_id' => $visit->doctor_id,
                'type' => DentalSmartNotification::TYPE_PRE_APPOINTMENT_ALERT,
                'channel' => 'sms',
                'notifiable_type' => Visit::class,
                'notifiable_id' => $visit->id,
                'message_ar' => $messageAr,
                'message_en' => $messageEn,
                'status' => 'pending',
                'scheduled_at' => now(),
                'phone' => $patient->phone,
                'is_auto' => true,
                'dedup_key' => $dedupKey,
            ]);

            $count++;
        }

        return $count;
    }

    // ──────────────────────────────────────────────────────
    // SCAN ALL (for scheduled command)
    // ──────────────────────────────────────────────────────

    /**
     * Scan all notification types and scan for pending triggers.
     * Also sends any ready-to-send queued notifications.
     */
    public static function scanAndSend(): array
    {
        $results = [
            'followup_scanned' => 0,
            'stalled_scanned' => 0,
            'pre_appointment_scanned' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        // Scan for new notifications to queue
        $results['followup_scanned'] = self::scanFollowUpReminders();
        $results['stalled_scanned'] = self::scanStalledPlans();
        $results['pre_appointment_scanned'] = self::scanPreAppointmentAlerts();

        // Send all pending notifications that are ready
        $results = array_merge($results, self::sendPendingNotifications());

        return $results;
    }

    /**
     * Send all queued notifications that are ready.
     */
    public static function sendPendingNotifications(): array
    {
        $sent = 0;
        $failed = 0;

        $notifications = DentalSmartNotification::readyToSend()
            ->orderBy('scheduled_at')
            ->limit(100)
            ->get();

        foreach ($notifications as $notification) {
            $result = self::sendNotification($notification);
            if ($result) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Send a single notification via its channel.
     */
    public static function sendNotification(DentalSmartNotification $notification): bool
    {
        if (!$notification->phone) {
            $notification->markFailed('No phone number');
            return false;
        }

        if (!SmsService::isEnabled()) {
            // Store as database-only notification, mark sent
            $notification->update([
                'status' => DentalSmartNotification::STATUS_SENT,
                'sent_at' => now(),
                'sms_provider' => 'database_only',
                'failure_reason' => 'SMS not enabled - stored as database notification only',
            ]);
            Log::info('Smart notification stored (SMS disabled)', [
                'id' => $notification->id,
                'type' => $notification->type,
                'patient_id' => $notification->patient_id,
            ]);
            return true;
        }

        // Send SMS
        $result = SmsService::send($notification->phone, $notification->message_ar);

        if ($result['success'] ?? false) {
            $notification->markSent($result['provider'] ?? null);
            Log::info('Smart notification sent', [
                'id' => $notification->id,
                'type' => $notification->type,
                'patient_id' => $notification->patient_id,
                'provider' => $result['provider'] ?? null,
            ]);
            return true;
        } else {
            $notification->markFailed($result['message'] ?? 'Unknown error');
            Log::warning('Smart notification failed', [
                'id' => $notification->id,
                'type' => $notification->type,
                'error' => $result['message'] ?? 'Unknown',
            ]);
            return false;
        }
    }

    // ──────────────────────────────────────────────────────
    // MANUAL SEND (admin can send any type manually)
    // ──────────────────────────────────────────────────────

    /**
     * Manually create and queue a custom notification for a patient.
     */
    public static function sendManual(
        Patient $patient,
        string $type,
        string $messageAr,
        string $messageEn = null,
        ?int $doctorId = null,
        string $notifiableType = null,
        ?int $notifiableId = null
    ): DentalSmartNotification {
        return DentalSmartNotification::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorId,
            'type' => $type,
            'channel' => 'sms',
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'status' => 'pending',
            'scheduled_at' => now(),
            'phone' => $patient->phone,
            'is_auto' => false,
        ]);
    }

    // ──────────────────────────────────────────────────────
    // STATISTICS
    // ──────────────────────────────────────────────────────

    public static function getStats(): array
    {
        $base = DentalSmartNotification::query();

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->pending()->count(),
            'sent' => (clone $base)->sent()->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
            'by_type' => [
                'followup_reminder' => (clone $base)->ofType('followup_reminder')->count(),
                'lab_order_ready' => (clone $base)->ofType('lab_order_ready')->count(),
                'stalled_plan_reminder' => (clone $base)->ofType('stalled_plan_reminder')->count(),
                'post_treatment_checkup' => (clone $base)->ofType('post_treatment_checkup')->count(),
                'pre_appointment_alert' => (clone $base)->ofType('pre_appointment_alert')->count(),
            ],
            'today_sent' => (clone $base)->whereDate('sent_at', today())->count(),
            'this_week_sent' => (clone $base)->where('sent_at', '>=', now()->startOfWeek())->count(),
            'responded_count' => (clone $base)->where('patient_responded', true)->count(),
        ];
    }

    // ──────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────

    private static function getTreatmentLabel(string $type): string
    {
        $labels = [
            'filling' => 'الحشوة',
            'root_canal' => 'علاج العصب',
            'extraction' => 'خلع السن',
            'crown' => 'التاج',
            'bridge' => 'الجسر',
            'implant' => 'الزراعة',
            'scaling' => 'تنظيف الأسنان',
            'whitening' => 'تبييض الأسنان',
            'veneer' => 'الفينير',
            'orthodontic' => 'التقويم',
            'denture' => 'طقم الأسنان',
            'sealant' => 'الحشوة الوقائية',
            'fluoride' => 'الفلورايد',
            'consultation' => 'الاستشارة',
            'xray' => 'الأشعة',
            'periodontal' => 'علاج اللثة',
            'surgery' => 'الجراحة',
            'night_guard' => 'الحارس الليلي',
            'retainer' => 'المثبت',
            'other' => 'العلاج',
        ];

        return $labels[$type] ?? 'العلاج';
    }
}
