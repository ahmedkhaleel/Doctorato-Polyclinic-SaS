<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Branch\BranchReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cross-branch comparison dashboard (B6). Only meaningful with >1 branch.
 * Permission-gated by reports.view (route).
 */
class BranchComparisonController extends Controller
{
    public function index(Request $request, BranchReportService $service): Response
    {
        $data = $service->comparison($request->query('from'), $request->query('to'));

        return Inertia::render('Admin/Reports/BranchComparison', [
            'report' => $data,
            'branchesEnabled' => (bool) config('branches.enabled'),
        ]);
    }
}
