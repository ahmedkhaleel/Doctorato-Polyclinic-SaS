<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Admin/secretary with wildcard or patients.view can list all patients.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->hasPermission('patients.view');
    }

    /**
     * Admin/secretary can view any patient.
     * Doctor can view patients who have visits with them.
     * Patient can only view own profile.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($user->role?->hasPermission('patients.view')) {
            return true;
        }

        // Doctor: can view patients they have treated
        if ($user->hasRole('doctor') && $user->doctor) {
            return $patient->visits()->where('doctor_id', $user->doctor->id)->exists();
        }

        // Patient portal: own profile only
        return $user->patient?->id === $patient->id;
    }

    public function create(User $user): bool
    {
        return $user->role?->hasPermission('patients.create');
    }

    public function update(User $user, Patient $patient): bool
    {
        if ($user->role?->hasPermission('patients.update')) {
            return true;
        }

        // Patient can update own profile
        return $user->patient?->id === $patient->id;
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->role?->hasPermission('patients.delete');
    }

    /**
     * View sensitive medical data (allergies, chronic conditions, medications).
     */
    public function viewSensitive(User $user, Patient $patient): bool
    {
        // Doctors treating this patient can view sensitive data
        if ($user->hasRole('doctor') && $user->doctor) {
            return $patient->visits()->where('doctor_id', $user->doctor->id)->exists();
        }

        return $user->role?->hasPermission('patients.view_sensitive_medical');
    }

    /**
     * Update sensitive medical data.
     */
    public function updateSensitive(User $user, Patient $patient): bool
    {
        return $user->role?->hasPermission('patients.update_sensitive_medical');
    }
}
