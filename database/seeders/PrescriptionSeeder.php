<?php

namespace Database\Seeders;

use App\Models\Medication;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $completedVisits = Visit::where('status', 'completed')->take(15)->get();
        $medications = Medication::all();

        if ($completedVisits->isEmpty() || $medications->isEmpty()) {
            return;
        }

        foreach ($completedVisits as $visit) {
            $prescription = Prescription::create([
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'doctor_id' => $visit->doctor_id,
                'diagnosis' => $visit->diagnosis ?? 'General dermatological consultation',
                'notes' => 'Follow up in 2 weeks. Apply sunscreen regularly.',
            ]);

            // Add 2-4 medication items per prescription
            $itemCount = rand(2, 4);
            $selectedMeds = $medications->random($itemCount);

            foreach ($selectedMeds as $sortOrder => $med) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medication_name' => $med->name,
                    'dosage' => $med->default_dosage,
                    'frequency' => $med->default_frequency,
                    'duration' => $med->default_duration,
                    'instructions' => $this->randomInstruction(),
                    'sort_order' => $sortOrder + 1,
                ]);
            }
        }
    }

    private function randomInstruction(): ?string
    {
        $instructions = [
            'Apply after washing face with gentle cleanser',
            'Avoid sun exposure after application',
            'Take with food',
            'Apply on affected areas only',
            'Avoid contact with eyes',
            'Do not mix with other topical treatments',
            'Use moisturizer 15 minutes before application',
            null,
            null,
        ];

        return $instructions[array_rand($instructions)];
    }
}
