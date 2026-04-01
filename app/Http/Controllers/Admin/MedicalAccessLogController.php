<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalDataAccessLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicalAccessLogController extends Controller
{
    /**
     * Display medical data access audit trail.
     * Only accessible by users with patients.view_sensitive_medical permission.
     */
    public function index(Request $request): Response
    {
        $query = MedicalDataAccessLog::with([
            'user:id,name',
            'patient:id,full_name,file_number',
        ]);

        // Filters
        if ($patientId = $request->input('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($accessType = $request->input('access_type')) {
            $query->where('access_type', $accessType);
        }

        if ($category = $request->input('data_category')) {
            $query->where('data_category', $category);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        // Summary stats
        $stats = [
            'total_today' => MedicalDataAccessLog::whereDate('created_at', today())->count(),
            'sensitive_today' => MedicalDataAccessLog::sensitive()->whereDate('created_at', today())->count(),
            'unique_patients_accessed' => MedicalDataAccessLog::whereDate('created_at', today())->distinct('patient_id')->count('patient_id'),
            'updates_this_week' => MedicalDataAccessLog::where('access_type', 'update_medical')
                ->where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        return Inertia::render('Admin/MedicalAccessLogs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['patient_id', 'user_id', 'access_type', 'data_category', 'date_from', 'date_to']),
        ]);
    }
}
