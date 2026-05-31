<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToBranch, HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'employee_number',
        'department_id',
        'job_title_ar',
        'job_title_en',
        'hire_date',
        'contract_type',
        'contract_end_date',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'national_id',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'bank_name',
        'bank_account_number',
        'insurance_number',
        'status',
        'termination_date',
        'termination_reason',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'contract_end_date' => 'date',
            'termination_date' => 'date',
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
        ];
    }

    // ─── Relationships ───────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function salarySlips(): HasMany
    {
        return $this->hasMany(SalarySlip::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringContracts(Builder $query, int $days = 30): Builder
    {
        return $query->where('contract_type', '!=', 'full_time')
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now(), now()->addDays($days)]);
    }

    // ─── Accessors ───────────────────────────────────────

    public function getTotalAllowancesAttribute(): float
    {
        return $this->housing_allowance + $this->transport_allowance + $this->other_allowances;
    }

    public function getGrossSalaryAttribute(): float
    {
        return $this->basic_salary + $this->total_allowances;
    }

    // ─── Helpers ─────────────────────────────────────────

    public static function generateEmployeeNumber(): string
    {
        // Global sequence across branches (the number has a single org-wide
        // unique). Bypass the branch scope so two branches never derive the
        // same EMP-#### from their local max id.
        $last = static::withoutGlobalScope('branch')->max('id') ?? 0;

        return 'EMP-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
