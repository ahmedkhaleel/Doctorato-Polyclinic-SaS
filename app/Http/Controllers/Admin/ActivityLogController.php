<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ActivityLog::with('user:id,name')
            ->orderBy('created_at', 'desc');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // User filter
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // Action filter
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        // Panel filter
        if ($panel = $request->input('panel')) {
            $query->forPanel($panel);
        }

        // Date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->paginate(30)->withQueryString();

        // Transform model_type to short class name
        $logs->getCollection()->transform(function ($log) {
            $log->model_type_short = $log->model_type ? class_basename($log->model_type) : null;
            $log->user_name = $log->user?->name;
            unset($log->user);

            return $log;
        });

        // Get distinct actions for filter dropdown
        $actions = ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        // Get users for filter dropdown
        $users = User::select('id', 'name')->orderBy('name')->get();

        // Available panels
        $panels = ['admin', 'secretary', 'doctor', 'webmaster'];

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'actions' => $actions,
            'users' => $users,
            'panels' => $panels,
            'filters' => [
                'search' => $request->input('search', ''),
                'user_id' => $request->input('user_id', ''),
                'action' => $request->input('action', ''),
                'panel' => $request->input('panel', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }
}
