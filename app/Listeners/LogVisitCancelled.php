<?php

namespace App\Listeners;

use App\Events\VisitCancelled;
use Illuminate\Support\Facades\Log;

class LogVisitCancelled
{
    public function handle(VisitCancelled $event): void
    {
        Log::warning('Visit cancelled', [
            'visit_id' => $event->visit->id,
            'patient_id' => $event->visit->patient_id,
            'doctor_id' => $event->visit->doctor_id,
            'visit_type' => $event->visit->visit_type,
            'module' => $event->visit->module,
            'cancelled_at' => $event->visit->cancelled_at,
        ]);
    }
}
