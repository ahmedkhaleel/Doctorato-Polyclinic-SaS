<?php

namespace App\Exports;

use App\Models\SalarySlip;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DoctorSalarySlipsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $employeeId;
    protected $month;
    protected $year;

    public function __construct(int $employeeId, ?int $month = null, ?int $year = null)
    {
        $this->employeeId = $employeeId;
        $this->month = $month;
        $this->year = $year;
    }

    public function query()
    {
        $query = SalarySlip::query()
            ->with(['employee.user:id,name', 'employee.department:id,name_en'])
            ->where('employee_id', $this->employeeId)
            ->where('status', 'paid')
            ->orderByDesc('year')
            ->orderByDesc('month');

        if ($this->month && $this->year) {
            $query->forPeriod($this->month, $this->year);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Slip #', 'Month', 'Year', 'Status',
            'Basic Salary', 'Housing', 'Transport', 'Other Allowances',
            'Overtime', 'Bonus', 'Commission',
            'Total Earnings',
            'Insurance', 'Tax', 'Absence', 'Advance', 'Penalty', 'Other Deductions',
            'Total Deductions',
            'Net Salary',
        ];
    }

    public function map($slip): array
    {
        $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        return [
            $slip->slip_number,
            $months[$slip->month] ?? $slip->month,
            $slip->year,
            ucfirst($slip->status),
            number_format($slip->basic_salary, 2),
            number_format($slip->housing_allowance, 2),
            number_format($slip->transport_allowance, 2),
            number_format($slip->other_allowances, 2),
            number_format($slip->overtime_amount, 2),
            number_format($slip->bonus, 2),
            number_format($slip->commission_amount, 2),
            number_format($slip->total_earnings, 2),
            number_format($slip->insurance_deduction, 2),
            number_format($slip->tax_deduction, 2),
            number_format($slip->absence_deduction, 2),
            number_format($slip->advance_deduction, 2),
            number_format($slip->penalty_deduction, 2),
            number_format($slip->other_deductions, 2),
            number_format($slip->total_deductions, 2),
            number_format($slip->net_salary, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']]],
        ];
    }
}
