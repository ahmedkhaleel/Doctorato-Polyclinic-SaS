<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

abstract class BasePatientController extends Controller
{
    protected function patient(Request $request): Patient
    {
        $patient = $request->user()->patient;

        if (! $patient) {
            abort(403, 'No patient profile is linked to your account.');
        }

        return $patient;
    }

    protected function patientId(Request $request): int
    {
        return $this->patient($request)->id;
    }

    protected function authorizePatient(Request $request, $model): void
    {
        if ($model->patient_id !== $this->patientId($request)) {
            abort(403, 'This record does not belong to you.');
        }
    }
}
