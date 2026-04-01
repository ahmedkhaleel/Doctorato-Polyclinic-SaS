<?php

namespace App\Listeners;

use App\Events\PatientRegistered;
use App\Jobs\SendSmsJob;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeSms implements ShouldQueue
{
    public function handle(PatientRegistered $event): void
    {
        if (Setting::get('sms_enabled') !== '1') {
            return;
        }

        $patient = $event->patient;

        if (! $patient->phone) {
            return;
        }

        $clinicName = Setting::get('clinic_name_ar', 'عيادة أورا ديرما');

        $message = "أهلاً وسهلاً {$patient->full_name}!\n"
            . "تم تسجيلك بنجاح في {$clinicName}.\n"
            . "رقم ملفك: {$patient->file_number}\n"
            . "نتمنى لكم دوام الصحة والعافية.";

        SendSmsJob::dispatch($patient->phone, $message);
    }
}
