<?php

namespace App\Listeners;

use App\Events\VisitCompleted;
use App\Services\SmsNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendVisitCompletedSms implements ShouldQueue
{
    public function handle(VisitCompleted $event): void
    {
        SmsNotificationService::visitCompleted($event->visit);
    }
}
