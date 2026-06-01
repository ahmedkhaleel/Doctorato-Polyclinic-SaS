<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills every remaining "operational" table so that ALL screens in the system
 * show data for a client demo (notifications hub, loyalty, referrals, payouts,
 * treatment plans, package bundles, OB/GYN profiles, service detail pages, etc.).
 *
 * Runs only inside the demo block of DatabaseSeeder (dev, or production with
 * SEED_DEMO_DATA=true). Reuses the patients/doctors/services/bookings/visits
 * created by DemoDataSeeder + ComprehensiveDemoSeeder.
 *
 * Idempotent: every block is skipped when its target table already has rows,
 * so a second SEED_DEMO_DATA pass never duplicates.
 */
class ShowcaseDemoSeeder extends Seeder
{
    private int $branch = 1;

    public function run(): void
    {
        $this->command->info('  → Showcase: filling remaining operational tables...');

        $now = now();

        $patients = DB::table('patients')->orderBy('id')->get();
        $doctors = DB::table('doctors')->orderBy('id')->get();
        $services = DB::table('services')->whereNotNull('price')->orderBy('id')->limit(12)->get();
        $supplies = DB::table('supplies')->orderBy('id')->limit(20)->get();
        $staff = DB::table('users')->orderBy('id')->get();
        $admin = $staff->first();

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            $this->command->warn('    No patients/doctors — skipping showcase.');

            return;
        }

        $this->medications($now);
        $this->contactMessages($now);
        $this->whatsappTemplates($now);
        $this->notificationCampaigns($now, $admin);
        $this->notificationSequences($now, $patients);
        $this->notificationLogs($now, $patients);
        $this->scheduledNotifications($now, $patients);
        $this->databaseNotifications($now, $staff);
        $this->notificationConsents($now, $patients);
        $this->loyaltyPoints($now, $patients, $admin);
        $this->patientReferrals($now, $patients);
        $this->doctorPayouts($now, $doctors, $admin);
        $this->dentalClinical($now, $patients, $doctors);
        $this->dermaPlans($now, $patients, $doctors);
        $this->cosmetic($now, $patients);
        $this->packageBundleBookings($now, $patients, $doctors, $admin);
        $this->obgynProfiles($now, $patients, $doctors);
        $this->bookingConsents($now, $admin);
        $this->serviceDetails($now, $services, $supplies);
        $this->postTags($now);
        $this->messages($now, $staff);
        $this->branchDoctor($now, $doctors);
        $this->accessLogs($now, $patients, $admin);
        $this->currentMonthActivity($now, $patients, $admin);

        $this->command->info('    Showcase data complete.');
    }

    private function fresh(string $table): bool
    {
        return DB::table($table)->count() === 0;
    }

    private function medications($now): void
    {
        if (! $this->fresh('medications')) {
            return;
        }
        $meds = [
            ['Amoxicillin', '500mg', 'ثلاث مرات يومياً', '7 أيام', 'مضاد حيوي'],
            ['Ibuprofen', '400mg', 'مرتين يومياً', '5 أيام', 'مسكن'],
            ['Paracetamol', '500mg', 'عند اللزوم', '5 أيام', 'مسكن وخافض حرارة'],
            ['Isotretinoin', '20mg', 'مرة يومياً', '3 أشهر', 'جلدية'],
            ['Cephalexin', '500mg', 'أربع مرات يومياً', '7 أيام', 'مضاد حيوي'],
            ['Metronidazole', '500mg', 'ثلاث مرات يومياً', '7 أيام', 'مضاد حيوي'],
            ['Chlorhexidine', 'غسول', 'مرتين يومياً', '14 يوم', 'فم وأسنان'],
            ['Hydrocortisone', 'كريم 1%', 'مرتين يومياً', '10 أيام', 'جلدية'],
            ['Folic Acid', '5mg', 'مرة يومياً', 'طوال الحمل', 'نساء وتوليد'],
            ['Vitamin D3', '1000 IU', 'مرة يومياً', '3 أشهر', 'مكملات'],
            ['Azithromycin', '250mg', 'مرة يومياً', '5 أيام', 'مضاد حيوي'],
            ['Diclofenac', '50mg', 'مرتين يومياً', '5 أيام', 'مسكن'],
            ['Clindamycin', 'جل موضعي', 'مرتين يومياً', '8 أسابيع', 'جلدية'],
            ['Amoxicillin/Clavulanate', '1g', 'مرتين يومياً', '7 أيام', 'مضاد حيوي'],
            ['Tranexamic Acid', '500mg', 'ثلاث مرات يومياً', '5 أيام', 'نساء وتوليد'],
        ];
        $rows = [];
        foreach ($meds as $m) {
            $rows[] = [
                'name' => $m[0], 'default_dosage' => $m[1], 'default_frequency' => $m[2],
                'default_duration' => $m[3], 'category' => $m[4], 'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        DB::table('medications')->insert($rows);
    }

    private function contactMessages($now): void
    {
        if (! $this->fresh('contact_messages')) {
            return;
        }
        $msgs = [
            ['سارة محمود', 'sara@example.com', '01001234567', 'استفسار عن الليزر', 'أريد معرفة أسعار جلسات الليزر وعدد الجلسات المطلوبة.', 1],
            ['أحمد فؤاد', 'ahmed@example.com', '01112345678', 'حجز كشف جلدية', 'هل يوجد مواعيد متاحة هذا الأسبوع لعيادة الجلدية؟', 1],
            ['منى السيد', 'mona@example.com', '01223456789', 'تقويم أسنان', 'كم تكلفة تركيب التقويم وما مدة العلاج المتوقعة؟', 0],
            ['خالد ناصر', 'khaled@example.com', '01034567890', 'متابعة طفل', 'أريد حجز موعد متابعة تطعيمات لطفلي عمره سنة.', 0],
            ['ريم عبدالله', 'reem@example.com', '01145678901', 'استشارة نسائية', 'هل تتوفر استشارة أونلاين لمتابعة الحمل؟', 0],
            ['عمر حسن', 'omar@example.com', '01256789012', 'شكر وتقدير', 'تجربة ممتازة مع العيادة، شكراً للطاقم الطبي.', 1],
        ];
        $rows = [];
        foreach ($msgs as $m) {
            $rows[] = [
                'name' => $m[0], 'email' => $m[1], 'phone' => $m[2], 'subject' => $m[3],
                'message' => $m[4], 'is_read' => $m[5], 'created_at' => $now->copy()->subDays(rand(1, 20)), 'updated_at' => $now,
            ];
        }
        DB::table('contact_messages')->insert($rows);
    }

    private function whatsappTemplates($now): void
    {
        if (! $this->fresh('whatsapp_templates')) {
            return;
        }
        $tpl = [
            ['booking_confirmation', 'تأكيد الحجز', 'booking_confirmed', ['name', 'date', 'time']],
            ['appointment_reminder', 'تذكير بالموعد', 'appointment_reminder', ['name', 'date', 'time', 'doctor']],
            ['invoice_issued', 'إصدار فاتورة', 'invoice_created', ['name', 'amount', 'invoice_number']],
            ['lab_ready', 'جاهزية التحاليل', 'lab_order_ready', ['name', 'order']],
            ['followup_due', 'موعد المتابعة', 'followup_due', ['name', 'days']],
        ];
        $rows = [];
        foreach ($tpl as $t) {
            $rows[] = [
                'name' => $t[0], 'language' => 'ar', 'event_key' => $t[2],
                'variables' => json_encode($t[3]),
                'body_preview' => 'مرحباً {{name}}، '.$t[1].' بتاريخ {{date}}.',
                'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        DB::table('whatsapp_templates')->insert($rows);
    }

    private function notificationCampaigns($now, $admin): void
    {
        if (! $this->fresh('notification_campaigns')) {
            return;
        }
        $rows = [
            ['حملة عروض الصيف', 'whatsapp', null, 'عروض خاصة على جلسات الليزر طوال شهر يونيو! احجز الآن.', 'sent', $now->copy()->subDays(10), 420, 415],
            ['تذكير المتابعة الدورية', 'sms', null, 'حان موعد متابعتك الدورية في عيادة الجلدية.', 'sent', $now->copy()->subDays(3), 180, 178],
            ['نشرة العيادة الشهرية', 'email', 'نصائح للعناية بالبشرة', 'تعرف على أحدث نصائح العناية بالبشرة من أطبائنا.', 'scheduled', $now->copy()->addDays(2), 0, 0],
        ];
        foreach ($rows as $r) {
            DB::table('notification_campaigns')->insert([
                'name' => $r[0], 'channel' => $r[1], 'subject' => $r[2], 'body_ar' => $r[3],
                'status' => $r[4], 'scheduled_at' => $r[4] === 'scheduled' ? $r[5] : null,
                'sent_at' => $r[4] === 'sent' ? $r[5] : null,
                'audience_count' => $r[6], 'sent_count' => $r[7], 'created_by' => $admin?->id,
                'ab_enabled' => 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function notificationSequences($now, $patients): void
    {
        if (! $this->fresh('notification_sequences')) {
            return;
        }
        $seqId = DB::table('notification_sequences')->insertGetId([
            'name' => 'ترحيب المريض الجديد', 'trigger_event' => 'patient_created',
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $seq2 = DB::table('notification_sequences')->insertGetId([
            'name' => 'متابعة ما بعد الزيارة', 'trigger_event' => 'visit_completed',
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $steps = [
            [$seqId, 1, 0, 'whatsapp', null, 'أهلاً بك في عيادتنا! نحن سعداء بانضمامك.'],
            [$seqId, 2, 1440, 'sms', null, 'لا تنسَ إكمال ملفك الطبي لتجربة أفضل.'],
            [$seq2, 1, 60, 'whatsapp', null, 'نتمنى أن تكون زيارتك مفيدة. كيف تقيّم تجربتك؟'],
            [$seq2, 2, 4320, 'email', 'متابعة حالتك', 'نذكّرك بموعد المتابعة القادم.'],
        ];
        foreach ($steps as $s) {
            DB::table('notification_sequence_steps')->insert([
                'sequence_id' => $s[0], 'position' => $s[1], 'delay_minutes' => $s[2],
                'channel' => $s[3], 'subject' => $s[4], 'body_ar' => $s[5],
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        foreach ($patients->take(5) as $p) {
            DB::table('notification_sequence_enrollments')->insert([
                'sequence_id' => $seqId, 'recipient_type' => 'App\\Models\\Patient', 'recipient_id' => $p->id,
                'current_step' => rand(1, 2), 'status' => 'active',
                'next_run_at' => $now->copy()->addDays(rand(1, 3)),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function notificationLogs($now, $patients): void
    {
        if (! $this->fresh('notification_logs')) {
            return;
        }
        $channels = ['whatsapp', 'sms', 'email'];
        $statuses = ['sent', 'delivered', 'read', 'failed'];
        $events = ['booking_confirmed', 'appointment_reminder', 'invoice_created', 'followup_due'];
        $rows = [];
        foreach ($patients as $i => $p) {
            $n = rand(1, 3);
            for ($k = 0; $k < $n; $k++) {
                $ch = $channels[array_rand($channels)];
                $st = $statuses[array_rand($statuses)];
                $rows[] = [
                    'branch_id' => $this->branch,
                    'recipient_type' => 'App\\Models\\Patient', 'recipient_id' => $p->id,
                    'to' => $p->phone ?? '0100000000', 'channel' => $ch, 'provider' => $ch === 'sms' ? 'twilio' : ($ch === 'email' ? 'smtp' : 'meta'),
                    'event_key' => $events[array_rand($events)], 'status' => $st,
                    'cost' => $ch === 'sms' ? 0.15 : 0,
                    'error' => $st === 'failed' ? 'recipient unreachable' : null,
                    'sent_at' => $now->copy()->subDays(rand(0, 25)),
                    'delivered_at' => in_array($st, ['delivered', 'read']) ? $now->copy()->subDays(rand(0, 25)) : null,
                    'read_at' => $st === 'read' ? $now->copy()->subDays(rand(0, 25)) : null,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        DB::table('notification_logs')->insert($rows);
    }

    private function scheduledNotifications($now, $patients): void
    {
        if (! $this->fresh('scheduled_notifications')) {
            return;
        }
        foreach ($patients->take(5) as $p) {
            DB::table('scheduled_notifications')->insert([
                'branch_id' => $this->branch, 'event_key' => 'appointment_reminder',
                'recipient_type' => 'App\\Models\\Patient', 'recipient_id' => $p->id,
                'data' => json_encode(['name' => $p->full_name ?? 'مريض']),
                'channels' => json_encode(['whatsapp', 'sms']),
                'reason' => 'تذكير قبل الموعد بـ 24 ساعة', 'send_after' => $now->copy()->addDays(rand(1, 5)),
                'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function databaseNotifications($now, $staff): void
    {
        if (! $this->fresh('notifications')) {
            return;
        }
        $titles = [
            'حجز جديد بانتظار التأكيد',
            'تم إصدار فاتورة جديدة',
            'طلب معمل جاهز للاستلام',
            'رسالة تواصل جديدة من الموقع',
            'تذكير: مراجعة كشوف الرواتب',
            'مريض جديد تم تسجيله',
        ];
        foreach ($staff->take(3) as $u) {
            foreach ($titles as $i => $t) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\\Notifications\\SystemNotification',
                    'notifiable_type' => 'App\\Models\\User', 'notifiable_id' => $u->id,
                    'data' => json_encode(['title' => $t, 'message' => $t, 'url' => '/admin']),
                    'read_at' => $i % 2 === 0 ? null : $now,
                    'created_at' => $now->copy()->subHours(rand(1, 72)), 'updated_at' => $now,
                ]);
            }
        }
    }

    private function notificationConsents($now, $patients): void
    {
        if (! $this->fresh('notification_consents')) {
            return;
        }
        foreach ($patients as $p) {
            foreach (['whatsapp', 'sms', 'email'] as $ch) {
                DB::table('notification_consents')->insert([
                    'recipient_type' => 'App\\Models\\Patient', 'recipient_id' => $p->id,
                    'channel' => $ch, 'category' => 'transactional', 'opted_in' => rand(0, 10) > 1 ? 1 : 0,
                    'source' => 'booking_form', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    private function loyaltyPoints($now, $patients, $admin): void
    {
        if (! $this->fresh('loyalty_points')) {
            return;
        }
        foreach ($patients->take(12) as $p) {
            DB::table('loyalty_points')->insert([
                'patient_id' => $p->id, 'points' => rand(50, 300), 'type' => 'earn',
                'description' => 'نقاط مكتسبة من زيارة', 'reference_type' => 'visit',
                'expires_at' => $now->copy()->addYear(), 'admin_user_id' => $admin?->id,
                'created_at' => $now->copy()->subDays(rand(5, 60)), 'updated_at' => $now,
            ]);
            if (rand(0, 1)) {
                DB::table('loyalty_points')->insert([
                    'patient_id' => $p->id, 'points' => -rand(20, 80), 'type' => 'redeem',
                    'description' => 'استبدال نقاط مقابل خصم', 'admin_user_id' => $admin?->id,
                    'created_at' => $now->copy()->subDays(rand(1, 4)), 'updated_at' => $now,
                ]);
            }
        }
    }

    private function patientReferrals($now, $patients): void
    {
        if (! $this->fresh('patient_referrals')) {
            return;
        }
        $arr = $patients->values();
        for ($i = 0; $i + 1 < $arr->count() && $i < 6; $i += 2) {
            DB::table('patient_referrals')->insert([
                'referrer_patient_id' => $arr[$i]->id, 'referred_patient_id' => $arr[$i + 1]->id,
                'code' => 'REF-'.strtoupper(Str::random(6)), 'discount_amount' => 50,
                // redeemed_at is NOT NULL (migration uses ->useCurrent()), so always
                // provide a real timestamp — never null.
                'discount_currency' => 'EGP', 'redeemed_at' => $now->copy()->subDays(rand(1, 20)),
                'created_at' => $now->copy()->subDays(rand(5, 30)), 'updated_at' => $now,
            ]);
        }
    }

    private function doctorPayouts($now, $doctors, $admin): void
    {
        if (! $this->fresh('doctor_payouts')) {
            return;
        }
        $start = $now->copy()->startOfMonth()->subMonth();
        $end = $start->copy()->endOfMonth();
        $i = 0;
        foreach ($doctors->take(6) as $d) {
            $i++;
            $visits = DB::table('visits')->where('doctor_id', $d->id)->where('status', 'completed')->limit(5)->get();
            if ($visits->isEmpty()) {
                continue;
            }
            $totalRev = 0;
            $totalComm = 0;
            $vrows = [];
            foreach ($visits as $v) {
                $amount = (float) (DB::table('invoices')->where('visit_id', $v->id)->value('total') ?? 400);
                $rate = 40;
                $comm = round($amount * $rate / 100, 2);
                $totalRev += $amount;
                $totalComm += $comm;
                $vrows[] = ['visit_id' => $v->id, 'visit_amount' => $amount, 'commission_rate' => $rate, 'commission_amount' => $comm];
            }
            $status = $i % 3 === 0 ? 'paid' : ($i % 3 === 1 ? 'confirmed' : 'draft');
            $payoutId = DB::table('doctor_payouts')->insertGetId([
                'branch_id' => $this->branch,
                'payout_number' => 'PO-'.$start->format('Ym').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'doctor_id' => $d->id, 'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
                'total_visits' => $visits->count(), 'total_revenue' => $totalRev, 'total_commission' => $totalComm,
                'deductions' => 0, 'net_amount' => $totalComm, 'status' => $status,
                'confirmed_at' => $status !== 'draft' ? $now : null, 'confirmed_by' => $status !== 'draft' ? $admin?->id : null,
                'paid_at' => $status === 'paid' ? $now : null, 'paid_by' => $status === 'paid' ? $admin?->id : null,
                'payment_method' => $status === 'paid' ? 'cash' : null,
                'created_by' => $admin?->id, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($vrows as $vr) {
                DB::table('doctor_payout_visits')->insert(array_merge($vr, [
                    'branch_id' => $this->branch, 'doctor_payout_id' => $payoutId,
                    'created_at' => $now, 'updated_at' => $now,
                ]));
            }
        }
    }

    private function dentalClinical($now, $patients, $doctors): void
    {
        $dentalDoc = $doctors->firstWhere('module', 'dental') ?? $doctors->first();

        if ($this->fresh('dental_followup_rules')) {
            $rules = [
                ['extraction', 'خلع', 'بعد الخلع', 3],
                ['filling', 'حشو', 'متابعة الحشو', 30],
                ['root_canal', 'علاج عصب', 'متابعة علاج العصب', 14],
                ['implant', 'زراعة', 'متابعة الزراعة', 90],
                ['scaling', 'تنظيف', 'تنظيف دوري', 180],
            ];
            foreach ($rules as $i => $r) {
                DB::table('dental_followup_rules')->insert([
                    'treatment_type' => $r[0], 'label_ar' => $r[1], 'label_en' => ucfirst($r[0]),
                    'followup_days' => $r[3], 'auto_create_booking' => 0, 'sms_patient' => 1,
                    'notify_doctor' => 1, 'notify_secretary' => 1, 'is_active' => 1, 'sort_order' => $i,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if ($this->fresh('dental_treatment_plan_templates')) {
            $tpls = [
                ['خطة تجميل الابتسامة', 'Smile Makeover', 'cosmetic', 8500, 6],
                ['خطة علاج اللثة', 'Periodontal Therapy', 'periodontal', 3200, 4],
                ['خطة الزراعة الكاملة', 'Full Implant', 'implant', 22000, 8],
            ];
            foreach ($tpls as $i => $t) {
                DB::table('dental_treatment_plan_templates')->insert([
                    'name_ar' => $t[0], 'name_en' => $t[1], 'category' => $t[2],
                    'treatments' => json_encode([['name' => $t[1], 'sessions' => $t[4]]]),
                    'estimated_cost' => $t[3], 'estimated_sessions' => $t[4], 'priority' => 'normal',
                    'is_active' => 1, 'sort_order' => $i, 'usage_count' => rand(0, 5),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if ($this->fresh('dental_treatment_plans')) {
            $statuses = ['draft', 'active', 'completed'];
            foreach ($patients->slice(5, 4) as $i => $p) {
                $planId = DB::table('dental_treatment_plans')->insertGetId([
                    'branch_id' => $this->branch, 'patient_id' => $p->id, 'doctor_id' => $dentalDoc->id,
                    'title_ar' => 'خطة علاج أسنان شاملة', 'title_en' => 'Comprehensive Dental Plan',
                    'estimated_cost' => rand(3000, 15000), 'estimated_sessions' => rand(3, 8),
                    'completed_sessions' => rand(0, 3), 'priority' => 'normal',
                    'status' => $statuses[$i % 3], 'start_date' => $now->copy()->subDays(rand(5, 40))->toDateString(),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                if ($this->fresh('treatment_plan_consents') || true) {
                    DB::table('treatment_plan_consents')->insert([
                        'branch_id' => $this->branch, 'dental_treatment_plan_id' => $planId, 'patient_id' => $p->id,
                        'status' => $i === 0 ? 'signed' : 'pending',
                        'sent_at' => $now->copy()->subDays(rand(1, 5)),
                        'signed_at' => $i === 0 ? $now->copy()->subDays(1) : null,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }

        if ($this->fresh('periodontal_charts')) {
            foreach ($patients->slice(5, 3) as $p) {
                foreach ([11, 16, 21, 26, 36, 46] as $tooth) {
                    DB::table('periodontal_charts')->insert([
                        'patient_id' => $p->id, 'doctor_id' => $dentalDoc->id,
                        'exam_date' => $now->copy()->subDays(rand(3, 20))->toDateString(), 'tooth_number' => $tooth,
                        'probing_depths' => json_encode([rand(1, 4), rand(1, 4), rand(1, 4), rand(1, 4), rand(1, 4), rand(1, 4)]),
                        'recession' => json_encode([rand(0, 2), rand(0, 2), rand(0, 2)]),
                        'bleeding_on_probing' => rand(0, 1), 'mobility' => rand(0, 2),
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function dermaPlans($now, $patients, $doctors): void
    {
        if (! $this->fresh('derma_treatment_plans')) {
            return;
        }
        $dermaDoc = $doctors->firstWhere('module', null) ?? $doctors->first();
        $skin = DB::table('skin_conditions')->value('id');
        $types = ['laser', 'peel', 'phototherapy', 'injection', 'cryotherapy'];
        $statuses = ['planned', 'in_progress', 'completed'];
        foreach ($patients->take(4) as $i => $p) {
            DB::table('derma_treatment_plans')->insert([
                'branch_id' => $this->branch, 'patient_id' => $p->id, 'doctor_id' => $dermaDoc->id,
                'skin_condition_id' => $skin, 'title_ar' => 'خطة علاج جلدي', 'title_en' => 'Derma Plan',
                'session_type' => $types[$i % count($types)], 'estimated_sessions' => rand(4, 10),
                'completed_sessions' => rand(0, 4), 'interval_days' => 21, 'estimated_cost' => rand(2000, 9000),
                'status' => $statuses[$i % 3], 'start_date' => $now->copy()->subDays(rand(5, 30))->toDateString(),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function cosmetic($now, $patients): void
    {
        if ($this->fresh('cosmetic_consent_templates')) {
            $proc = DB::table('cosmetic_procedures')->value('id');
            $tpls = [
                ['موافقة على البوتوكس', 'Botox Consent'],
                ['موافقة على الفيلر', 'Filler Consent'],
                ['موافقة على الليزر', 'Laser Consent'],
            ];
            foreach ($tpls as $t) {
                DB::table('cosmetic_consent_templates')->insert([
                    'procedure_id' => $proc, 'title_ar' => $t[0], 'title_en' => $t[1],
                    'body_ar' => 'أقر بأنني اطلعت على المخاطر والفوائد ووافقت على إجراء '.$t[0].'.',
                    'requires_signature' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if ($this->fresh('cosmetic_package_purchases')) {
            $pkgs = DB::table('cosmetic_packages')->get();
            if ($pkgs->isNotEmpty()) {
                $statuses = ['active', 'active', 'completed', 'expired'];
                foreach ($patients->take(6) as $i => $p) {
                    $pkg = $pkgs[$i % $pkgs->count()];
                    $total = (int) ($pkg->total_sessions ?? 6);
                    DB::table('cosmetic_package_purchases')->insert([
                        'branch_id' => $this->branch, 'patient_id' => $p->id, 'package_id' => $pkg->id,
                        'total_sessions' => $total, 'sessions_used' => rand(0, $total),
                        'amount' => $pkg->total_price ?? rand(3000, 12000),
                        'purchased_at' => $now->copy()->subDays(rand(10, 90)),
                        'expires_at' => $now->copy()->addDays(rand(30, 300)),
                        'status' => $statuses[$i % count($statuses)],
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function packageBundleBookings($now, $patients, $doctors, $admin): void
    {
        if (! $this->fresh('package_bundle_bookings')) {
            return;
        }
        $bundles = DB::table('package_bundles')->get();
        if ($bundles->isEmpty()) {
            return;
        }
        $doc = $doctors->first();
        $statuses = ['pending', 'in_progress', 'completed'];
        foreach ($patients->take(5) as $i => $p) {
            $bundle = $bundles[$i % $bundles->count()];
            $services = DB::table('package_bundle_services')->where('package_bundle_id', $bundle->id)->get();
            $total = (float) $services->sum('bundle_price') ?: 5000;
            $paid = $i % 2 === 0 ? $total : round($total / 2, 2);
            $bookingId = DB::table('package_bundle_bookings')->insertGetId([
                'branch_id' => $this->branch,
                'booking_number' => 'PKG-'.$now->format('Ym').'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'package_bundle_id' => $bundle->id, 'patient_id' => $p->id, 'receptionist_id' => $admin?->id,
                'status' => $statuses[$i % 3], 'total_price' => $total, 'total_paid' => $paid,
                'balance_due' => $total - $paid, 'source' => 'admin',
                'started_at' => $now->copy()->subDays(rand(3, 30)),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $sess = 1;
            foreach ($services as $svc) {
                $pbsId = DB::table('package_bundle_booking_services')->insertGetId([
                    'branch_id' => $this->branch, 'package_bundle_booking_id' => $bookingId,
                    'package_bundle_service_id' => $svc->id, 'service_id' => $svc->service_id,
                    'doctor_id' => $doc->id, 'sessions_count' => $svc->sessions_count ?? 1,
                    'completed_sessions' => rand(0, (int) ($svc->sessions_count ?? 1)),
                    'bundle_price' => $svc->bundle_price ?? 1000, 'status' => 'in_progress',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('package_bundle_booking_appointments')->insert([
                    'branch_id' => $this->branch, 'package_bundle_booking_id' => $bookingId,
                    'package_bundle_booking_service_id' => $pbsId, 'doctor_id' => $doc->id,
                    'appointment_date' => $now->copy()->addDays($sess)->toDateString(),
                    'start_time' => sprintf('%02d:00', 9 + ($sess % 7)), 'end_time' => sprintf('%02d:30', 9 + ($sess % 7)),
                    'session_number' => 1, 'status' => 'scheduled',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $sess++;
            }
        }
    }

    private function obgynProfiles($now, $patients, $doctors): void
    {
        if (! $this->fresh('obgyn_profiles')) {
            return;
        }
        $doc = $doctors->firstWhere('module', 'obgyn') ?? $doctors->first();
        $females = $patients->where('gender', 'female')->take(6);
        $groups = ['A+', 'O+', 'B+', 'AB+', 'O-', 'A-'];
        foreach ($females as $i => $p) {
            DB::table('obgyn_profiles')->insert([
                'patient_id' => $p->id, 'doctor_id' => $doc->id,
                'menarche_age' => rand(11, 15), 'gravida' => rand(0, 4), 'para' => rand(0, 3),
                'abortus' => rand(0, 1), 'living_children' => rand(0, 3),
                'blood_group' => $groups[$i % count($groups)], 'rh_factor' => $i % 5 === 0 ? 'negative' : 'positive',
                'lmp' => $now->copy()->subDays(rand(10, 200))->toDateString(), 'cycle_length_days' => rand(26, 32),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function bookingConsents($now, $admin): void
    {
        if (! $this->fresh('booking_consents')) {
            return;
        }
        $bookings = DB::table('bookings')->limit(5)->get();
        foreach ($bookings as $b) {
            DB::table('booking_consents')->insert([
                'branch_id' => $this->branch, 'booking_id' => $b->id,
                'file_path' => 'consents/demo-consent-'.$b->id.'.pdf',
                'original_name' => 'إقرار-موافقة.pdf', 'uploaded_by' => $admin?->id,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function serviceDetails($now, $services, $supplies): void
    {
        if ($this->fresh('service_faqs')) {
            foreach ($services as $s) {
                for ($k = 1; $k <= 2; $k++) {
                    DB::table('service_faqs')->insert([
                        'service_id' => $s->id,
                        'question_ar' => "سؤال شائع رقم $k حول الخدمة؟", 'question_en' => "FAQ $k about this service?",
                        'answer_ar' => 'هذه إجابة توضيحية تفصيلية على السؤال الشائع المتعلق بالخدمة.',
                        'answer_en' => 'This is a detailed explanatory answer to the frequently asked question.',
                        'display_order' => $k, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
        if ($this->fresh('service_gallery')) {
            foreach ($services as $s) {
                for ($k = 1; $k <= 2; $k++) {
                    DB::table('service_gallery')->insert([
                        'service_id' => $s->id, 'image_path' => "services/gallery/demo-{$s->id}-{$k}.jpg",
                        'caption_ar' => 'صورة توضيحية للخدمة', 'caption_en' => 'Service illustration',
                        'display_order' => $k, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
        if ($this->fresh('service_supplies') && $supplies->isNotEmpty()) {
            foreach ($services->take(8) as $s) {
                foreach ($supplies->random(min(3, $supplies->count())) as $sup) {
                    DB::table('service_supplies')->insert([
                        'service_id' => $s->id, 'supply_id' => $sup->id,
                        'quantity_per_session' => rand(1, 4), 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function postTags($now): void
    {
        if (! $this->fresh('post_tag')) {
            return;
        }
        $posts = DB::table('posts')->pluck('id');
        $tags = DB::table('tags')->pluck('id');
        if ($posts->isEmpty() || $tags->isEmpty()) {
            return;
        }
        foreach ($posts as $pid) {
            foreach ($tags->random(min(3, $tags->count())) as $tid) {
                DB::table('post_tag')->insert(['post_id' => $pid, 'tag_id' => $tid]);
            }
        }
    }

    private function messages($now, $staff): void
    {
        if (! $this->fresh('messages') || $staff->count() < 2) {
            return;
        }
        $bodies = [
            'هل يمكنك مراجعة جدول مواعيد الغد؟',
            'تم تأكيد حجز المريض في عيادة الأسنان.',
            'نحتاج إعادة طلب بعض المستلزمات الطبية.',
            'تقرير الإيرادات الأسبوعي جاهز للمراجعة.',
            'شكراً، تمت المتابعة مع المريض.',
        ];
        $a = $staff[0];
        $b = $staff[1];
        foreach ($bodies as $i => $body) {
            $sender = $i % 2 === 0 ? $a : $b;
            $receiver = $i % 2 === 0 ? $b : $a;
            DB::table('messages')->insert([
                'sender_id' => $sender->id, 'receiver_id' => $receiver->id, 'body' => $body,
                'read_at' => $i < 3 ? $now : null,
                'created_at' => $now->copy()->subHours(rand(1, 48)), 'updated_at' => $now,
            ]);
        }
    }

    private function branchDoctor($now, $doctors): void
    {
        if (! $this->fresh('branch_doctor')) {
            return;
        }
        foreach ($doctors as $d) {
            DB::table('branch_doctor')->insert([
                'branch_id' => $this->branch, 'doctor_id' => $d->id,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    /**
     * Guarantees the dashboard's headline KPIs (revenue / net income / expenses)
     * are non-zero on ANY demo date. The bulk demo data is dated across the past
     * ~1-3 months, so near a month boundary the *current-month* figures would read
     * ~0. This seeds paid invoices + payments + expenses dated within the current
     * month-to-date. Idempotent: keyed on a DEMO-MTD reference so it never piles on.
     */
    private function currentMonthActivity($now, $patients, $admin): void
    {
        $already = DB::table('payments')->where('reference_number', 'like', 'DEMO-MTD-%')->exists();
        if ($already || $patients->isEmpty()) {
            return;
        }

        $method = DB::table('payment_methods')->value('id');
        $cat = DB::table('expense_categories')->value('id');
        // Span only within the current month: today back to the 1st (clamped to 20d).
        $span = max(0, min(20, (int) $now->day - 1));
        $pats = $patients->values();

        for ($i = 0; $i < 18; $i++) {
            $p = $pats[$i % $pats->count()];
            $date = $now->copy()->subDays(rand(0, $span));
            $amount = (float) (rand(6, 40) * 100);
            $invId = DB::table('invoices')->insertGetId([
                'branch_id' => $this->branch,
                'invoice_number' => 'INV-MTD-'.$now->format('Ym').'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'invoice_date' => $date->toDateString(), 'patient_id' => $p->id,
                'subtotal' => $amount, 'discount_amount' => 0, 'tax_amount' => 0,
                'total' => $amount, 'paid_amount' => $amount, 'status' => 'paid',
                'module' => ['derma', 'dental', 'pediatric', 'obgyn'][$i % 4],
                'created_by' => $admin?->id, 'created_at' => $date, 'updated_at' => $date,
            ]);
            DB::table('payments')->insert([
                'branch_id' => $this->branch, 'invoice_id' => $invId, 'patient_id' => $p->id,
                'payment_method_id' => $method, 'amount' => $amount, 'payment_date' => $date->toDateString(),
                'reference_number' => 'DEMO-MTD-'.($i + 1), 'received_by' => $admin?->id,
                'created_at' => $date, 'updated_at' => $date,
            ]);
        }

        for ($i = 0; $i < 8; $i++) {
            $date = $now->copy()->subDays(rand(0, $span));
            DB::table('expenses')->insert([
                'branch_id' => $this->branch, 'expense_category_id' => $cat,
                'amount' => (float) (rand(3, 20) * 100), 'expense_date' => $date->toDateString(),
                'description' => 'مصروف تشغيلي تجريبي', 'created_by' => $admin?->id,
                'created_at' => $date, 'updated_at' => $date,
            ]);
        }
    }

    private function accessLogs($now, $patients, $admin): void
    {
        if (! $this->fresh('medical_data_access_logs') || ! $admin) {
            return;
        }
        $types = ['view', 'export', 'update'];
        $cats = ['clinical_notes', 'prescriptions', 'lab_results', 'billing'];
        foreach ($patients->take(10) as $p) {
            DB::table('medical_data_access_logs')->insert([
                'user_id' => $admin->id, 'patient_id' => $p->id,
                'access_type' => $types[array_rand($types)], 'data_category' => $cats[array_rand($cats)],
                'panel' => 'admin', 'ip_address' => '127.0.0.1', 'reason' => 'عرض ملف المريض',
                'created_at' => $now->copy()->subDays(rand(0, 15)), 'updated_at' => $now,
            ]);
        }
    }
}
