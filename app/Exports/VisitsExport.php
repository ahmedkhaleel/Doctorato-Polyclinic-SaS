<?php

namespace App\Exports;

use App\Models\Visit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFrom;
    protected $dateTo;
    protected $status;
    protected $doctorId;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null, ?string $status = null, ?int $doctorId = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->doctorId = $doctorId;
    }

    public function query()
    {
        $query = Visit::with(['patient', 'doctor', 'service'])->orderBy('visit_date', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('visit_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('visit_date', '<=', $this->dateTo);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->doctorId) {
            $query->where('doctor_id', $this->doctorId);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Date', 'Patient', 'Doctor', 'Service', 'Type', 'Session #', 'Status', 'Commission Rate', 'Commission Amount', 'Started At', 'Completed At'];
    }

    public function map($visit): array
    {
        return [
            $visit->visit_date?->format('d/m/Y') ?? '-',
            $visit->patient?->full_name ?? '-',
            $visit->doctor?->name_en ?? '-',
            $visit->service?->name_en ?? '-',
            ucfirst(str_replace('_', ' ', $visit->visit_type ?? '-')),
            $visit->session_number ?? '-',
            ucfirst($visit->status),
            $visit->commission_rate ? $visit->commission_rate . '%' : '-',
            $visit->commission_amount ? number_format($visit->commission_amount, 2) : '-',
            $visit->started_at?->format('d/m/Y H:i') ?? '-',
            $visit->completed_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']]],
        ];
    }
}
