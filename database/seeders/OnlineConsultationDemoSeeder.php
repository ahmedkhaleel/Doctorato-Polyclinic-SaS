<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Seeder;

/**
 * Enables online consultation on every active doctor with demo
 * defaults (fee / session length / bilingual bio), so the Telemedicine
 * module has populated data out of the box.
 *
 * Usage:
 *   php artisan db:seed --class=OnlineConsultationDemoSeeder
 *
 * Idempotent — re-running updates the same rows without duplicating.
 */
class OnlineConsultationDemoSeeder extends Seeder
{
    public function run(): void
    {
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

        $doctors = Doctor::where('status', 'active')->get();

        foreach ($doctors as $doctor) {
            $isConsultant = $doctor->doctor_type === 'consultant';
            $bio = $bios[$isConsultant ? 'consultant' : 'specialist'];

            $doctor->update([
                'online_consultation_enabled'      => true,
                'online_consultation_fee'          => $isConsultant ? 350.00 : 250.00,
                'online_session_duration_minutes'  => 30,
                'online_consultation_bio_ar'       => $bio['ar'],
                'online_consultation_bio_en'       => $bio['en'],
            ]);
        }

        // Flip existing in-person schedules to "both" so the same weekly
        // windows serve both clinic and online bookings.
        $scheduleUpdates = DoctorSchedule::whereIn('doctor_id', $doctors->pluck('id'))
            ->where('is_active', true)
            ->where('mode', 'in_person')
            ->update(['mode' => 'both']);

        $count = $doctors->count();
        $this->command->info("✓ Enabled online consultation on {$count} doctor(s) + converted {$scheduleUpdates} schedule(s) to dual mode.");
    }
}
