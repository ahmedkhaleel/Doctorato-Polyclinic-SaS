<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFrom;
    protected $dateTo;
    protected $userId;
    protected $action;
    protected $panel;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null, ?int $userId = null, ?string $action = null, ?string $panel = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->userId = $userId;
        $this->action = $action;
        $this->panel = $panel;
    }

    public function query()
    {
        $query = ActivityLog::with('user:id,name')
            ->orderBy('created_at', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }
        if ($this->action) {
            $query->where('action', $this->action);
        }
        if ($this->panel) {
            $query->forPanel($this->panel);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Date/Time', 'User', 'Panel', 'Action', 'Description', 'Model Type', 'Model ID', 'IP Address', 'User Agent'];
    }

    public function map($log): array
    {
        $modelType = $log->model_type ? class_basename($log->model_type) : '-';

        return [
            $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-',
            $log->user?->name ?? 'System',
            ucfirst($log->panel ?? '-'),
            ucfirst(str_replace('_', ' ', $log->action ?? '-')),
            $log->description ?? '-',
            $modelType,
            $log->model_id ?? '-',
            $log->ip_address ?? '-',
            $log->user_agent ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C4A265']]],
        ];
    }
}
