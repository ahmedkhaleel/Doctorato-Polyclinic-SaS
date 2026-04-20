<?php

namespace App\Console\Commands;

use App\Jobs\SendSmsJob;
use App\Models\PediatricVaccination;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPediatricVaccinationReminders extends Command
{
    protected $signature = 'pediatric:vaccination-reminders
                            {--type=upcoming : Type: upcoming (scheduled soon) or overdue (missed)}
                            {--days=7 : Days ahead to check for upcoming, or days overdue}
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send SMS reminders for upcoming or overdue pediatric vaccinations';

    public function handle(): int
    {
        if (Setting::get('sms_enabled', '0') !== '1') {
            $this->warn('SMS is not enabled. Skipping vaccination reminders.');
            return self::SUCCESS;
        }

        $type = $this->option('type');
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $sent = 0;

        if ($type === 'upcoming') {
            $sent = $this->sendUpcomingReminders($days, $dryRun);
        } elseif ($type === 'overdue') {
            $sent = $this->sendOverdueReminders($days, $dryRun);
        } else {
            $this->error('Invalid type. Use: upcoming or overdue');
            return self::FAILURE;
        }

        $prefix = $dryRun ? '[DRY RUN] Would send' : 'Sent';
        $this->info("{$prefix} {$sent} vaccination {$type} reminders.");

        return self::SUCCESS;
    }

    /**
     * Send reminders for vaccinations scheduled within the next N days.
     */
    protected function sendUpcomingReminders(int $days, bool $dryRun): int
    {
        $from = today();
        $to = today()->addDays($days);
        $maxPerRun = (int) Setting::get('sms_pediatric_max_per_day', '100');

        $vaccinations = PediatricVaccination::where('status', 'scheduled')
            ->whereBetween('scheduled_date', [$from, $to])
            ->with('patient:id,full_name,phone,guardian_name,guardian_phone')
            ->limit($maxPerRun)
            ->get();

        $sent = 0;
        $clinicName = Setting::get('clinic_name', 'Doctorato Polyclinic');

        foreach ($vaccinations as $vax) {
            $patient = $vax->patient;
            if (!$patient) continue;

            $phone = $patient->guardian_phone ?: $patient->phone;
            if (!$phone) continue;

            // Skip if reminder already sent recently (dedup via notes field)
            if ($vax->reminder_sent_at && $vax->reminder_sent_at->isToday()) continue;

            $childName = $patient->full_name ?? 'طفلكم';
            $vaccineName = $vax->vaccine_name_ar ?: $vax->vaccine_name;
            $date = $vax->scheduled_date ? $vax->scheduled_date->format('d/m/Y') : '-';

            $message = "تذكير من {$clinicName}: موعد تطعيم {$childName} ({$vaccineName} - {$vax->dose_number}) بتاريخ {$date}. يرجى الحضور في الموعد المحدد.";

            if ($dryRun) {
                $this->line("  [DRY] -> {$phone}: {$message}");
            } else {
                SendSmsJob::dispatch($phone, $message, null, "pediatric_vax_reminder:{$vax->id}");
                // Mark reminder sent
                $vax->update(['reminder_sent_at' => now()]);
            }

            $sent++;
        }

        return $sent;
    }

    /**
     * Send reminders for overdue (missed) vaccinations.
     */
    protected function sendOverdueReminders(int $days, bool $dryRun): int
    {
        $cutoff = today()->subDays($days);
        $maxPerRun = (int) Setting::get('sms_pediatric_max_per_day', '100');

        // Get missed vaccinations OR scheduled ones that are past their date
        $vaccinations = PediatricVaccination::where(function ($q) use ($cutoff) {
                $q->where('status', 'missed')
                  ->orWhere(function ($sq) use ($cutoff) {
                      $sq->where('status', 'scheduled')
                         ->where('scheduled_date', '<', today());
                  });
            })
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('overdue_reminder_sent_at')
                  ->orWhere('overdue_reminder_sent_at', '<', today()->subDays(14)); // Don't spam — max once per 2 weeks
            })
            ->with('patient:id,full_name,phone,guardian_name,guardian_phone')
            ->limit($maxPerRun)
            ->get();

        $sent = 0;
        $clinicName = Setting::get('clinic_name', 'Doctorato Polyclinic');

        foreach ($vaccinations as $vax) {
            $patient = $vax->patient;
            if (!$patient) continue;

            $phone = $patient->guardian_phone ?: $patient->phone;
            if (!$phone) continue;

            $childName = $patient->full_name ?? 'طفلكم';
            $vaccineName = $vax->vaccine_name_ar ?: $vax->vaccine_name;

            $message = "تنبيه من {$clinicName}: تطعيم {$childName} ({$vaccineName} - {$vax->dose_number}) متأخر عن موعده. يرجى حجز موعد في أقرب وقت لحماية صحة طفلكم.";

            if ($dryRun) {
                $this->line("  [DRY] -> {$phone}: {$message}");
            } else {
                SendSmsJob::dispatch($phone, $message, null, "pediatric_vax_overdue:{$vax->id}");
                $vax->update(['overdue_reminder_sent_at' => now()]);
            }

            $sent++;

            // Auto-update status to missed if still scheduled
            if (!$dryRun && $vax->status === 'scheduled' && $vax->scheduled_date && $vax->scheduled_date->isPast()) {
                $vax->update(['status' => 'missed']);
            }
        }

        return $sent;
    }
}
