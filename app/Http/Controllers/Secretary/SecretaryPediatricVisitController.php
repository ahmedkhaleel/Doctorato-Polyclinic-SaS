<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecretaryPediatricVisitController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $visits = Visit::where('module', 'pediatric')
            ->with([
                'patient:id,full_name,date_of_birth,gender,file_number,guardian_name,guardian_phone,photo',
                'doctor:id,name_en,name_ar',
            ])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('file_number', 'like', "%{$search}%")
                       ->orWhere('guardian_name', 'like', "%{$search}%");
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('visit_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('visit_date', '<=', $dateTo))
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Secretary/Pediatric/Visits/Index', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function show(Visit $visit)
    {
        $visit->load([
            'patient',
            'doctor:id,name_en,name_ar',
            'prescriptions.items',
        ]);

        return Inertia::render('Secretary/Pediatric/Visits/Show', [
            'visit' => $visit,
        ]);
    }
}
