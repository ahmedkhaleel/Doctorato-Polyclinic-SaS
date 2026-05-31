<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advance extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToBranch, HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'amount',
        'monthly_installment',
        'remaining_balance',
        'total_paid',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'start_month',
        'start_year',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'approved_at' => 'datetime',
            'start_month' => 'integer',
            'start_year' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
