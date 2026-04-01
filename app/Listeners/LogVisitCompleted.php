<?php

namespace App\Listeners;

use App\Events\VisitCompleted;
use Illuminate\Support\Facades\Log;

class LogVisitCompleted
{
    public function handle(VisitCompleted $event): void
    {
        Log::info('Visit completed', [
            'visit_id' => $event->visit->id,
            'patient_id' => $event->visit->patient_id,
            'doctor_id' => $event->visit->doctor_id,
            'visit_type' => $event->visit->visit_type,
            'module' => $event->visit->module,
        ]);
    }
}
