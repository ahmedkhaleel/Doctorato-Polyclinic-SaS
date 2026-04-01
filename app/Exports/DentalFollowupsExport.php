<?php

namespace App\Exports;

use App\Models\DentalScheduledFollowup;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DentalFollowupsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        $query = DentalScheduledFollowup::with(['patient', 'doctor', 'treatment'])
            ->orderBy('scheduled_date', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('scheduled_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('scheduled_date', '<=', $this->dateTo);
        }
        if ($this->status) {
            if ($this->status === 'overdue') {
                $query->where('status', 'pending')
                      ->whereNull('booking_id')
                      ->where('scheduled_date', '<', now()->toDateString());
            } else {
                $query->where('status', $this->status);
            }
        }
        if ($this->doctorId) {
            $query->where('doctor_id', $this->doctorId);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID', 'Patient', 'File #', 'Phone', 'Doctor',
            'Treatment Type', 'Tooth #', 'Scheduled Date',
            'Status', 'Booking ID', 'Notes', 'Created At',
        ];
    }

    public function map($followup): array
    {
        $isOverdue = $followup->status === 'pending'
            && !$followup->booking_id
            && $followup->scheduled_date < now()->toDateString();

        return [
            $followup->id,
            $followup->patient?->full_name ?? '-',
            $followup->patient?->file_number ?? '-',
            $followup->patient?->phone ?? '-',
            $followup->doctor?->name_en ?? '-',
            ucfirst(str_replace('_', ' ', $followup->treatment?->treatment_type ?? '-')),
            $followup->treatment?->tooth_number ?? '-',
            $followup->scheduled_date ?? '-',
            $isOverdue ? 'OVERDUE' : ucfirst(str_replace('_', ' ', $followup->status ?? '-')),
            $followup->booking_id ?? '-',
            $followup->notes ?? '-',
            $followup->created_at?->format('d/m/Y') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F59E0B']]],
        ];
    }
}
