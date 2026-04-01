<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

abstract class BaseDoctorController extends Controller
{
    /**
     * Get the authenticated doctor model.
     */
    protected function doctor(Request $request): Doctor
    {
        return $request->user()->doctor;
    }

    /**
     * Get the authenticated doctor's ID.
     */
    protected function doctorId(Request $request): int
    {
        return $this->doctor($request)->id;
    }

    /**
     * Ensure a visit belongs to this doctor, or abort.
     */
    protected function authorizeDoctor(Request $request, $model): void
    {
        if ($model->doctor_id !== $this->doctorId($request)) {
            abort(403, 'This record does not belong to you.');
        }
    }
}
