<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToBranch, HasFactory, LogsActivity;

    protected $fillable = [
        'slip_number',
        'employee_id',
        'expense_id',
        'month',
        'year',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'overtime_amount',
        'bonus',
        'commission_amount',
        'insurance_deduction',
        'tax_deduction',
        'absence_deduction',
        'advance_deduction',
        'penalty_deduction',
        'other_deductions',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'status',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'payment_method',
        'payment_reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'bonus' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'insurance_deduction' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'penalty_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeForPeriod(Builder $query, int $month, int $year): Builder
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    // ─── Helpers ─────────────────────────────────────────

    public static function generateSlipNumber(int $month, int $year): string
    {
        $prefix = sprintf('SAL-%s%04d%02d-', \App\Services\Branch\BranchNumber::segment(), $year, $month);
        $last = static::where('slip_number', 'like', $prefix.'%')->max('slip_number');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $this->total_earnings = $this->basic_salary
            + $this->housing_allowance
            + $this->transport_allowance
            + $this->other_allowances
            + $this->overtime_amount
            + $this->bonus
            + $this->commission_amount;

        $this->total_deductions = $this->insurance_deduction
            + $this->tax_deduction
            + $this->absence_deduction
            + $this->advance_deduction
            + $this->penalty_deduction
            + $this->other_deductions;

        $this->net_salary = $this->total_earnings - $this->total_deductions;
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        return ($months[$this->month] ?? '').' '.$this->year;
    }
}
