<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait HasStatusTransitions
 *
 * Enforces valid status transitions on Eloquent models.
 * Models using this trait must define:
 *   - const ALLOWED_TRANSITIONS = ['old_status' => ['new_status1', 'new_status2'], ...]
 *   - A 'status' column
 */
trait HasStatusTransitions
{
    /**
     * Boot the trait: register a model "updating" event to validate transitions.
     */
    public static function bootHasStatusTransitions(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                $oldStatus = $model->getOriginal('status');
                $newStatus = $model->status;

                if (!$model->isValidTransition($oldStatus, $newStatus)) {
                    Log::warning('Invalid status transition blocked', [
                        'model' => get_class($model),
                        'id' => $model->id,
                        'from' => $oldStatus,
                        'to' => $newStatus,
                    ]);

                    throw new \InvalidArgumentException(
                        "Invalid status transition: '{$oldStatus}' → '{$newStatus}' is not allowed for " . class_basename($model)
                    );
                }
            }
        });
    }

    /**
     * Check if a status transition is valid.
     */
    public function isValidTransition(?string $from, string $to): bool
    {
        // Same status is always valid (no actual change)
        if ($from === $to) {
            return true;
        }

        // If no transitions defined, allow all
        if (!defined(static::class . '::ALLOWED_TRANSITIONS')) {
            return true;
        }

        // New record (no previous status) — allow any initial status
        if ($from === null) {
            return true;
        }

        $allowed = static::ALLOWED_TRANSITIONS[$from] ?? [];

        return in_array($to, $allowed, true);
    }

    /**
     * Get allowed next statuses from current status.
     */
    public function getAllowedNextStatuses(): array
    {
        if (!defined(static::class . '::ALLOWED_TRANSITIONS')) {
            return [];
        }

        return static::ALLOWED_TRANSITIONS[$this->status] ?? [];
    }

    /**
     * Check if model can transition to a given status.
     */
    public function canTransitionTo(string $status): bool
    {
        return $this->isValidTransition($this->status, $status);
    }
}
