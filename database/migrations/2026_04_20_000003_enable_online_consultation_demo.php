<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo data: enable online consultation on all active doctors with a
 * sensible default fee + session length + bilingual bio, so the
 * Telemedicine module has something to show as soon as it's enabled.
 *
 * Idempotent — only touches rows that still have the default values
 * (online_consultation_enabled = false, no fee set). If an operator
 * has already configured a doctor from the admin UI, their settings
 * are preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctors')) {
            return;
        }

        $required = [
            'online_consultation_enabled',
            'online_consultation_fee',
            'online_session_duration_minutes',
            'online_consultation_bio_ar',
            'online_consultation_bio_en',
        ];
        foreach ($required as $col) {
            if (! Schema::hasColumn('doctors', $col)) {
                return; // schema migration hasn't run yet — bail safely
            }
        }

        $now = now();

        $bios = [
            'specialist' => [
                'ar' => 'استشارة أونلاين عبر مكالمة فيديو لتقييم الحالات الجلدية الشائعة، وصف العلاج، ومتابعة النتائج من منزلك بكل خصوصية.',
                'en' => 'Online video consultations for common dermatology concerns, prescriptions, and follow-ups from the comfort of your home.',
            ],
            'consultant' => [
                'ar' => 'استشاري يقدّم استشارات فيديو متقدّمة لتقييم الحالات المعقدة ووضع خطة علاج مخصصة — بخصوصية تامّة ومن أي مكان.',
                'en' => 'Consultant-level video sessions for complex cases and personalised treatment plans — fully private, from anywhere.',
            ],
        ];

        $doctors = DB::table('doctors')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('online_consultation_enabled', false)
                  ->orWhereNull('online_consultation_fee');
            })
            ->get();

        foreach ($doctors as $d) {
            $isConsultant = ($d->doctor_type ?? 'specialist') === 'consultant';
            $fee = $isConsultant ? 350.00 : 250.00;
            $bio = $bios[$isConsultant ? 'consultant' : 'specialist'];

            DB::table('doctors')
                ->where('id', $d->id)
                ->update([
                    'online_consultation_enabled'    => true,
                    'online_consultation_fee'        => $fee,
                    'online_session_duration_minutes' => 30,
                    'online_consultation_bio_ar'     => $bio['ar'],
                    'online_consultation_bio_en'     => $bio['en'],
                    'updated_at'                     => $now,
                ]);
        }
    }

    public function down(): void
    {
        // No-op: don't un-enable doctors on rollback
    }
};
