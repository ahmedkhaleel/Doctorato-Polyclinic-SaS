<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OutcomeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * P2-2b — cross-specialty clinical-quality / outcomes dashboard for admins.
 * Aggregates real outcome metrics from existing clinical event tables.
 */
class QualityOutcomesController extends Controller
{
    public function __construct(private OutcomeService $outcomes) {}

    public function index(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('Admin/Quality/Outcomes', [
            'sections' => $this->outcomes->forEnabledModules($from, $to),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $sections = $this->outcomes->forEnabledModules($from, $to);

        $filename = 'clinical-outcomes-'.($from ?: 'all').'_'.($to ?: 'all').'.csv';

        return response()->streamDownload(function () use ($sections) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Module', 'Metric', 'Value', 'Note']);
            foreach ($sections as $s) {
                foreach ($s['metrics'] as $m) {
                    fputcsv($h, [$s['title_en'], $m['label_en'], $m['value'], $m['hint_en'] ?? '']);
                }
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array{0:?string,1:?string} */
    private function range(Request $request): array
    {
        $from = $request->input('from');
        $to = $request->input('to');

        return [
            $from && strtotime($from) ? date('Y-m-d', strtotime($from)) : null,
            $to && strtotime($to) ? date('Y-m-d', strtotime($to)) : null,
        ];
    }
}
