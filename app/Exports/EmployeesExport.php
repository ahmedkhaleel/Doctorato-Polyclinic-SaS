<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $status;
    protected $departmentId;

    public function __construct(?string $status = null, ?int $departmentId = null)
    {
        $this->status = $status;
        $this->departmentId = $departmentId;
    }

    public function query()
    {
        $query = Employee::query()
            ->with(['user:id,name,email', 'department:id,name_en'])
            ->orderBy('employee_number');

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Employee #', 'Name', 'Email', 'Department', 'Job Title',
            'Contract Type', 'Hire Date', 'Status',
            'Basic Salary', 'Housing', 'Transport', 'Other Allowances', 'Gross Salary',
            'Phone', 'National ID',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_number,
            $employee->user->name ?? '-',
            $employee->user->email ?? '-',
            $employee->department->name_en ?? '-',
            $employee->job_title_en ?? '-',
            str_replace('_', ' ', ucfirst($employee->contract_type ?? '-')),
            $employee->hire_date?->format('d/m/Y') ?? '-',
            ucfirst($employee->status ?? '-'),
            number_format($employee->basic_salary, 2),
            number_format($employee->housing_allowance, 2),
            number_format($employee->transport_allowance, 2),
            number_format($employee->other_allowances, 2),
            number_format($employee->basic_salary + $employee->housing_allowance + $employee->transport_allowance + $employee->other_allowances, 2),
            $employee->phone ?? '-',
            $employee->national_id ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']]],
        ];
    }
}
