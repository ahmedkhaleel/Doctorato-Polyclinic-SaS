<?php

namespace App\Exports;

use App\Models\Visit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CommissionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFrom;
    protected $dateTo;
    protected $doctorId;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null, ?int $doctorId = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->doctorId = $doctorId;
    }

    public function query()
    {
        $query = Visit::with(['patient', 'doctor', 'service', 'invoice'])
            ->where('status', 'completed')
            ->whereNotNull('commission_amount')
            ->where('commission_amount', '>', 0)
            ->orderBy('visit_date', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('visit_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('visit_date', '<=', $this->dateTo);
        }
        if ($this->doctorId) {
            $query->where('doctor_id', $this->doctorId);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Date', 'Doctor', 'Patient', 'Service', 'Invoice Total', 'Paid Amount', 'Commission Rate', 'Commission Amount'];
    }

    public function map($visit): array
    {
        return [
            $visit->visit_date?->format('d/m/Y') ?? '-',
            $visit->doctor?->name_en ?? '-',
            $visit->patient?->full_name ?? '-',
            $visit->service?->name_en ?? '-',
            $visit->invoice ? number_format($visit->invoice->total, 2) : '-',
            $visit->invoice ? number_format($visit->invoice->paid_amount, 2) : '-',
            $visit->commission_rate ? $visit->commission_rate . '%' : '-',
            number_format($visit->commission_amount, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']]],
        ];
    }
}
