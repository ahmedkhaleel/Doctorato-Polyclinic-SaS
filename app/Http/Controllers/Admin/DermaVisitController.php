<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DermaVisitController extends Controller
{
    public function index(Request $request)
    {
        $query = Visit::with(['patient:id,name,phone,file_number', 'doctor:id,name'])
            ->where('module', 'derma');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('patient', function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $visits = $query->orderByDesc('visit_date')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Derma/Visits', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status']),
        ]);
    }
}
