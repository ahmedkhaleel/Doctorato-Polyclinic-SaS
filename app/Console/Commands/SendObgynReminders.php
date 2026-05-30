<?php

namespace App\Console\Commands;

use App\Jobs\SendSmsJob;
use App\Models\AntenatalVisit;
use App\Models\PapSmearScreening;
use App\Models\Pregnancy;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * OB/GYN SMS reminders. Uses an exact-day window so each record fires once
 * (no per-row sent flag / migration needed):
 *   - anc: antenatal visits whose next_visit_date is `anc_reminder_days` away
 *   - edd: active pregnancies whose EDD is `edd_alert_days` away
 *   - pap: pap smears whose next_due_date is today (recall due)
 *
 * Cadence comes from the obgyn module_settings; respects the patient's SMS
 * reminder consent and the global sms_enabled switch.
 */
class SendObgynReminders extends Command
{
    protected $signature = 'obgyn:reminders
                            {--type=anc : anc | edd | pap}
                            {--dry-run : Show what would be sent without sending}';

    protected $description = 'Send OB/GYN SMS reminders (antenatal visits, EDD approaching, pap recall)';

    public function handle(): int
    {
        if (Setting::get('sms_enabled', '0') !== '1') {
            $this->warn('SMS is not enabled. Skipping OB/GYN reminders.');

            return self::SUCCESS;
        }

        $type = $this->option('type');
        $dry = (bool) $this->option('dry-run');

        $sent = match ($type) {
            'anc' => $this->ancReminders($dry),
            'edd' => $this->eddAlerts($dry),
            'pap' => $this->papRecalls($dry),
            default => -1,
        };

        if ($sent < 0) {
            $this->error('Invalid type. Use: anc | edd | pap');

            return self::FAILURE;
        }

        $this->info(($dry ? '[DRY RUN] Would send ' : 'Sent ')."{$sent} obgyn {$type} reminder(s).");

        return self::SUCCESS;
    }

    private function setting(string $key, int $default): int
    {
        $v = DB::table('module_settings')->where('module', 'obgyn')->where('key', $key)->value('value');

        return is_numeric($v) ? (int) $v : $default;
    }

    private function ancReminders(bool $dry): int
    {
        $target = today()->addDays($this->setting('anc_reminder_days', 2))->toDateString();
        $clinic = Setting::get('clinic_name', 'Doctorato Polyclinic');

        $visits = AntenatalVisit::whereDate('next_visit_date', $target)
            ->with('pregnancy.patient:id,full_name,phone,notify_sms_reminders')
            ->get();

        $sent = 0;
        foreach ($visits as $v) {
            $patient = $v->pregnancy?->patient;
            if (! $this->canSms($patient)) {
                continue;
            }
            $date = \Illuminate\Support\Carbon::parse($v->next_visit_date)->format('d/m/Y');
            $msg = "تذكير من {$clinic}: موعد متابعة الحمل بتاريخ {$date}. نتمنى لكِ الحضور في الموعد.";
            $this->fire($patient->phone, $msg, "obgyn_anc:{$v->id}", $dry);
            $sent++;
        }

        return $sent;
    }

    private function eddAlerts(bool $dry): int
    {
        $target = today()->addDays($this->setting('edd_alert_days', 14))->toDateString();
        $clinic = Setting::get('clinic_name', 'Doctorato Polyclinic');

        $pregnancies = Pregnancy::active()->whereDate('edd', $target)
            ->with('patient:id,full_name,phone,notify_sms_reminders')
            ->get();

        $sent = 0;
        foreach ($pregnancies as $p) {
            if (! $this->canSms($p->patient)) {
                continue;
            }
            $date = \Illuminate\Support\Carbon::parse($p->edd)->format('d/m/Y');
            $msg = "تذكير من {$clinic}: موعد الولادة المتوقّع قريب ({$date}). يُرجى التواصل لمتابعة الاستعدادات.";
            $this->fire($p->patient->phone, $msg, "obgyn_edd:{$p->id}", $dry);
            $sent++;
        }

        return $sent;
    }

    private function papRecalls(bool $dry): int
    {
        $clinic = Setting::get('clinic_name', 'Doctorato Polyclinic');

        $paps = PapSmearScreening::whereDate('next_due_date', today()->toDateString())
            ->with('patient:id,full_name,phone,notify_sms_reminders')
            ->get();

        $sent = 0;
        foreach ($paps as $pap) {
            if (! $this->canSms($pap->patient)) {
                continue;
            }
            $msg = "تذكير من {$clinic}: حان موعد تجديد فحص مسحة عنق الرحم. يُرجى حجز موعد.";
            $this->fire($pap->patient->phone, $msg, "obgyn_pap:{$pap->id}", $dry);
            $sent++;
        }

        return $sent;
    }

    private function canSms($patient): bool
    {
        return $patient
            && $patient->phone
            && ($patient->notify_sms_reminders ?? true);
    }

    private function fire(string $phone, string $message, string $key, bool $dry): void
    {
        if ($dry) {
            $this->line("  [DRY] -> {$phone}: {$message}");

            return;
        }
        SendSmsJob::dispatch($phone, $message, null, $key);
    }
}
