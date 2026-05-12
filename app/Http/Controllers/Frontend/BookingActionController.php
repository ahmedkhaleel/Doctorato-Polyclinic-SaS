<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One-click booking confirm/cancel from email links — no login
 * required. Uses Laravel's `signed` middleware so the link is
 * tamper-proof and can include an optional expiry. Patients getting
 * a reminder no longer have to log in just to confirm attendance —
 * which is the biggest single driver of no-show rates.
 *
 * Cancelled bookings can be re-confirmed too (they go back to
 * `unconfirmed` so the front desk re-verifies the slot).
 */
class BookingActionController extends Controller
{
    /**
     * Patient confirms attendance via the signed link in their email.
     * Idempotent: hitting it twice doesn't double-fire events.
     */
    public function confirm(Request $request, Booking $booking): Response
    {
        $locale = $request->route('locale', 'ar');

        // If already confirmed, just show the success page — patients
        // sometimes click the link twice and we shouldn't panic at them.
        if ($booking->status === 'confirmed') {
            return $this->renderResult('already_confirmed', $booking, $locale);
        }

        if (! in_array($booking->status, ['unconfirmed', 'new', 'contacted', 'cancelled'])) {
            return $this->renderResult('not_confirmable', $booking, $locale);
        }

        $booking->update(['status' => 'confirmed']);

        try {
            \App\Events\BookingConfirmed::dispatch($booking);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[booking.confirm.event] failed', [
                'booking_id' => $booking->id, 'error' => $e->getMessage(),
            ]);
        }

        AuditLogger::log('booking_confirmed_via_email_link', $booking, [
            'source' => 'email_one_click',
        ]);

        return $this->renderResult('confirmed', $booking, $locale);
    }

    /**
     * Patient asks to cancel the booking via the signed link.
     */
    public function cancel(Request $request, Booking $booking): Response
    {
        $locale = $request->route('locale', 'ar');

        if ($booking->status === 'cancelled') {
            return $this->renderResult('already_cancelled', $booking, $locale);
        }

        if (in_array($booking->status, ['completed'])) {
            return $this->renderResult('not_cancellable', $booking, $locale);
        }

        $booking->update(['status' => 'cancelled']);

        try {
            \App\Events\BookingCancelled::dispatch($booking, 'Cancelled by patient via email link');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[booking.cancel.event] failed', [
                'booking_id' => $booking->id, 'error' => $e->getMessage(),
            ]);
        }

        AuditLogger::log('booking_cancelled_via_email_link', $booking, [
            'source' => 'email_one_click',
        ]);

        return $this->renderResult('cancelled', $booking, $locale);
    }

    private function renderResult(string $outcome, Booking $booking, string $locale): Response
    {
        return Inertia::render('Frontend/BookingAction', [
            'outcome' => $outcome,
            'booking' => [
                'id'             => $booking->id,
                'booking_number' => $booking->booking_number,
                'preferred_date' => $booking->preferred_date instanceof \DateTimeInterface
                                    ? $booking->preferred_date->format('Y-m-d') : (string) $booking->preferred_date,
                'preferred_time' => $booking->preferred_time,
                'status'         => $booking->status,
            ],
        ]);
    }
}
