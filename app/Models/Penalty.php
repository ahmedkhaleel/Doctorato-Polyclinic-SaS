<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Penalty extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'type',
        'amount',
        'reason',
        'date',
        'applied_to_salary',
        'salary_slip_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'applied_to_salary' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salarySlip(): BelongsTo
    {
        return $this->belongsTo(SalarySlip::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePenalties(Builder $query): Builder
    {
        return $query->where('type', 'penalty');
    }

    public function scopeRewards(Builder $query): Builder
    {
        return $query->where('type', 'reward');
    }

    public function scopeUnapplied(Builder $query): Builder
    {
        return $query->where('applied_to_salary', false);
    }
}
