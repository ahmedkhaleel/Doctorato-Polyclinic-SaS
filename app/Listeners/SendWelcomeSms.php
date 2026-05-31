<?php

namespace App\Listeners;

use App\Events\PatientRegistered;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeSms implements ShouldQueue
{
    public function handle(PatientRegistered $event): void
    {
        $patient = $event->patient;

        if (! $patient->phone) {
            return;
        }

        $clinicName = Setting::get('clinic_name_ar', 'عيادة دكتوراتو');

        $message = "أهلاً وسهلاً {$patient->full_name}!\n"
            ."تم تسجيلك بنجاح في {$clinicName}.\n"
            ."رقم ملفك: {$patient->file_number}\n"
            .'نتمنى لكم دوام الصحة والعافية.';

        // account.created is transactional → always sends via the hub.
        Notifier::event('account.created', $patient, [
            'to' => $patient->phone,
            'body' => $message,
            'name' => $patient->full_name,
            'clinic_name' => $clinicName,
        ]);
    }
}
