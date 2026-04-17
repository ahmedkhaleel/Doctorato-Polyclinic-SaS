<?php

use App\Models\OnlineConsultation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('online-consultation.{consultationId}', function ($user, $consultationId) {
    $consultation = OnlineConsultation::find($consultationId);
    if (!$consultation) {
        return false;
    }

    if ($user->doctor && $user->doctor->id === $consultation->doctor_id) {
        return true;
    }
    if ($user->patient && $user->patient->id === $consultation->patient_id) {
        return true;
    }

    return false;
});
