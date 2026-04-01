<?php

namespace App\Services;

use App\Models\DentalChart;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\User;
use App\Notifications\DentalTreatmentCompletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Services\DentalSmartNotificationService;

class DentalTreatmentService
{
    public function __construct(
        protected DentalInvoiceService $invoiceService,
        protected DentalPrescriptionService $prescriptionService,
        protected DentalFollowupService $followupService,
    ) {}

    /**
     * Create a dental treatment and handle all cascading side-effects.
     * Wrapped in a database transaction for data integrity.
     */
    public function createTreatment(array $data): DentalTreatment
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = $data['status'] ?? 'planned';

            if ($data['status'] === 'completed') {
                $data['completed_at'] = now();
            }

            $treatment = DentalTreatment::create($data);

            AuditLogger::log('created', $treatment);

            $this->handlePostSave($treatment, false);

            return $treatment;
        });
    }

    /**
     * Update a dental treatment and handle cascading side-effects.
     * Wrapped in a database transaction for data integrity.
     */
    public function updateTreatment(DentalTreatment $treatment, array $data): DentalTreatment
    {
        return DB::transaction(function () use ($treatment, $data) {
            $wasCompleted = $treatment->status === 'completed';
            $oldStatus = $treatment->status;

            if (isset($data['status']) && $data['status'] === 'completed' && !$wasCompleted) {
                $data['completed_at'] = now();
            }

            $treatment->update($data);

            // Build audit description
            $description = null;
            if (isset($data['status']) && $data['status'] !== $oldStatus) {
                $toothLabel = $treatment->tooth_number ? " (tooth #{$treatment->tooth_number})" : '';
                $description = "Treatment status changed: {$oldStatus} → {$data['status']}{$toothLabel}";
            }

            AuditLogger::log('updated', $treatment, null, $description);

            $this->handlePostSave($treatment, $wasCompleted);

            return $treatment;
        });
    }

    /**
     * Delete a treatment and update plan progress if needed.
     * Wrapped in a database transaction for data integrity.
     */
    public function deleteTreatment(DentalTreatment $treatment): void
    {
        DB::transaction(function () use ($treatment) {
            AuditLogger::log('deleted', $treatment);

            $planId = $treatment->treatment_plan_id;
            $treatment->delete();

            if ($planId) {
                $this->updatePlanProgress($planId);
            }
        });
    }

    /**
     * Handle all post-save side-effects (chart, plan, invoice, prescription).
     */
    protected function handlePostSave(DentalTreatment $treatment, bool $wasCompleted): void
    {
        // Update dental chart if tooth number specified and completed
        if ($treatment->tooth_number && $treatment->status === 'completed') {
            $this->updateChartAfterTreatment($treatment);
        }

        // Update treatment plan progress
        if ($treatment->treatment_plan_id) {
            $this->updatePlanProgress($treatment->treatment_plan_id);
        }

        // Auto-generate invoice and prescription when newly completed
        if (!$wasCompleted && $treatment->status === 'completed') {
            $this->invoiceService->generateForCompletedTreatment($treatment);
            $this->prescriptionService->generateForCompletedTreatment($treatment);

            // Notify secretaries about completed treatment (for invoicing)
            $secretaries = User::whereHas('role', fn ($q) => $q->where('name', 'secretary'))
                ->where('is_active', true)->get();
            Notification::send($secretaries, new DentalTreatmentCompletedNotification($treatment));

            // Auto-schedule follow-up based on treatment type rules
            try {
                $this->followupService->scheduleForCompletedTreatment($treatment);
            } catch (\Throwable $e) {
                Log::warning('Auto follow-up scheduling failed', [
                    'treatment_id' => $treatment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Smart Notification: Queue "How are you?" post-treatment check (sent 48h later)
            try {
                DentalSmartNotificationService::queuePostTreatmentCheck($treatment);
            } catch (\Throwable $e) {
                Log::warning('Post-treatment smart notification failed', [
                    'treatment_id' => $treatment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update the dental chart based on a completed treatment.
     */
    public function updateChartAfterTreatment(DentalTreatment $treatment): void
    {
        $conditionMap = [
            'filling' => 'filled',
            'extraction' => 'extracted',
            'surgical_extraction' => 'extracted',
            'root_canal' => 'root_canal',
            'crown' => 'crown',
            'bridge' => 'bridge',
            'implant' => 'implant',
        ];

        if (!isset($conditionMap[$treatment->treatment_type])) {
            return;
        }

        $newCondition = $conditionMap[$treatment->treatment_type];
        $newStatus = in_array($treatment->treatment_type, ['extraction', 'surgical_extraction']) ? 'missing' : 'present';

        $existing = DentalChart::where('patient_id', $treatment->patient_id)
            ->where('tooth_number', $treatment->tooth_number)
            ->first();

        $oldCondition = $existing?->condition ?? 'healthy';

        $chart = DentalChart::updateOrCreate(
            ['patient_id' => $treatment->patient_id, 'tooth_number' => $treatment->tooth_number],
            ['condition' => $newCondition, 'status' => $newStatus]
        );

        AuditLogger::log(
            $chart->wasRecentlyCreated ? 'created' : 'updated',
            $chart,
            ['old' => ['condition' => $oldCondition], 'new' => ['condition' => $newCondition]],
            "Tooth #{$treatment->tooth_number} chart updated: {$oldCondition} → {$newCondition} (from {$treatment->treatment_type} treatment)"
        );
    }

    /**
     * Update treatment plan progress after any treatment change.
     */
    public function updatePlanProgress(int $planId): void
    {
        $plan = DentalTreatmentPlan::find($planId);
        if (!$plan) return;

        $completedCount = $plan->treatments()->where('status', 'completed')->count();
        $actualCost = $plan->treatments()->where('status', 'completed')->sum(DB::raw('cost + lab_cost'));

        $plan->update([
            'completed_sessions' => $completedCount,
            'actual_cost' => $actualCost,
        ]);
    }
}
