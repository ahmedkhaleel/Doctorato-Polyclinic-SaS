<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->hasPermission('prescriptions.view');
    }

    /**
     * Admin can view any prescription.
     * Doctor can view prescriptions they wrote.
     * Patient can view own prescriptions.
     */
    public function view(User $user, Prescription $prescription): bool
    {
        if ($user->role?->hasPermission('prescriptions.view')) {
            return true;
        }

        if ($user->hasRole('doctor') && $user->doctor) {
            return $prescription->doctor_id === $user->doctor->id;
        }

        return $user->patient?->id === $prescription->patient_id;
    }

    /**
     * Only doctors can create prescriptions.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('doctor') && $user->doctor) {
            return true;
        }

        return $user->role?->hasPermission('prescriptions.create');
    }

    /**
     * Doctor can only update their own prescriptions.
     */
    public function update(User $user, Prescription $prescription): bool
    {
        if ($user->role?->hasPermission('prescriptions.update')) {
            return true;
        }

        if ($user->hasRole('doctor') && $user->doctor) {
            return $prescription->doctor_id === $user->doctor->id;
        }

        return false;
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        if ($user->role?->hasPermission('prescriptions.delete')) {
            return true;
        }

        // Doctor can delete own prescriptions only if not yet dispensed
        if ($user->hasRole('doctor') && $user->doctor) {
            return $prescription->doctor_id === $user->doctor->id;
        }

        return false;
    }
}
