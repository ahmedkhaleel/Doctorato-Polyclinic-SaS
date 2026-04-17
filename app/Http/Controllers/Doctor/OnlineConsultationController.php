<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\OnlineConsultation;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnlineConsultationController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $request->user()->doctor;
        if (!$doctor) abort(403);

        $consultations = OnlineConsultation::forDoctor($doctor->id)
            ->with('patient:id,full_name,phone,photo,date_of_birth,gender')
            ->orderByDesc('scheduled_date')
            ->orderByDesc('start_time')
            ->paginate(15);

        return Inertia::render('Doctor/OnlineConsultations/Index', [
            'consultations' => $consultations,
            'joinWindowMinutes' => (int) Setting::get('telemedicine_join_window_minutes', 15),
        ]);
    }

    public function room(Request $request, OnlineConsultation $consultation)
    {
        if ($consultation->doctor_id !== $request->user()->doctor?->id) abort(403);

        return Inertia::render('Doctor/OnlineConsultations/Room', [
            'consultationId' => $consultation->id,
            'role' => 'doctor',
        ]);
    }
}
