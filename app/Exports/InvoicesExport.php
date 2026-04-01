<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFrom;
    protected $dateTo;
    protected $status;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null, ?string $status = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
    }

    public function query()
    {
        $query = Invoice::with(['patient', 'creator'])->orderBy('created_at', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('invoice_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('invoice_date', '<=', $this->dateTo);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Invoice #', 'Date', 'Patient', 'Subtotal', 'Discount', 'Tax', 'Total', 'Paid', 'Balance', 'Status', 'Created By'];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : '-',
            $invoice->patient?->full_name ?? '-',
            number_format($invoice->subtotal, 2),
            number_format($invoice->discount_amount, 2),
            number_format($invoice->tax_amount, 2),
            number_format($invoice->total, 2),
            number_format($invoice->paid_amount, 2),
            number_format($invoice->total - $invoice->paid_amount, 2),
            ucfirst($invoice->status),
            $invoice->creator?->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']]],
        ];
    }
}
