<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->hasPermission('visits.view');
    }

    /**
     * Admin/secretary can view any visit.
     * Doctor can only view their own visits.
     * Patient can view own visits.
     */
    public function view(User $user, Visit $visit): bool
    {
        if ($user->role?->hasPermission('visits.view')) {
            return true;
        }

        if ($user->hasRole('doctor') && $user->doctor) {
            return $visit->doctor_id === $user->doctor->id;
        }

        return $user->patient?->id === $visit->patient_id;
    }

    public function create(User $user): bool
    {
        return $user->role?->hasPermission('visits.create');
    }

    /**
     * Admin can update any visit.
     * Doctor can only update their own visits that are not cancelled.
     */
    public function update(User $user, Visit $visit): bool
    {
        if ($user->role?->hasPermission('visits.update')) {
            return true;
        }

        if ($user->hasRole('doctor') && $user->doctor) {
            return $visit->doctor_id === $user->doctor->id
                && $visit->status !== 'cancelled';
        }

        return false;
    }

    /**
     * Only admin can delete/cancel visits.
     */
    public function delete(User $user, Visit $visit): bool
    {
        return $user->role?->hasPermission('visits.delete');
    }

    /**
     * Complete a visit — doctor must own the visit.
     */
    public function complete(User $user, Visit $visit): bool
    {
        if ($user->role?->hasPermission('visits.update')) {
            return true;
        }

        if ($user->hasRole('doctor') && $user->doctor) {
            return $visit->doctor_id === $user->doctor->id
                && in_array($visit->status, ['waiting', 'in_progress']);
        }

        return false;
    }
}
