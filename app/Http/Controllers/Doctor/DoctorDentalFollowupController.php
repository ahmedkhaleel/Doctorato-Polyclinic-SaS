<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DentalScheduledFollowup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDentalFollowupController extends BaseDoctorController
{
    /**
     * List doctor's dental follow-ups with filtering.
     */
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = DentalScheduledFollowup::with([
            'patient:id,full_name,file_number,phone',
            'treatment:id,treatment_type,tooth_number',
        ])->where('doctor_id', $doctorId);

        // Filter by status
        $statusFilter = $request->input('status', 'pending');
        if ($statusFilter === 'pending') {
            $query->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id');
        } elseif ($statusFilter === 'overdue') {
            $query->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->where('scheduled_date', '<', today());
        } elseif ($statusFilter === 'booked') {
            $query->where('status', DentalScheduledFollowup::STATUS_BOOKING_CREATED);
        } elseif ($statusFilter === 'completed') {
            $query->where('status', DentalScheduledFollowup::STATUS_COMPLETED);
        } elseif ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Search by patient
        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('file_number', 'like', "%{$request->search}%");
            });
        }

        $followups = $query->orderBy('scheduled_date')->paginate(20)->withQueryString();

        $stats = [
            'pending' => DentalScheduledFollowup::where('doctor_id', $doctorId)
                ->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')->count(),
            'overdue' => DentalScheduledFollowup::where('doctor_id', $doctorId)
                ->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->where('scheduled_date', '<', today())->count(),
            'this_week' => DentalScheduledFollowup::where('doctor_id', $doctorId)
                ->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->whereBetween('scheduled_date', [today(), today()->addDays(7)])->count(),
            'completed_month' => DentalScheduledFollowup::where('doctor_id', $doctorId)
                ->where('status', DentalScheduledFollowup::STATUS_COMPLETED)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)->count(),
        ];

        return Inertia::render('Doctor/Dental/Followups/Index', [
            'followups' => $followups,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
        ]);
    }
}
