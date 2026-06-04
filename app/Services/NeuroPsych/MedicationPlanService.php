<?php

namespace App\Services\NeuroPsych;

use App\Models\ControlledSubstanceEntry;
use App\Models\MedicationMonitoring;
use App\Models\MedicationPlan;
use Illuminate\Support\Carbon;

/**
 * NP4 — creates a medication plan and, based on the drug class, schedules the
 * required safety monitoring AND mirrors controlled drugs into the audited
 * register. Monitoring cadence reflects standard practice:
 *   clozapine → ANC weekly · lithium → level in 5 days · antipsychotic →
 *   metabolic panel quarterly.
 */
class MedicationPlanService
{
    /** drug_class → [[type, daysUntilDue], …] */
    private const SCHEDULE = [
        'clozapine' => [['clozapine_anc', 7]],
        'lithium' => [['lithium_level', 5]],
        'antipsychotic' => [['metabolic', 90]],
    ];

    public function createPlan(array $data): MedicationPlan
    {
        $plan = MedicationPlan::create($data);

        $this->scheduleMonitoring($plan);

        if ($plan->is_controlled) {
            ControlledSubstanceEntry::create([
                'patient_id' => $plan->patient_id,
                'medication_plan_id' => $plan->id,
                'doctor_id' => $plan->doctor_id,
                'drug' => $plan->drug,
                'quantity' => $plan->dose,
                'prescribed_at' => now(),
            ]);
        }

        return $plan;
    }

    /** Create the due monitoring rows for a plan's drug class (idempotent-ish). */
    public function scheduleMonitoring(MedicationPlan $plan): void
    {
        $class = strtolower((string) $plan->drug_class);
        foreach (self::SCHEDULE[$class] ?? [] as [$type, $days]) {
            $exists = MedicationMonitoring::where('medication_plan_id', $plan->id)
                ->where('type', $type)
                ->where('status', 'due')
                ->exists();
            if ($exists) {
                continue;
            }
            MedicationMonitoring::create([
                'medication_plan_id' => $plan->id,
                'patient_id' => $plan->patient_id,
                'type' => $type,
                'due_at' => Carbon::today()->addDays($days),
                'status' => 'due',
            ]);
        }
    }

    /** Record a monitoring result and, for recurring tasks, schedule the next one. */
    public function recordResult(MedicationMonitoring $m, string $result, ?string $notes = null): void
    {
        $m->update(['result' => $result, 'result_at' => now()->toDateString(), 'status' => 'done', 'notes' => $notes]);

        // Recurring cadence: clozapine ANC repeats weekly.
        if ($m->type === 'clozapine_anc') {
            MedicationMonitoring::create([
                'medication_plan_id' => $m->medication_plan_id,
                'patient_id' => $m->patient_id,
                'type' => 'clozapine_anc',
                'due_at' => Carbon::today()->addDays(7),
                'status' => 'due',
            ]);
        }
    }
}
