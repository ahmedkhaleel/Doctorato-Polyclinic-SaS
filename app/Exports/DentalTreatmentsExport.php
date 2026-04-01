<?php

namespace App\Exports;

use App\Models\DentalTreatment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DentalTreatmentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?string $dateFrom;
    protected ?string $dateTo;
    protected ?string $status;
    protected ?int $doctorId;
    protected ?string $treatmentType;

    public function __construct(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null,
        ?int $doctorId = null,
        ?string $treatmentType = null
    ) {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->doctorId = $doctorId;
        $this->treatmentType = $treatmentType;
    }

    public function query()
    {
        $query = DentalTreatment::with(['patient', 'doctor', 'treatmentPlan'])
            ->orderBy('created_at', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->doctorId) {
            $query->where('doctor_id', $this->doctorId);
        }
        if ($this->treatmentType) {
            $query->where('treatment_type', $this->treatmentType);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID', 'Patient', 'File #', 'Doctor', 'Treatment Type', 'Tooth #',
            'Surfaces', 'Description', 'Cost', 'Lab Cost', 'Total Cost',
            'Status', 'Treatment Plan', 'Completed At', 'Created At',
        ];
    }

    public function map($treatment): array
    {
        return [
            $treatment->id,
            $treatment->patient?->full_name ?? '-',
            $treatment->patient?->file_number ?? '-',
            $treatment->doctor?->name_en ?? '-',
            ucfirst(str_replace('_', ' ', $treatment->treatment_type ?? '-')),
            $treatment->tooth_number ?? '-',
            is_array($treatment->surfaces) ? implode(', ', $treatment->surfaces) : '-',
            $treatment->description ?? '-',
            number_format((float) $treatment->cost, 2),
            number_format((float) $treatment->lab_cost, 2),
            number_format((float) $treatment->cost + (float) $treatment->lab_cost, 2),
            ucfirst(str_replace('_', ' ', $treatment->status ?? '-')),
            $treatment->treatmentPlan?->title_en ?? '-',
            $treatment->completed_at?->format('d/m/Y') ?? '-',
            $treatment->created_at?->format('d/m/Y') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '06B6D4']]],
        ];
    }
}
