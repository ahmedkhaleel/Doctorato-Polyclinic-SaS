<?php

namespace App\Listeners;

use App\Events\PatientRegistered;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LinkPendingBookings implements ShouldQueue
{
    /**
     * When a new patient is registered, link any unlinked bookings
     * that match their phone or email (from website bookings).
     */
    public function handle(PatientRegistered $event): void
    {
        $patient = $event->patient;

        $linkedCount = Booking::whereNull('patient_id')
            ->where(function ($q) use ($patient) {
                $q->where('phone', $patient->phone);
                if ($patient->email) {
                    $q->orWhere('email', $patient->email);
                }
            })
            ->update(['patient_id' => $patient->id]);

        if ($linkedCount > 0) {
            Log::info("Linked {$linkedCount} pending booking(s) to new patient", [
                'patient_id' => $patient->id,
                'file_number' => $patient->file_number,
            ]);
        }
    }
}
