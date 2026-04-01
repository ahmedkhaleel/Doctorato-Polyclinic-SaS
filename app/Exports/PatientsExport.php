<?php

namespace App\Exports;

use App\Models\Patient;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatientsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $search;
    protected $status;

    public function __construct(?string $search = null, ?string $status = null)
    {
        $this->search = $search;
        $this->status = $status;
    }

    public function query()
    {
        $query = Patient::query()->orderBy('created_at', 'desc');

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('file_number', 'like', "%{$s}%");
            });
        }

        if ($this->status !== null) {
            $query->where('is_active', $this->status === 'active');
        }

        return $query;
    }

    public function headings(): array
    {
        return ['File #', 'Full Name', 'Phone', 'Email', 'Gender', 'Date of Birth', 'Nationality', 'Referral Source', 'Status', 'Registered At'];
    }

    public function map($patient): array
    {
        return [
            $patient->file_number,
            $patient->full_name,
            $patient->phone,
            $patient->email ?? '-',
            ucfirst($patient->gender ?? '-'),
            $patient->date_of_birth?->format('d/m/Y') ?? '-',
            $patient->nationality ?? '-',
            str_replace('_', ' ', ucfirst($patient->referral_source ?? '-')),
            $patient->is_active ? 'Active' : 'Inactive',
            $patient->created_at?->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']], 'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
