<?php

namespace App\Services;

use App\Models\Advance;
use App\Models\Attendance;
use App\Models\DoctorPayout;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Penalty;
use App\Models\SalarySlip;
use Carbon\Carbon;

class PayrollService
{
    /**
     * Generate a salary slip for an employee for the given month/year.
     * Pulls: base salary + allowances, overtime, absence deduction,
     * advance installment, penalties/rewards, and doctor commission.
     */
    public function generateForEmployee(Employee $employee, int $month, int $year): SalarySlip
    {
        $employee->loadMissing('user');

        // Base salary + allowances from employee record
        $basicSalary = (float) $employee->basic_salary;
        $housingAllowance = (float) $employee->housing_allowance;
        $transportAllowance = (float) $employee->transport_allowance;
        $otherAllowances = (float) $employee->other_allowances;

        // Daily rate for absence calculation (30-day month)
        $grossSalary = $basicSalary + $housingAllowance + $transportAllowance + $otherAllowances;
        $dailyRate = $grossSalary / 30;

        // Overtime from attendance records
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $overtimeHours = Attendance::where('user_id', $employee->user_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('overtime_hours');

        // Overtime rate: 1.5x hourly rate (8 hours/day)
        $hourlyRate = $dailyRate / 8;
        $overtimeAmount = round($overtimeHours * $hourlyRate * 1.5, 2);

        // Absence deduction: raw absent records (status='absent')
        $absentDays = Attendance::where('user_id', $employee->user_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'absent')
            ->count();

        // Unpaid leave deduction: approved unpaid leaves overlapping this month.
        // Attendance rows for those days are marked 'leave' (not 'absent'), so we
        // must count them separately. Days outside the month are clamped to its bounds.
        $unpaidLeaveDays = 0;
        if ($employee->user_id) {
            $unpaidLeaves = Leave::where('user_id', $employee->user_id)
                ->where('status', 'approved')
                ->where('leave_type', 'unpaid')
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->get(['start_date', 'end_date']);

            foreach ($unpaidLeaves as $leave) {
                $from = $leave->start_date->max($startDate);
                $to   = $leave->end_date->min($endDate);
                $unpaidLeaveDays += $from->diffInDays($to) + 1;
            }
        }

        $absenceDeduction = round(($absentDays + $unpaidLeaveDays) * $dailyRate, 2);

        // Active advance installment deduction
        $advanceDeduction = 0;
        $activeAdvance = Advance::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('remaining_balance', '>', 0)
            ->first();

        if ($activeAdvance) {
            $advanceDeduction = min(
                (float) $activeAdvance->monthly_installment,
                (float) $activeAdvance->remaining_balance
            );
        }

        // Penalties and rewards for the month
        $penaltyAmount = Penalty::where('employee_id', $employee->id)
            ->where('type', 'penalty')
            ->where('applied_to_salary', false)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('amount');

        $rewardAmount = Penalty::where('employee_id', $employee->id)
            ->where('type', 'reward')
            ->where('applied_to_salary', false)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('amount');

        $penaltyDeduction = (float) $penaltyAmount;
        $bonus = (float) $rewardAmount;

        // Doctor commission from paid DoctorPayout records (if applicable)
        $commissionAmount = 0;
        if ($employee->user && class_exists(DoctorPayout::class)) {
            $doctor = $employee->user->doctor ?? null;
            if ($doctor) {
                $commissionAmount = (float) DoctorPayout::where('doctor_id', $doctor->id)
                    ->where('status', 'paid')
                    ->whereMonth('period_start', $month)
                    ->whereYear('period_start', $year)
                    ->sum('total_commission');
            }
        }

        $slip = new SalarySlip([
            'slip_number' => SalarySlip::generateSlipNumber($month, $year),
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year,
            'basic_salary' => $basicSalary,
            'housing_allowance' => $housingAllowance,
            'transport_allowance' => $transportAllowance,
            'other_allowances' => $otherAllowances,
            'overtime_amount' => $overtimeAmount,
            'bonus' => $bonus,
            'commission_amount' => $commissionAmount,
            'insurance_deduction' => 0,
            'tax_deduction' => 0,
            'absence_deduction' => $absenceDeduction,
            'advance_deduction' => $advanceDeduction,
            'penalty_deduction' => $penaltyDeduction,
            'other_deductions' => 0,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $slip->recalculateTotals();
        $slip->save();

        // Mark penalties/rewards as applied
        Penalty::where('employee_id', $employee->id)
            ->where('applied_to_salary', false)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->update([
                'applied_to_salary' => true,
                'salary_slip_id' => $slip->id,
            ]);

        // Deduct from advance balance
        if ($activeAdvance && $advanceDeduction > 0) {
            $activeAdvance->remaining_balance -= $advanceDeduction;
            $activeAdvance->total_paid += $advanceDeduction;

            if ($activeAdvance->remaining_balance <= 0) {
                $activeAdvance->remaining_balance = 0;
                $activeAdvance->status = 'completed';
            }

            $activeAdvance->save();
        }

        return $slip;
    }
}
