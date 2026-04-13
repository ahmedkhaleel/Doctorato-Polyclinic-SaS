<?php

namespace App\Exports;

use App\Models\Supply;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuppliesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?string $search;
    protected ?string $module;
    protected ?string $stock;
    protected ?int $categoryId;

    public function __construct(?string $search = null, ?string $module = null, ?string $stock = null, ?int $categoryId = null)
    {
        $this->search = $search;
        $this->module = $module;
        $this->stock = $stock;
        $this->categoryId = $categoryId;
    }

    public function query()
    {
        $query = Supply::with('supplyCategory')->orderBy('name_en');

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%{$s}%")
                    ->orWhere('name_en', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($this->module && $this->module !== 'all') {
            $query->forModule($this->module);
        }

        if ($this->stock === 'low') {
            $query->lowStock();
        } elseif ($this->stock === 'expired') {
            $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
        } elseif ($this->stock === 'expiring') {
            $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>=', now());
        }

        if ($this->categoryId) {
            $query->where('supply_category_id', $this->categoryId);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'SKU', 'Name (EN)', 'Name (AR)', 'Module', 'Category',
            'Unit', 'Quantity', 'Min Qty', 'Purchase Price',
            'Supplier', 'Batch #', 'Expiry Date', 'Status',
        ];
    }

    public function map($supply): array
    {
        return [
            $supply->sku ?? '-',
            $supply->name_en,
            $supply->name_ar,
            ucfirst($supply->module ?? 'shared'),
            $supply->supplyCategory?->name_en ?? ($supply->category ?? '-'),
            $supply->unit ?? '-',
            $supply->quantity,
            $supply->min_quantity,
            $supply->purchase_price ?? 0,
            $supply->supplier ?? '-',
            $supply->batch_number ?? '-',
            $supply->expiry_date?->format('d/m/Y') ?? '-',
            $supply->is_active ? 'Active' : 'Inactive',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6366F1']],
            ],
        ];
    }
}
