<?php

namespace App\Services;

use App\Models\DoctorPayout;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SalarySlip;
use Illuminate\Support\Facades\DB;

/**
 * Records labor costs (salary slips, doctor commission payouts) in the
 * Expense ledger when they are paid, so the clinic's largest outflow shows
 * up in the financial reports. Each record links back via expense_id, making
 * the entry idempotent (never double-booked) and reversible (voided if the
 * payment is reversed/cancelled).
 */
class LaborExpenseService
{
    /** Record a paid salary slip as a 'Salaries' expense. */
    public function recordForSalarySlip(SalarySlip $slip): ?Expense
    {
        if ($slip->expense_id || (float) $slip->net_salary <= 0) {
            return $slip->expense_id ? $slip->expense : null;
        }

        return DB::transaction(function () use ($slip) {
            $slip->loadMissing('employee.user');
            $name = $slip->employee?->user?->name ?? "Employee #{$slip->employee_id}";

            $expense = Expense::create([
                'expense_category_id' => $this->categoryId('Salaries', 'الرواتب'),
                'amount'              => (float) $slip->net_salary,
                'expense_date'        => ($slip->paid_at ?? now())->toDateString(),
                'description'         => "Salary: {$name} — {$slip->month}/{$slip->year} [{$slip->slip_number}]",
                'created_by'          => auth()->id(),
            ]);

            $slip->forceFill(['expense_id' => $expense->id])->saveQuietly();

            return $expense;
        });
    }

    /** Record a paid doctor payout as a 'Doctor Commissions' expense. */
    public function recordForDoctorPayout(DoctorPayout $payout): ?Expense
    {
        if ($payout->expense_id || (float) $payout->net_amount <= 0) {
            return $payout->expense_id ? $payout->expense : null;
        }

        return DB::transaction(function () use ($payout) {
            $payout->loadMissing('doctor');
            $name = $payout->doctor?->name_ar ?? $payout->doctor?->name_en ?? "Doctor #{$payout->doctor_id}";

            $expense = Expense::create([
                'expense_category_id' => $this->categoryId('Doctor Commissions', 'عمولات الأطباء'),
                'amount'              => (float) $payout->net_amount,
                'expense_date'        => ($payout->paid_at ?? now())->toDateString(),
                'description'         => "Doctor commission: {$name} — {$payout->payout_number}",
                'created_by'          => auth()->id(),
            ]);

            $payout->forceFill(['expense_id' => $expense->id])->saveQuietly();

            return $expense;
        });
    }

    /** Void the linked expense (on un-pay / cancel). Safe + idempotent. */
    public function reverse($model): void
    {
        if (! $model->expense_id) {
            return;
        }

        DB::transaction(function () use ($model) {
            Expense::where('id', $model->expense_id)->delete();
            $model->forceFill(['expense_id' => null])->saveQuietly();
        });
    }

    /** Find or create an expense category by name (idempotent). */
    private function categoryId(string $en, string $ar): ?int
    {
        return ExpenseCategory::firstOrCreate(
            ['name_en' => $en],
            ['name_ar' => $ar, 'is_active' => true]
        )->id;
    }
}
