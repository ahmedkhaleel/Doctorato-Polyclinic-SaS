<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Fields to exclude from change tracking.
     */
    protected static array $excludedFields = [
        'password', 'remember_token', 'two_factor_secret',
        'two_factor_recovery_codes', 'updated_at', 'created_at',
    ];

    /**
     * Main logging method — backward-compatible with all existing calls.
     */
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $changes = null,
        ?string $description = null,
    ): void {
        if ($changes === null && $model !== null) {
            $changes = static::detectChanges($action, $model);
        }

        if ($description === null) {
            $description = static::generateDescription($action, $model);
        }

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->getKey(),
            'changes'     => $changes,
            'ip_address'  => request()->ip(),
            'panel'       => static::detectPanel(),
            'user_agent'  => static::getUserAgent(),
            'description' => $description ? mb_substr($description, 0, 500) : null,
        ]);
    }

    /**
     * Log an auth event (login, logout, failed_login).
     */
    public static function authEvent(string $action, ?string $email = null, ?string $panel = null): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'model_type'  => $user ? get_class($user) : null,
            'model_id'    => $user?->getKey(),
            'changes'     => $email ? ['email' => $email] : null,
            'ip_address'  => request()->ip(),
            'panel'       => $panel ?? static::detectPanel(),
            'user_agent'  => static::getUserAgent(),
            'description' => static::generateAuthDescription($action, $user, $email, $panel),
        ]);
    }

    /**
     * Auto-detect old/new values from model state.
     */
    protected static function detectChanges(string $action, Model $model): ?array
    {
        if ($action === 'created') {
            $attributes = collect($model->getAttributes())
                ->except(static::$excludedFields)
                ->toArray();
            return $attributes ? ['new' => $attributes] : null;
        }

        if ($action === 'deleted') {
            $attributes = collect($model->getOriginal())
                ->except(static::$excludedFields)
                ->toArray();
            return $attributes ? ['old' => $attributes] : null;
        }

        // For 'updated' and similar actions — capture dirty if available
        $dirty = $model->getDirty();
        if (empty($dirty)) {
            return null;
        }

        $old = [];
        $new = [];
        foreach ($dirty as $key => $value) {
            if (in_array($key, static::$excludedFields)) continue;
            $old[$key] = $model->getOriginal($key);
            $new[$key] = $value;
        }

        return ($old || $new) ? ['old' => $old, 'new' => $new] : null;
    }

    /**
     * Detect the current panel from the request URL prefix.
     */
    protected static function detectPanel(): ?string
    {
        $path = request()->path();

        if (str_starts_with($path, 'admin')) return 'admin';
        if (str_starts_with($path, 'secretary')) return 'secretary';
        if (str_starts_with($path, 'doctor')) return 'doctor';
        if (str_starts_with($path, 'webmaster')) return 'webmaster';

        return null;
    }

    /**
     * Get user agent, truncated for safety.
     */
    protected static function getUserAgent(): ?string
    {
        $ua = request()->userAgent();
        return $ua ? mb_substr($ua, 0, 500) : null;
    }

    /**
     * Generate a human-readable description.
     */
    protected static function generateDescription(string $action, ?Model $model): string
    {
        $userName = Auth::user()?->name ?? 'System';
        $actionLabel = ucfirst(str_replace('_', ' ', $action));

        if (!$model) {
            return "{$userName} {$actionLabel}";
        }

        $modelLabel = class_basename($model);
        $modelIdentifier = static::getModelIdentifier($model);

        return "{$userName} {$actionLabel} {$modelLabel} {$modelIdentifier}";
    }

    /**
     * Get a meaningful identifier for the model.
     */
    protected static function getModelIdentifier(Model $model): string
    {
        $identifierFields = [
            'full_name', 'name', 'name_en', 'title', 'title_en',
            'invoice_number', 'payout_number', 'file_number', 'medication_name',
        ];

        foreach ($identifierFields as $field) {
            $value = $model->getAttribute($field);
            if ($value) {
                return '"' . mb_substr($value, 0, 80) . '"';
            }
        }

        return '#' . $model->getKey();
    }

    protected static function generateAuthDescription(string $action, $user, ?string $email, ?string $panel): string
    {
        $panelLabel = ucfirst($panel ?? 'unknown');

        return match ($action) {
            'login'        => ($user?->name ?? $email ?? 'Unknown') . " logged in to {$panelLabel} panel",
            'logout'       => ($user?->name ?? 'Unknown') . " logged out of {$panelLabel} panel",
            'failed_login' => "Failed login attempt for {$email} on {$panelLabel} panel",
            default        => ($user?->name ?? $email ?? 'Unknown') . " {$action} on {$panelLabel} panel",
        };
    }
}
