<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A dermatology course — a planned series of sessions of one type.
 * Linked derma sessions drive completed_sessions and the status lifecycle.
 */
class DermaTreatmentPlan extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    const STATUS_PLANNED     = 'planned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_CANCELLED   = 'cancelled';

    const TYPES = ['laser', 'peel', 'phototherapy', 'injection', 'cryotherapy', 'other'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'skin_condition_id', 'created_by',
        'title_ar', 'title_en', 'session_type',
        'estimated_sessions', 'completed_sessions', 'interval_days',
        'estimated_cost', 'status', 'start_date', 'notes',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'start_date'     => 'date',
    ];

    protected $appends = ['progress_percentage', 'sessions_remaining'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function skinCondition() { return $this->belongsTo(SkinCondition::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function sessions() { return $this->hasMany(DermaSession::class, 'treatment_plan_id'); }

    public function getProgressPercentageAttribute(): int
    {
        if (! $this->estimated_sessions) {
            return 0;
        }

        return min(100, (int) round(($this->completed_sessions / $this->estimated_sessions) * 100));
    }

    public function getSessionsRemainingAttribute(): int
    {
        return max(0, (int) $this->estimated_sessions - (int) $this->completed_sessions);
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', [self::STATUS_PLANNED, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Recompute completed_sessions from linked completed sessions and advance
     * the status. Idempotent (counts rows), so it self-corrects on edits.
     */
    public function syncProgress(): void
    {
        $completed = $this->sessions()->whereNotNull('completed_at')->count();
        $status = $this->status;

        if ($status !== self::STATUS_CANCELLED) {
            if ($this->estimated_sessions && $completed >= $this->estimated_sessions) {
                $status = self::STATUS_COMPLETED;
            } elseif ($completed > 0) {
                $status = self::STATUS_IN_PROGRESS;
            } else {
                $status = self::STATUS_PLANNED;
            }
        }

        $this->update(['completed_sessions' => $completed, 'status' => $status]);
    }
}
