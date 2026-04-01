<?php

namespace App\Exports;

use App\Models\DentalLabOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DentalLabOrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?string $dateFrom;
    protected ?string $dateTo;
    protected ?string $status;

    public function __construct(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null
    ) {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
    }

    public function query()
    {
        $query = DentalLabOrder::with(['patient', 'doctor'])
            ->orderBy('order_date', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('order_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('order_date', '<=', $this->dateTo);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID', 'Order #', 'Patient', 'File #', 'Doctor', 'Lab Name',
            'Item Type', 'Tooth #', 'Shade', 'Material',
            'Lab Cost', 'Patient Charge', 'Status',
            'Order Date', 'Expected Date', 'Delivered Date', 'Notes',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->order_number ?? '-',
            $order->patient?->full_name ?? '-',
            $order->patient?->file_number ?? '-',
            $order->doctor?->name_en ?? '-',
            $order->lab_name ?? '-',
            ucfirst(str_replace('_', ' ', $order->item_type ?? '-')),
            $order->tooth_number ?? '-',
            $order->shade ?? '-',
            ucfirst(str_replace('_', ' ', $order->material ?? '-')),
            number_format((float) $order->cost, 2),
            number_format((float) $order->patient_charge, 2),
            ucfirst(str_replace('_', ' ', $order->status ?? '-')),
            $order->order_date ?? '-',
            $order->expected_date ?? '-',
            $order->delivered_date ?? '-',
            $order->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '06B6D4']]],
        ];
    }
}
