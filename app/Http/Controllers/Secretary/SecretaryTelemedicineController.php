<?php

namespace App\Http\Controllers\Secretary;

use App\Models\OnlineConsultation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P1-3 — front-desk view for telemedicine. ADMINISTRATIVE ONLY: upcoming online
 * consultations and a payment-chase queue (unpaid upcoming consultations). The
 * reception desk schedules and follows up on payment; it never opens the video
 * room or sees consultation clinical notes.
 */
class SecretaryTelemedicineController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $base = fn () => OnlineConsultation::query()
            ->with(['patient:id,full_name,phone,file_number', 'doctor:id,name_ar,name_en'])
            ->when($search !== '', fn ($q) => $q->whereHas('patient', fn ($pq) => $pq
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")));

        $row = fn (OnlineConsultation $c) => [
            'id' => $c->id,
            'consultation_number' => $c->consultation_number,
            'patient' => $c->patient
                ? ['id' => $c->patient->id, 'full_name' => $c->patient->full_name, 'phone' => $c->patient->phone, 'file_number' => $c->patient->file_number]
                : null,
            'doctor' => $c->doctor ? ['name_ar' => $c->doctor->name_ar, 'name_en' => $c->doctor->name_en] : null,
            'date' => optional($c->scheduled_date)->toDateString(),
            'start_time' => $c->start_time,
            'status' => $c->status,
            'payment_status' => $c->payment_status,
            'fee' => (float) $c->fee,
        ];

        $upcoming = $base()->upcoming()
            ->orderBy('scheduled_date')->orderBy('start_time')
            ->limit(100)->get()->map($row);

        // Payment-chase queue: upcoming consultations not yet paid.
        $unpaid = $base()
            ->whereDate('scheduled_date', '>=', today())
            ->whereNotIn('status', ['cancelled', 'refunded', 'completed'])
            ->where('payment_status', '!=', 'paid')
            ->orderBy('scheduled_date')
            ->limit(50)->get()->map($row);

        return Inertia::render('Secretary/Telemedicine/Index', [
            'upcoming' => $upcoming,
            'unpaid' => $unpaid,
            'filters' => ['search' => $search],
        ]);
    }
}
