<?php

namespace App\Models;

use App\Notifications\BrandedPasswordReset;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        // Multi-branch: every new user is assigned to the default branch (primary)
        // so they always have at least one branch (mirrors the B3 backfill).
        static::created(function (self $user) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('branch_user') && ! $user->branches()->exists()) {
                    $user->branches()->attach((int) config('branches.default_id', 1), ['is_primary' => true]);
                }
            } catch (\Throwable $e) {
                // table not ready (early migrations) — ignore
            }
        });
    }

    protected array $activityExcludedFields = [
        'password', 'remember_token', 'updated_at', 'created_at', 'last_seen_at',
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'is_active',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['permissions'];

    protected $with = ['role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check authorization: supports both role-based permissions and model policies.
     *
     * - With model argument: delegates to Laravel Gate/Policy (e.g., $user->can('view', $patient))
     * - Without arguments: checks role permission string (e.g., $user->can('patients.view'))
     */
    public function can($permission, $arguments = []): bool
    {
        // If arguments contain a model or class, use Laravel's Gate/Policy system
        if (! empty($arguments)) {
            return app(\Illuminate\Contracts\Auth\Access\Gate::class)
                ->forUser($this)
                ->check($permission, $arguments);
        }

        // Simple string permission check via role
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role?->name === $roleName;
    }

    public function getPermissionsAttribute(): array
    {
        return $this->role?->permissions ?? [];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ─── Branch (multi-branch) ──────────────────────────

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withPivot('is_primary')->withTimestamps();
    }

    /** super_admin sees all branches; others only their assigned ones. */
    public function canSwitchAllBranches(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    public function belongsToBranch(int $branchId): bool
    {
        if ($this->canSwitchAllBranches()) {
            return true;
        }

        return $this->branches()->where('branches.id', $branchId)->exists();
    }

    public function primaryBranchId(): int
    {
        $primary = $this->branches()->wherePivot('is_primary', true)->value('branches.id')
            ?? $this->branches()->value('branches.id');

        return (int) ($primary ?? config('branches.default_id', 1));
    }

    // ─── Clinic Relationships ───────────────────────────

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    // ─── CRM Relationships ─────────────────────────────

    public function assignedLeads()
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    // ─── Chat Relationships ─────────────────────────────

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Override the default Laravel reset-password notification with our
     * branded HTML template that routes the link to the user's portal
     * (admin/doctor/secretary/webmaster/patient).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new BrandedPasswordReset($token));
    }
}
