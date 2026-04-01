<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->hasPermission('bookings.view');
    }

    /**
     * Admin/secretary can view any booking.
     * Doctor can view bookings assigned to them.
     * Patient can view own bookings.
     */
    public function view(User $user, Booking $booking): bool
    {
        if ($user->role?->hasPermission('bookings.view')) {
            return true;
        }

        if ($user->hasRole('doctor') && $user->doctor) {
            return $booking->doctor_id === $user->doctor->id;
        }

        return $user->patient?->id === $booking->patient_id;
    }

    public function create(User $user): bool
    {
        return $user->role?->hasPermission('bookings.create');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->role?->hasPermission('bookings.update');
    }

    /**
     * Edit booking services (add/remove/modify).
     */
    public function editServices(User $user, Booking $booking): bool
    {
        return $user->role?->hasPermission('bookings.edit_services');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->role?->hasPermission('bookings.delete');
    }

    public function export(User $user): bool
    {
        return $user->role?->hasPermission('bookings.export');
    }
}
