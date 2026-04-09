<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;

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
}
