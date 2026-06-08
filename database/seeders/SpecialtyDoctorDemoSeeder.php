<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * SpecialtyDoctorDemoSeeder — one demo DOCTOR LOGIN per medical specialty, each
 * owning a complete, self-contained data set so logging in shows a populated
 * portal: patients (full bio), bookings (past + upcoming appointments), completed
 * visits with invoices + payments + commissions, and rich specialty clinical
 * records (dental charts/treatments/plans/xrays, derma plans/sessions, pediatric
 * growth/vaccinations/allergies, obgyn pregnancies/labs, neuropsych encounters/
 * scales/meds/risk).
 *
 * Logins:  demo.<module>@doctorato.net  (module ∈ dental/derma/pediatric/obgyn/
 * psychiatry/neurology), password = env('DEMO_PASSWORD', 'DemoClinic@2026').
 *
 * On-demand only (NOT wired into the auto-deploy migrate path):
 *   php artisan db:seed --class=Database\\Seeders\\SpecialtyDoctorDemoSeeder
 *
 * Idempotent: a module is skipped if its demo doctor already has visits. Each
 * module is wrapped so one failure never aborts the others.
 */
class SpecialtyDoctorDemoSeeder extends Seeder
{
    private string $pwd;

    private int $payMethodId;

    private array $firstAr = ['أحمد', 'محمد', 'سارة', 'نورة', 'خالد', 'ليلى', 'عمر', 'منى', 'يوسف', 'دانة', 'فهد', 'ريم'];

    private array $lastAr = ['الشهري', 'القحطاني', 'الحربي', 'العتيبي', 'الزهراني', 'الدوسري', 'المطيري', 'الغامدي'];

    public function run(): void
    {
        $this->pwd = (string) env('DEMO_PASSWORD', 'DemoClinic@2026');
        $this->payMethodId = PaymentMethod::firstOrCreate(['name_en' => 'Cash'], ['name_ar' => 'نقدي', 'is_active' => true])->id;
        Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['ai.view', 'ai.doctor'], 'is_system' => true]);

        $modules = [
            ['m' => 'dental', 'ar' => 'الأسنان', 'spEn' => 'Dentistry', 'spAr' => 'طب الأسنان', 'name' => 'Sami Dental', 'fee' => 300, 'female' => false, 'type' => 'dental_consultation'],
            ['m' => 'derma', 'ar' => 'الجلدية', 'spEn' => 'Dermatology', 'spAr' => 'جلدية', 'name' => 'Lina Derma', 'fee' => 400, 'female' => false, 'type' => 'derma_consultation'],
            ['m' => 'pediatric', 'ar' => 'الأطفال', 'spEn' => 'Pediatrics', 'spAr' => 'أطفال', 'name' => 'Omar Peds', 'fee' => 250, 'female' => false, 'type' => 'pediatric_consultation'],
            ['m' => 'obgyn', 'ar' => 'النساء والتوليد', 'spEn' => 'OB/GYN', 'spAr' => 'نساء وتوليد', 'name' => 'Huda Obgyn', 'fee' => 350, 'female' => true, 'type' => 'obgyn_consultation'],
            ['m' => 'psychiatry', 'ar' => 'الطب النفسي', 'spEn' => 'Psychiatry', 'spAr' => 'طب نفسي', 'name' => 'Faisal Psych', 'fee' => 350, 'female' => false, 'type' => 'psychiatry_consultation'],
            ['m' => 'neurology', 'ar' => 'المخ والأعصاب', 'spEn' => 'Neurology', 'spAr' => 'مخ وأعصاب', 'name' => 'Maya Neuro', 'fee' => 350, 'female' => false, 'type' => 'neurology_consultation'],
            ['m' => 'physiotherapy', 'ar' => 'العلاج الطبيعي', 'spEn' => 'Physiotherapy', 'spAr' => 'علاج طبيعي', 'name' => 'Tarek Physio', 'fee' => 250, 'female' => false, 'type' => 'physiotherapy_consultation'],
        ];

        foreach ($modules as $i => $cfg) {
            try {
                DB::transaction(fn () => $this->seedModule($i, $cfg));
                $this->command?->info("  ✓ demo.{$cfg['m']}@doctorato.net seeded");
            } catch (\Throwable $e) {
                if (app()->runningUnitTests()) {
                    throw $e;
                }
                $this->command?->warn("  ✗ {$cfg['m']} skipped: ".$e->getMessage());
            }
        }
    }

    private function seedModule(int $idx, array $cfg): void
    {
        $module = $cfg['m'];

        // ── Doctor + login ───────────────────────────────────
        $user = User::withTrashed()->firstOrNew(['email' => "demo.{$module}@doctorato.net"]);
        $user->fill([
            'name' => "Dr. {$cfg['name']} / د. {$cfg['name']}",
            'password' => Hash::make($this->pwd),
            'role_id' => Role::where('name', 'doctor')->value('id'),
            'is_active' => true,
        ]);
        if (in_array('is_demo', $user->getFillable(), true) || \Illuminate\Support\Facades\Schema::hasColumn('users', 'is_demo')) {
            $user->is_demo = true;
        }
        $user->deleted_at = null;
        $user->save();

        $doctor = Doctor::firstOrNew(['user_id' => $user->id]);
        $doctor->fill([
            'name_ar' => 'د. '.$cfg['name'], 'name_en' => 'Dr. '.$cfg['name'],
            'specialization_ar' => $cfg['spAr'], 'specialization_en' => $cfg['spEn'],
            'status' => 'active', 'doctor_type' => 'consultant', 'module' => $module,
            'default_commission_percentage' => 25,
        ]);
        $doctor->user_id = $user->id;
        $doctor->save();

        // Idempotent: already populated?
        if (Visit::where('doctor_id', $doctor->id)->where('module', $module)->exists()) {
            return;
        }

        // ── Patients + chain + clinical ──────────────────────
        for ($n = 1; $n <= 5; $n++) {
            $patient = $this->makePatient($idx, $n, $cfg);
            $isPast = $n <= 3; // 3 completed visits, 2 upcoming appointments
            $date = $isPast
                ? Carbon::now()->subDays($n * 4)
                : Carbon::now()->addDays($n * 2);

            $booking = Booking::create([
                'booking_number' => Booking::generateBookingNumber(),
                'source' => 'clinic', 'module' => $module, 'booking_type' => $cfg['type'],
                'full_name' => $patient->full_name, 'phone' => $patient->phone, 'patient_id' => $patient->id,
                'doctor_id' => $doctor->id, 'preferred_date' => $date->toDateString(),
                'preferred_time' => '10:'.str_pad((string) ($n * 5), 2, '0', STR_PAD_LEFT),
                'status' => $isPast ? 'completed' : 'confirmed',
            ]);

            if ($isPast) {
                $this->completedVisitChain($doctor, $patient, $booking, $module, (float) $cfg['fee'], $date);
            }

            $this->seedClinical($module, $doctor, $patient, $isPast, $date);
        }
    }

    private function makePatient(int $idx, int $n, array $cfg): Patient
    {
        $name = $this->firstAr[($idx * 5 + $n) % count($this->firstAr)].' '.$this->lastAr[($idx + $n) % count($this->lastAr)];
        $phone = '059'.$idx.str_pad((string) (1000 + $n), 5, '0', STR_PAD_LEFT);
        $isPeds = $cfg['m'] === 'pediatric';
        $dob = $isPeds ? Carbon::now()->subYears(rand(1, 9))->subMonths(rand(0, 11)) : Carbon::now()->subYears(rand(20, 55));

        $patient = Patient::firstOrNew(['phone' => $phone]);
        $patient->fill([
            'full_name' => $name,
            'gender' => $cfg['female'] ? 'female' : ($isPeds ? (rand(0, 1) ? 'male' : 'female') : 'male'),
            'date_of_birth' => $dob->toDateString(),
            'blood_type' => ['A+', 'O+', 'B+', 'AB+'][$n % 4],
            'allergies' => $n === 1 ? ($cfg['m'] === 'pediatric' ? 'حساسية البنسلين' : 'Penicillin allergy') : null,
            'chronic_conditions' => $n === 2 ? 'ارتفاع ضغط الدم' : null,
            'nationality' => 'Saudi',
            'guardian_name' => $isPeds ? 'ولي أمر '.$name : null,
            'guardian_phone' => $isPeds ? $phone : null,
        ]);
        $patient->save();
        $patient->forceFill(['is_active' => true])->saveQuietly();

        return $patient;
    }

    private function completedVisitChain(Doctor $doctor, Patient $patient, Booking $booking, string $module, float $fee, Carbon $date): void
    {
        $visit = Visit::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'booking_id' => $booking->id,
            'module' => $module, 'visit_type' => 'consultation', 'status' => 'completed',
            'diagnosis' => 'Demo diagnosis / تشخيص تجريبي',
            'visit_date' => $date->toDateString(), 'scheduled_time' => $booking->preferred_time,
            'started_at' => $date->copy()->setTime(10, 0), 'completed_at' => $date->copy()->setTime(10, 20),
        ]);

        // Commission columns are guarded (not fillable) → set directly.
        DB::table('visits')->where('id', $visit->id)->update([
            'commission_rate' => 25, 'commission_amount' => round($fee * 0.25, 2),
        ]);

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(), 'invoice_date' => $date->toDateString(),
            'patient_id' => $patient->id, 'visit_id' => $visit->id, 'module' => $module,
            'subtotal' => $fee, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => $fee,
        ]);
        Payment::create([
            'invoice_id' => $invoice->id, 'patient_id' => $patient->id, 'payment_method_id' => $this->payMethodId,
            'amount' => $fee, 'payment_date' => $date->toDateString(), 'reference_number' => 'DEMO-'.$invoice->id,
        ]);
        if (method_exists($invoice, 'recalculateStatus')) {
            $invoice->recalculateStatus();
        }
    }

    private function seedClinical(string $module, Doctor $doctor, Patient $patient, bool $isPast, Carbon $date): void
    {
        switch ($module) {
            case 'dental':
                foreach ([[11, 'decayed'], [12, 'filled'], [36, 'crown']] as [$t, $cond]) {
                    \App\Models\DentalChart::create(['patient_id' => $patient->id, 'tooth_number' => $t, 'condition' => $cond, 'status' => 'active', 'notes' => 'Demo']);
                }
                $plan = \App\Models\DentalTreatmentPlan::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'title_ar' => 'خطة علاج الأسنان', 'title_en' => 'Dental Plan', 'description' => 'Demo plan', 'estimated_cost' => 1200, 'status' => 'in_progress', 'estimated_sessions' => 4, 'completed_sessions' => $isPast ? 2 : 0]);
                \App\Models\DentalTreatment::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'treatment_plan_id' => $plan->id, 'tooth_number' => 11, 'treatment_type' => 'filling', 'description' => 'Composite filling', 'cost' => 300, 'status' => $isPast ? 'completed' : 'planned']);
                \App\Models\DentalXray::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'type' => 'periapical', 'image_path' => 'demo/xray-placeholder.png', 'tooth_number' => 11, 'findings' => 'Demo finding', 'taken_date' => $date->toDateString()]);
                break;

            case 'derma':
                $plan = \App\Models\DermaTreatmentPlan::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'title_ar' => 'خطة جلدية', 'title_en' => 'Derma Plan', 'session_type' => 'laser', 'estimated_sessions' => 6, 'completed_sessions' => $isPast ? 2 : 0, 'interval_days' => 21, 'estimated_cost' => 2400, 'status' => 'in_progress', 'start_date' => $date->toDateString()]);
                if ($isPast) {
                    \App\Models\DermaSession::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'treatment_plan_id' => $plan->id, 'session_type' => 'laser', 'area_treated' => 'face', 'session_number' => 1, 'total_sessions' => 6, 'cost' => 400, 'completed_at' => $date->toDateString()]);
                }
                break;

            case 'pediatric':
                $ageM = max(1, (int) Carbon::parse($patient->date_of_birth)->diffInMonths(now()));
                foreach ([0, 3, 6] as $k) {
                    \App\Models\PediatricGrowthRecord::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'measurement_date' => now()->subMonths($k)->toDateString(), 'age_months' => max(1, $ageM - $k), 'weight_kg' => 10 + $k * 0.4, 'height_cm' => 80 + $k, 'weight_percentile' => 50, 'height_percentile' => 55]);
                }
                \App\Models\PediatricVaccination::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'vaccine_name' => 'MMR', 'vaccine_name_ar' => 'الحصبة والنكاف والحصبة الألمانية', 'dose_number' => 1, 'scheduled_age' => '12 شهر', 'scheduled_date' => now()->subMonths(2)->toDateString(), 'given_date' => now()->subMonths(2)->toDateString(), 'status' => 'given']);
                \App\Models\PediatricVaccination::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'vaccine_name' => 'DTaP', 'vaccine_name_ar' => 'الثلاثي', 'dose_number' => 2, 'scheduled_age' => '18 شهر', 'scheduled_date' => now()->addWeeks(2)->toDateString(), 'status' => 'scheduled']);
                break;

            case 'obgyn':
                $preg = \App\Models\Pregnancy::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'lmp' => now()->subWeeks(20)->toDateString(), 'edd' => now()->addWeeks(20)->toDateString(), 'edd_source' => 'lmp', 'gravida' => 2, 'para' => 1, 'conception_method' => 'natural', 'status' => 'active', 'is_high_risk' => false]);
                \App\Models\ObgynLabTest::create(['patient_id' => $patient->id, 'pregnancy_id' => $preg->id, 'doctor_id' => $doctor->id, 'test_type' => 'CBC', 'value' => '11.5', 'unit' => 'g/dL', 'reference_range' => '11-14', 'result_date' => $date->toDateString(), 'is_abnormal' => false]);
                break;

            case 'psychiatry':
            case 'neurology':
                $enc = \App\Models\NeuropsychEncounter::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'module' => $module, 'encounter_date' => $date->toDateString(), 'note_format' => 'soap', 'subjective' => 'Demo subjective', 'assessment' => 'Demo assessment', 'plan' => 'Demo plan', 'cost' => 350, 'completed_at' => $isPast ? $date : null]);
                $scale = $module === 'psychiatry' ? 'phq9' : 'hit6';
                \App\Models\ScaleResult::create(['patient_id' => $patient->id, 'scale_key' => $scale, 'answers' => [], 'score' => 18, 'severity' => 'moderate', 'entered_by' => 'doctor', 'neuropsych_encounter_id' => $enc->id, 'taken_at' => now()->subMonths(2)]);
                \App\Models\ScaleResult::create(['patient_id' => $patient->id, 'scale_key' => $scale, 'answers' => [], 'score' => 9, 'severity' => 'mild', 'entered_by' => 'doctor', 'neuropsych_encounter_id' => $enc->id, 'taken_at' => now()]);
                \App\Models\MedicationPlan::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'module' => $module, 'drug' => $module === 'psychiatry' ? 'Sertraline' : 'Topiramate', 'dose' => '50mg', 'frequency' => 'OD', 'started_at' => $date->toDateString(), 'is_controlled' => false]);
                if ($module === 'psychiatry') {
                    \App\Models\RiskAssessment::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'type' => 'suicide', 'tool' => 'c-ssrs', 'answers' => [], 'risk_level' => 'low', 'safety_plan' => 'Demo safety plan', 'is_active' => true, 'assessed_at' => $date]);
                }
                break;

            case 'physiotherapy':
                // Plan of care + progress.
                $plan = \App\Models\PhysioTreatmentPlan::create([
                    'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
                    'title_ar' => 'إعادة تأهيل أسفل الظهر', 'title_en' => 'Low-back rehab',
                    'goals' => [['type' => 'pain', 'baseline' => 8, 'target' => 2]], 'modalities' => ['tens', 'manual', 'exercise'],
                    'frequency' => '3x/week', 'duration_weeks' => 4, 'estimated_sessions' => 12, 'completed_sessions' => 4,
                    'status' => 'in_progress', 'start_date' => now()->subWeeks(2)->toDateString(),
                ]);

                // Assessment with ROM / MMT / pain map.
                $assessment = \App\Models\PhysioAssessment::create([
                    'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'assessment_date' => $date->toDateString(),
                    'subjective' => 'Mechanical LBP, 3 weeks', 'objective' => 'Reduced lumbar flexion', 'diagnosis' => 'Mechanical low back pain', 'completed_at' => $date,
                ]);
                foreach ([['knee', 'flexion', 100, 135], ['hip', 'flexion', 95, 120], ['lumbar', 'flexion', 40, 60]] as [$j, $mo, $a, $n]) {
                    $assessment->romMeasurements()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'joint' => $j, 'motion' => $mo, 'side' => 'right', 'arom' => $a, 'normal_ref' => $n, 'recorded_at' => $date->toDateString()]);
                }
                $assessment->strengthTests()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'muscle_group' => 'quadriceps', 'side' => 'right', 'grade' => 4, 'recorded_at' => $date->toDateString()]);
                $assessment->painPoints()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'view' => 'back', 'x' => 50, 'y' => 55, 'intensity' => 7, 'pain_type' => 'aching', 'recorded_at' => $date->toDateString()]);

                // A couple of billable sessions with improving pain.
                foreach ([[1, 7, 5], [2, 6, 3]] as [$num, $pb, $pa]) {
                    \App\Models\PhysioSession::create([
                        'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'treatment_plan_id' => $plan->id,
                        'session_number' => $num, 'session_date' => now()->subDays(10 - $num * 3)->toDateString(),
                        'modalities' => [['type' => 'tens', 'params' => '80Hz/20min']], 'attended' => true,
                        'pain_before' => $pb, 'pain_after' => $pa, 'cost' => 200, 'completed_at' => $date,
                    ]);
                }

                // HEP prescription + an adherence log.
                $ex = \App\Models\Exercise::query()->where('is_active', true)->first();
                if ($ex) {
                    $rx = \App\Models\PhysioExercisePrescription::create([
                        'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'treatment_plan_id' => $plan->id,
                        'exercise_id' => $ex->id, 'sets' => 3, 'reps' => 12, 'hold_sec' => 20, 'frequency' => 'daily',
                        'status' => 'active', 'prescribed_at' => now()->subWeek()->toDateString(),
                    ]);
                    \App\Models\HepAdherenceLog::create(['patient_id' => $patient->id, 'prescription_id' => $rx->id, 'log_date' => now()->subDay()->toDateString(), 'done' => true, 'pain_after' => 3]);
                }

                // PROMs (ODI) showing improvement across two timepoints.
                \App\Models\ScaleResult::create(['patient_id' => $patient->id, 'scale_key' => 'odi', 'answers' => array_fill(0, 10, 3), 'score' => 30, 'severity' => 'Severe disability', 'entered_by' => 'doctor', 'taken_at' => now()->subWeeks(3)]);
                \App\Models\ScaleResult::create(['patient_id' => $patient->id, 'scale_key' => 'odi', 'answers' => array_fill(0, 10, 1), 'score' => 18, 'severity' => 'Moderate disability', 'entered_by' => 'doctor', 'taken_at' => now()]);
                break;
        }
    }
}
