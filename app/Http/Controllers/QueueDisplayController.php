<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QueueDisplayController extends Controller
{
    public function index()
    {
        $visits = Visit::with(['patient:id,full_name,file_number', 'service:id,name_en,name_ar', 'doctor:id,name_en,name_ar,specialization_en,photo'])
            ->today()
            ->whereIn('status', ['waiting', 'in_progress'])
            ->orderByRaw("FIELD(status, 'in_progress', 'waiting')")
            ->orderBy('created_at')
            ->get()
            ->groupBy('doctor_id');

        $doctors = Doctor::active()
            ->select('id', 'name_en', 'name_ar', 'specialization_en', 'photo')
            ->get();

        return Inertia::render('QueueDisplay', [
            'visitsByDoctor' => $visits,
            'doctors' => $doctors,
            'generatedAt' => now()->format('H:i:s'),
        ]);
    }

    /**
     * JSON API for auto-refresh polling (no full page reload).
     */
    public function data()
    {
        $visits = Visit::with(['patient:id,full_name,file_number', 'service:id,name_en,name_ar', 'doctor:id,name_en,name_ar,specialization_en,photo'])
            ->today()
            ->whereIn('status', ['waiting', 'in_progress'])
            ->orderByRaw("FIELD(status, 'in_progress', 'waiting')")
            ->orderBy('created_at')
            ->get()
            ->groupBy('doctor_id');

        $doctors = Doctor::active()
            ->select('id', 'name_en', 'name_ar', 'specialization_en', 'photo')
            ->get();

        return response()->json([
            'visitsByDoctor' => $visits,
            'doctors' => $doctors,
            'generatedAt' => now()->format('H:i:s'),
        ]);
    }
}
