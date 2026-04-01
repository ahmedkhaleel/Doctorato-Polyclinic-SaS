<?php

namespace App\Exports;

use App\Models\DentalTreatmentPlan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DentalTreatmentPlansExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?string $dateFrom;
    protected ?string $dateTo;
    protected ?string $status;
    protected ?int $doctorId;

    public function __construct(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null,
        ?int $doctorId = null
    ) {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->doctorId = $doctorId;
    }

    public function query()
    {
        $query = DentalTreatmentPlan::with(['patient', 'doctor', 'consent'])
            ->withCount('treatments')
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

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID', 'Title (EN)', 'Title (AR)', 'Patient', 'File #',
            'Doctor', 'Status', 'Priority', 'Treatments Count',
            'Completed Sessions', 'Estimated Sessions',
            'Estimated Cost', 'Actual Cost', 'Consent Status',
            'Start Date', 'Created At',
        ];
    }

    public function map($plan): array
    {
        return [
            $plan->id,
            $plan->title_en ?? '-',
            $plan->title_ar ?? '-',
            $plan->patient?->full_name ?? '-',
            $plan->patient?->file_number ?? '-',
            $plan->doctor?->name_en ?? '-',
            ucfirst(str_replace('_', ' ', $plan->status ?? '-')),
            ucfirst($plan->priority ?? 'normal'),
            $plan->treatments_count ?? 0,
            $plan->completed_sessions ?? 0,
            $plan->estimated_sessions ?? 0,
            number_format((float) $plan->estimated_cost, 2),
            number_format((float) $plan->actual_cost, 2),
            $plan->consent ? ucfirst($plan->consent->status) : 'No Consent',
            $plan->start_date ?? '-',
            $plan->created_at?->format('d/m/Y') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B5CF6']]],
        ];
    }
}
