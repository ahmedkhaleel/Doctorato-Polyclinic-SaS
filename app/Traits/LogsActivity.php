<?php

namespace App\Traits;

use App\Services\AuditLogger;

trait LogsActivity
{
    /**
     * Static registry to hold pending changes between updating/updated events.
     * Keyed by "ClassName:primaryKey" to avoid any Eloquent attribute issues.
     */
    protected static array $pendingActivityChanges = [];

    public static function bootLogsActivity(): void
    {
        // After create: log with all new attributes
        static::created(function ($model) {
            AuditLogger::log('created', $model);
        });

        // Before update: capture original values while still available
        static::updating(function ($model) {
            $excluded = $model->getActivityExcludedFields();
            $oldValues = [];
            $newValues = [];

            foreach ($model->getDirty() as $key => $newValue) {
                if (in_array($key, $excluded)) continue;
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $newValue;
            }

            // Store in static registry instead of on the model (PHP 8.4 safe)
            $registryKey = get_class($model) . ':' . $model->getKey();
            static::$pendingActivityChanges[$registryKey] = [
                'old' => $oldValues,
                'new' => $newValues,
            ];
        });

        // After update: log with captured old/new values
        static::updated(function ($model) {
            $registryKey = get_class($model) . ':' . $model->getKey();
            $changes = static::$pendingActivityChanges[$registryKey] ?? null;
            unset(static::$pendingActivityChanges[$registryKey]);

            if (! $changes || (empty($changes['old']) && empty($changes['new']))) {
                return;
            }

            AuditLogger::log('updated', $model, $changes);
        });

        // Before delete: log with current attributes
        static::deleting(function ($model) {
            AuditLogger::log('deleted', $model);
        });
    }

    /**
     * Override in the model to exclude certain fields from logging.
     */
    public function getActivityExcludedFields(): array
    {
        return property_exists($this, 'activityExcludedFields')
            ? $this->activityExcludedFields
            : ['password', 'remember_token', 'updated_at', 'created_at'];
    }
}
