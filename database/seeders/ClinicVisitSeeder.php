<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClinicVisitSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $doctors = Doctor::all();
        $services = Service::whereNotNull('price')->get();
        $receptionist = User::first();

        if ($patients->isEmpty() || $doctors->isEmpty() || $services->isEmpty()) {
            return;
        }

        // Idempotent: this seeder generates standalone demo visits (no booking_id).
        // DemoDataSeeder's visits all carry a booking_id, so this guard skips only a
        // previous ClinicVisitSeeder batch — the first fill still proceeds, but a
        // re-run (second SEED_DEMO_DATA pass) will not pile on 50 more visits.
        if (Visit::whereNull('booking_id')->exists()) {
            return;
        }

        $statuses = ['waiting', 'in_progress', 'completed', 'cancelled'];
        $visits = [];

        // Generate 50 visits over the past 30 days
        for ($i = 0; $i < 50; $i++) {
            $patient = $patients->random();
            $doctor = $doctors->random();
            $isConsultation = $i % 5 === 0; // 20% are consultations
            $service = $isConsultation ? null : $services->random();
            $daysAgo = rand(0, 30);
            $visitDate = Carbon::today()->subDays($daysAgo);

            // Weight towards completed status
            $statusIndex = $daysAgo === 0
                ? rand(0, 2) // today: can be waiting, in_progress, or completed
                : ($daysAgo <= 2 ? rand(2, 3) : rand(2, 3)); // past: completed or cancelled

            $status = $statuses[$statusIndex];

            $startedAt = null;
            $completedAt = null;
            if (in_array($status, ['in_progress', 'completed'])) {
                $startedAt = $visitDate->copy()->setHour(rand(9, 18))->setMinute(rand(0, 59));
            }
            if ($status === 'completed') {
                $completedAt = $startedAt?->copy()->addMinutes(rand(15, 60));
            }

            $visits[] = [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'receptionist_id' => $receptionist?->id,
                'visit_type' => $isConsultation ? 'consultation' : 'session',
                'service_id' => $service?->id,
                'status' => $status,
                'visit_date' => $visitDate->toDateString(),
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'diagnosis' => $status === 'completed' ? $this->randomDiagnosis() : null,
                'doctor_notes' => $status === 'completed' ? $this->randomNote() : null,
                'created_at' => $visitDate,
                'updated_at' => $completedAt ?? $startedAt ?? $visitDate,
            ];
        }

        foreach ($visits as $visit) {
            Visit::create($visit);
        }
    }

    private function randomDiagnosis(): string
    {
        $diagnoses = [
            'Acne vulgaris - mild',
            'Acne vulgaris - moderate',
            'Melasma - bilateral',
            'Alopecia areata',
            'Contact dermatitis',
            'Eczema - mild',
            'Psoriasis - plaque type',
            'Hyperpigmentation - post-inflammatory',
            'Rosacea',
            'Folliculitis',
            'Seborrheic dermatitis',
            'Vitiligo - localized',
            'Fungal infection - tinea corporis',
            'Warts - common',
            'Aging skin - wrinkles and laxity',
        ];

        return $diagnoses[array_rand($diagnoses)];
    }

    private function randomNote(): string
    {
        $notes = [
            'Patient responded well to treatment. Follow-up in 2 weeks.',
            'Prescribed topical treatment. Re-evaluate in 1 month.',
            'Laser session completed successfully. Next session in 4 weeks.',
            'Mild erythema post-procedure, expected to resolve in 24-48 hours.',
            'Treatment plan discussed with patient. Consent obtained.',
            'Good progress noted. Continue current regimen.',
            'Referred for blood tests before starting systemic treatment.',
            'Post-procedure care instructions provided.',
        ];

        return $notes[array_rand($notes)];
    }
}
