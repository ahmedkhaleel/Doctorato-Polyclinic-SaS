<?php

namespace App\Console\Commands;

use App\Models\OnlineConsultation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Booking abandonment recovery — emails patients who started an online
 * consultation booking but never completed the payment.
 *
 * Targets: payment_status='pending' AND created_at older than --min-age
 * (default 30 min) AND newer than --max-age (default 12 h) AND haven't
 * already received a recovery email.
 *
 * Why a window?
 *   - <30 min: too soon, the patient might still be at the checkout
 *   - >12 h: telemedicine:cleanup-stuck will reclaim the slot anyway,
 *     so a recovery email at that point is misleading
 *
 * Schedule: every 30 minutes — close to real-time recovery without
 * spamming.
 */
class SendConsultationRecoveryCommand extends Command
{
    protected $signature = 'telemedicine:send-recovery-emails
                            {--min-age=30 : Only target consultations older than this many minutes}
                            {--max-age=720 : Skip consultations older than this (default 12 h)}
                            {--dry-run : Preview the affected rows without sending}';

    protected $description = 'Email patients who started an online consultation but did not pay';

    public function handle(): int
    {
        $minAge = (int) $this->option('min-age');
        $maxAge = (int) $this->option('max-age');

        $candidates = OnlineConsultation::where('payment_status', 'pending')
            ->whereNull('recovery_email_sent_at')
            ->whereBetween('created_at', [now()->subMinutes($maxAge), now()->subMinutes($minAge)])
            ->with(['patient.user', 'doctor'])
            ->get();

        if ($candidates->isEmpty()) {
            $this->info("No abandoned-payment consultations to follow up on.");
            return self::SUCCESS;
        }

        $this->line("Found {$candidates->count()} candidate(s)");

        $sent = 0;
        $skipped = 0;
        foreach ($candidates as $consultation) {
            $patient = $consultation->patient;
            $email = $patient?->email ?: $patient?->user?->email;

            if (! $email) {
                $this->warn("  - skip #{$consultation->id}: no patient email");
                $skipped++;
                continue;
            }

            // Honor patient's notify_email_bookings opt-out.
            if ($patient && method_exists($patient, 'wantsNotification')
                && ! $patient->wantsNotification('bookings', 'email')) {
                $this->warn("  - skip #{$consultation->id}: patient opted out");
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  - would email {$email} for consultation #{$consultation->id}");
                continue;
            }

            try {
                $this->sendRecoveryEmail($consultation, $patient, $email);
                $consultation->update(['recovery_email_sent_at' => now()]);
                $sent++;
                $this->line("  ✓ #{$consultation->id} → {$email}");
            } catch (\Throwable $e) {
                Log::warning('[telemedicine:send-recovery-emails] failed', [
                    'consultation_id' => $consultation->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ #{$consultation->id}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $verb = $this->option('dry-run') ? 'would ' : '';
        $this->info("\n✓ Done. {$verb}Sent: {$sent}, Skipped: {$skipped}");
        return self::SUCCESS;
    }

    private function sendRecoveryEmail(OnlineConsultation $c, $patient, string $email): void
    {
        $locale = $patient?->preferred_language ?? 'ar';
        $isRtl  = $locale === 'ar';
        $appUrl = rtrim(config('app.url'), '/');

        // Resume URL: where the patient picks up the abandoned booking.
        // For now, send them back to the doctor list — they'll see their
        // pending row in the patient's online-consultations index too.
        $resumeUrl = "{$appUrl}/{$locale}/patient/online-consultations";

        $doctorName = $c->doctor?->{$isRtl ? 'name_ar' : 'name_en'} ?? '—';
        $patientName = $patient?->full_name
            ?? $patient?->user?->name
            ?? ($isRtl ? 'عميلنا الكريم' : 'there');

        $appointmentDate = $c->scheduled_date
            ? Carbon::parse($c->scheduled_date)->translatedFormat($isRtl ? 'l، j F Y' : 'l, F j, Y')
            : null;
        $appointmentTime = $c->start_time
            ? (is_string($c->start_time) ? substr($c->start_time, 0, 5) : $c->start_time->format('H:i'))
            : null;

        $fee = number_format((float) $c->fee, 0)
            . ' ' . ($isRtl ? 'ج.م' : 'EGP');

        $subject = $isRtl
            ? '[Doctorato] حجز استشارتك في انتظارك'
            : '[Doctorato] Your consultation booking is waiting';

        Mail::send('emails.consultation-recovery', [
            'patientName'     => $patientName,
            'locale'          => $locale,
            'doctorName'      => $doctorName,
            'appointmentDate' => $appointmentDate,
            'appointmentTime' => $appointmentTime,
            'fee'             => $fee,
            'resumeUrl'       => $resumeUrl,
        ], function ($m) use ($email, $patientName, $subject) {
            $m->to($email, $patientName)->subject($subject);
        });
    }
}
