<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFrom;
    protected $dateTo;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function query()
    {
        $query = Payment::with(['patient', 'invoice', 'paymentMethod', 'receiver'])->orderBy('payment_date', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('payment_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('payment_date', '<=', $this->dateTo);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Date', 'Patient', 'Invoice #', 'Amount', 'Method', 'Reference', 'Received By', 'Notes'];
    }

    public function map($payment): array
    {
        return [
            $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '-',
            $payment->patient?->full_name ?? '-',
            $payment->invoice?->invoice_number ?? '-',
            number_format($payment->amount, 2),
            $payment->paymentMethod?->name_en ?? '-',
            $payment->reference_number ?? '-',
            $payment->receiver?->name ?? '-',
            $payment->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']]],
        ];
    }
}
