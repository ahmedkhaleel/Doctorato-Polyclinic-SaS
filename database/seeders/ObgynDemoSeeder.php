<?php

namespace Database\Seeders;

use App\Models\AntenatalVisit;
use App\Models\ContraceptionRecord;
use App\Models\Doctor;
use App\Models\ObgynLabTest;
use App\Models\ObstetricUltrasound;
use App\Models\PapSmearScreening;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Services\ObgynBillingService;
use App\Services\ObstetricCalculatorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo data for the OB/GYN module: an OB/GYN doctor, several female patients
 * with pregnancies at different gestational ages (one delivered), antenatal
 * visits, ultrasounds, labs, plus pap smears and contraception records.
 * Billed through ObgynBillingService so module-revenue reports show data.
 *
 * Safe to re-run: keyed on patient phone; skips a patient who already has a
 * pregnancy.
 */
class ObgynDemoSeeder extends Seeder
{
    public function run(): void
    {
        $calc = app(ObstetricCalculatorService::class);
        $billing = app(ObgynBillingService::class);

        $doctor = Doctor::firstOrCreate(
            ['name_en' => 'Dr. Sara Hassan'],
            [
                'name_ar' => 'د. سارة حسن',
                'specialization_ar' => 'النساء والتوليد',
                'specialization_en' => 'Obstetrics & Gynecology',
                'doctor_type' => 'consultant',
                'status' => 'active',
                'module' => 'obgyn',
                'obgyn_consultation_fee' => 250,
            ]
        );

        // [name, phone, gestational weeks now] — null weeks = delivered case.
        $cases = [
            ['أمل محمد', '01010000101', 10],
            ['هدى علي', '01010000102', 22],
            ['ريم سمير', '01010000103', 33],
            ['ليلى خالد', '01010000104', null], // delivered
        ];

        foreach ($cases as [$name, $phone, $weeks]) {
            // file_number is set explicitly: DatabaseSeeder runs WithoutModelEvents,
            // so Patient::booted()'s auto-generation hook does not fire here.
            $patient = Patient::firstOrCreate(
                ['phone' => $phone],
                ['full_name' => $name, 'gender' => 'female', 'file_number' => Patient::generateFileNumber()]
            );
            $patient->forceFill(['gender' => 'female', 'is_active' => true])->save();

            if (Pregnancy::where('patient_id', $patient->id)->exists()) {
                continue;
            }

            if ($weeks === null) {
                $this->seedDelivered($patient, $doctor, $calc, $billing);

                continue;
            }

            $this->seedActive($patient, $doctor, $calc, $billing, $weeks);
        }

        // A standalone gynaecology patient: pap smear + contraception.
        $gyn = Patient::firstOrCreate(['phone' => '01010000105'], ['full_name' => 'منى فؤاد', 'gender' => 'female', 'file_number' => Patient::generateFileNumber()]);
        $gyn->forceFill(['gender' => 'female', 'is_active' => true])->save();

        PapSmearScreening::firstOrCreate(
            ['patient_id' => $gyn->id, 'test_date' => Carbon::now()->subMonths(2)->toDateString()],
            ['doctor_id' => $doctor->id, 'result' => 'normal', 'hpv_status' => 'negative', 'next_due_date' => Carbon::now()->addYears(3)->toDateString()]
        );
        ContraceptionRecord::firstOrCreate(
            ['patient_id' => $gyn->id, 'method' => 'IUD'],
            ['doctor_id' => $doctor->id, 'start_date' => Carbon::now()->subMonths(2)->toDateString(), 'status' => 'active', 'follow_up_date' => Carbon::now()->addMonths(10)->toDateString()]
        );
    }

    private function seedActive(Patient $patient, Doctor $doctor, ObstetricCalculatorService $calc, ObgynBillingService $billing, int $weeks): void
    {
        $lmp = Carbon::now()->subWeeks($weeks)->toDateString();

        $pregnancy = Pregnancy::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'lmp' => $lmp,
            'edd' => $calc->eddFromLmp($lmp)->toDateString(),
            'edd_source' => 'lmp',
            'gravida' => 2,
            'para' => 1,
            'blood_group' => 'O',
            'rh_factor' => 'positive',
            'is_high_risk' => $weeks >= 33,
            'status' => Pregnancy::STATUS_ACTIVE,
        ]);

        // A couple of past antenatal visits.
        foreach ([$weeks - 8, $weeks - 4, $weeks] as $w) {
            if ($w < 6) {
                continue;
            }
            $date = Carbon::now()->subWeeks($weeks - $w);
            $anc = AntenatalVisit::create([
                'pregnancy_id' => $pregnancy->id,
                'doctor_id' => $doctor->id,
                'visit_date' => $date->toDateString(),
                'gestational_age_weeks' => $w,
                'weight_kg' => 62 + ($w * 0.3),
                'bp_systolic' => 115,
                'bp_diastolic' => 75,
                'fundal_height_cm' => $calc->expectedFundalHeight($w),
                'fetal_heart_rate' => 145,
                'presentation' => $w >= 32 ? 'cephalic' : null,
                'next_visit_date' => $calc->nextAncDate($lmp, $date)?->toDateString(),
            ]);
            $billing->billAntenatalVisit($anc);
        }

        // A dating/growth ultrasound.
        $scan = ObstetricUltrasound::create([
            'pregnancy_id' => $pregnancy->id,
            'doctor_id' => $doctor->id,
            'scan_date' => Carbon::now()->subWeeks(2)->toDateString(),
            'scan_type' => $weeks >= 28 ? 'growth' : 'dating',
            'gestational_age_weeks' => $weeks - 2,
            'efw_grams' => $weeks >= 28 ? $calc->efwAssessment($weeks - 2, null)['expected'] : null,
            'fetal_count' => 1,
            'fetal_heart' => true,
            'presentation' => $weeks >= 32 ? 'cephalic' : null,
            'findings' => 'Normal study.',
        ]);
        $billing->billUltrasound($scan);

        // A routine lab.
        ObgynLabTest::create([
            'patient_id' => $patient->id,
            'pregnancy_id' => $pregnancy->id,
            'doctor_id' => $doctor->id,
            'test_type' => 'CBC (Hemoglobin)',
            'value' => '11.8',
            'unit' => 'g/dL',
            'reference_range' => '11-15',
            'result_date' => Carbon::now()->subWeeks(3)->toDateString(),
            'is_abnormal' => false,
        ]);
    }

    private function seedDelivered(Patient $patient, Doctor $doctor, ObstetricCalculatorService $calc, ObgynBillingService $billing): void
    {
        $lmp = Carbon::now()->subWeeks(43)->toDateString(); // delivered ~3 weeks ago
        $pregnancy = Pregnancy::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'lmp' => $lmp,
            'edd' => $calc->eddFromLmp($lmp)->toDateString(),
            'edd_source' => 'lmp',
            'gravida' => 1,
            'para' => 0,
            'status' => Pregnancy::STATUS_DELIVERED,
        ]);

        $delivery = $pregnancy->delivery()->create([
            'doctor_id' => $doctor->id,
            'delivery_date' => Carbon::now()->subWeeks(3)->toDateString(),
            'delivery_mode' => 'nvd',
            'outcome' => 'live',
            'gestational_age_at_delivery' => 40,
            'baby_weight_grams' => 3250,
            'baby_sex' => 'female',
            'apgar_1' => 8,
            'apgar_5' => 10,
        ]);
        $billing->billDelivery($delivery);
    }
}
