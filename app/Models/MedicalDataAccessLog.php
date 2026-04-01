<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class MedicalDataAccessLog extends Model
{
    protected $fillable = [
        'user_id',
        'patient_id',
        'access_type',
        'data_category',
        'fields_accessed',
        'panel',
        'ip_address',
        'user_agent',
        'reason',
    ];

    protected $casts = [
        'fields_accessed' => 'array',
    ];

    // ── Access Types ─────────────────────────────────
    const ACCESS_VIEW = 'view_medical';
    const ACCESS_UPDATE = 'update_medical';
    const ACCESS_EXPORT = 'export_medical';
    const ACCESS_PRINT = 'print_medical';

    // ── Data Categories ──────────────────────────────
    const CATEGORY_DENTAL_MEDICAL = 'dental_medical';
    const CATEGORY_RISK_FLAGS = 'risk_flags';
    const CATEGORY_SENSITIVE = 'sensitive_medical';
    const CATEGORY_FULL_RECORD = 'full_record';

    // ── Relationships ────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Scopes ───────────────────────────────────────
    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSensitive($query)
    {
        return $query->where('data_category', self::CATEGORY_SENSITIVE);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ── Static Helpers ───────────────────────────────

    /**
     * Log a medical data access event.
     */
    public static function record(
        int $patientId,
        string $accessType,
        string $dataCategory,
        ?array $fieldsAccessed = null,
        ?string $reason = null,
    ): self {
        $panel = null;
        $path = request()->path();
        if (str_starts_with($path, 'admin')) $panel = 'admin';
        elseif (str_starts_with($path, 'doctor')) $panel = 'doctor';
        elseif (str_starts_with($path, 'secretary')) $panel = 'secretary';

        return static::create([
            'user_id' => Auth::id(),
            'patient_id' => $patientId,
            'access_type' => $accessType,
            'data_category' => $dataCategory,
            'fields_accessed' => $fieldsAccessed,
            'panel' => $panel,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr(request()->userAgent() ?? '', 0, 500),
            'reason' => $reason,
        ]);
    }

    /**
     * Quick helper — log viewing of sensitive medical data.
     */
    public static function logSensitiveView(int $patientId, ?array $fields = null): self
    {
        return static::record($patientId, self::ACCESS_VIEW, self::CATEGORY_SENSITIVE, $fields);
    }

    /**
     * Quick helper — log updating of medical data.
     */
    public static function logMedicalUpdate(int $patientId, ?array $fields = null): self
    {
        return static::record($patientId, self::ACCESS_UPDATE, self::CATEGORY_DENTAL_MEDICAL, $fields);
    }
}
