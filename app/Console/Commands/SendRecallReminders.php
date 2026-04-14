<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\PatientRecallReminder;
use App\Models\Setting;
use App\Models\Visit;
use App\Services\SmsNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendRecallReminders extends Command
{
    protected $signature = 'patients:send-recall-reminders
                            {--module=all : Module to check (dental, derma, all)}
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send recall SMS reminders to patients who haven\'t visited in a configurable period';

    public function handle(): int
    {
        if (Setting::get('sms_enabled', '0') !== '1') {
            $this->warn('SMS is not enabled. Skipping recall reminders.');
            return self::SUCCESS;
        }

        if (Setting::get('sms_recall_enabled', '0') !== '1') {
            $this->warn('Recall reminders are disabled in settings. Skipping.');
            return self::SUCCESS;
        }

        $module = $this->option('module');
        $dryRun = $this->option('dry-run');
        $totalSent = 0;

        if (in_array($module, ['dental', 'all'])) {
            $totalSent += $this->sendDentalRecalls($dryRun);
        }

        if (in_array($module, ['derma', 'all'])) {
            $totalSent += $this->sendDermaRecalls($dryRun);
        }

        $prefix = $dryRun ? '[DRY RUN] Would send' : 'Sent';
        $this->info("{$prefix} {$totalSent} recall reminders.");

        return self::SUCCESS;
    }

    protected function sendDentalRecalls(bool $dryRun): int
    {
        $months = (int) Setting::get('sms_recall_dental_months', '6');
        $maxPerDay = (int) Setting::get('sms_recall_max_per_day', '50');
        $recallTypes = ['checkup', 'cleaning'];

        $cutoffDate = today()->subMonths($months);

        // Find patients whose last dental visit was exactly $months ago (within a 7-day window)
        // This avoids spamming — only targets patients in the recall window
        $windowStart = today()->subMonths($months)->subDays(3);
        $windowEnd = today()->subMonths($months)->addDays(3);

        $patients = $this->getPatientsForRecall('dental', $windowStart, $windowEnd, $maxPerDay);

        $count = 0;

        foreach ($patients as $patient) {
            // Skip if already sent a recall for this period
            $alreadySent = PatientRecallReminder::where('patient_id', $patient->id)
                ->where('module', 'dental')
                ->where('reminder_sent_at', '>=', today()->subMonths($months)->subDays(30))
                ->exists();

            if ($alreadySent) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY] Dental recall: {$patient->full_name} ({$patient->phone}) - last visit: {$patient->last_dental_visit}");
                $count++;
                continue;
            }

            try {
                $result = SmsNotificationService::dentalRecallReminder($patient, 'checkup', $months);

                PatientRecallReminder::create([
                    'patient_id' => $patient->id,
                    'module' => 'dental',
                    'type' => 'checkup',
                    'last_visit_date' => $patient->last_dental_visit,
                    'reminder_sent_at' => now(),
                    'sms_status' => ($result['success'] ?? false) ? 'sent' : 'failed',
                    'notes' => $result['message'] ?? null,
                ]);

                if ($result['success'] ?? false) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error('Dental recall SMS exception', [
                    'patient_id' => $patient->id,
                    'error' => $e->getMessage(),
                ]);

                PatientRecallReminder::create([
                    'patient_id' => $patient->id,
                    'module' => 'dental',
                    'type' => 'checkup',
                    'last_visit_date' => $patient->last_dental_visit,
                    'reminder_sent_at' => now(),
                    'sms_status' => 'failed',
                    'notes' => $e->getMessage(),
                ]);
            }
        }

        $this->line("  Dental recall reminders: {$count}/{$patients->count()}");
        return $count;
    }

    protected function sendDermaRecalls(bool $dryRun): int
    {
        $months = (int) Setting::get('sms_recall_derma_months', '6');
        $maxPerDay = (int) Setting::get('sms_recall_max_per_day', '50');

        $windowStart = today()->subMonths($months)->subDays(3);
        $windowEnd = today()->subMonths($months)->addDays(3);

        $patients = $this->getPatientsForRecall('derma', $windowStart, $windowEnd, $maxPerDay);

        $count = 0;

        foreach ($patients as $patient) {
            $alreadySent = PatientRecallReminder::where('patient_id', $patient->id)
                ->where('module', 'derma')
                ->where('reminder_sent_at', '>=', today()->subMonths($months)->subDays(30))
                ->exists();

            if ($alreadySent) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY] Derma recall: {$patient->full_name} ({$patient->phone}) - last visit: {$patient->last_derma_visit}");
                $count++;
                continue;
            }

            try {
                $result = SmsNotificationService::dermaRecallReminder($patient, $months);

                PatientRecallReminder::create([
                    'patient_id' => $patient->id,
                    'module' => 'derma',
                    'type' => 'checkup',
                    'last_visit_date' => $patient->last_derma_visit,
                    'reminder_sent_at' => now(),
                    'sms_status' => ($result['success'] ?? false) ? 'sent' : 'failed',
                    'notes' => $result['message'] ?? null,
                ]);

                if ($result['success'] ?? false) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error('Derma recall SMS exception', [
                    'patient_id' => $patient->id,
                    'error' => $e->getMessage(),
                ]);

                PatientRecallReminder::create([
                    'patient_id' => $patient->id,
                    'module' => 'derma',
                    'type' => 'checkup',
                    'last_visit_date' => $patient->last_derma_visit,
                    'reminder_sent_at' => now(),
                    'sms_status' => 'failed',
                    'notes' => $e->getMessage(),
                ]);
            }
        }

        $this->line("  Derma recall reminders: {$count}/{$patients->count()}");
        return $count;
    }

    /**
     * Get patients whose last visit in a module falls within a date window.
     */
    protected function getPatientsForRecall(string $module, $windowStart, $windowEnd, int $limit): \Illuminate\Support\Collection
    {
        $moduleColumn = $module === 'dental' ? 'last_dental_visit' : 'last_derma_visit';

        // Subquery: get last visit date per patient per module
        $subQuery = Visit::select('patient_id', DB::raw('MAX(visit_date) as last_visit'))
            ->where('module', $module)
            ->whereIn('status', ['completed', 'checked_out'])
            ->groupBy('patient_id');

        return Patient::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->joinSub($subQuery, 'lv', function ($join) {
                $join->on('patients.id', '=', 'lv.patient_id');
            })
            ->whereBetween('lv.last_visit', [$windowStart, $windowEnd])
            // Exclude patients with upcoming bookings
            ->whereDoesntHave('bookings', function ($q) {
                $q->where('status', 'confirmed')
                    ->where('preferred_date', '>=', today());
            })
            ->select('patients.*', "lv.last_visit as {$moduleColumn}")
            ->limit($limit)
            ->get();
    }
}
