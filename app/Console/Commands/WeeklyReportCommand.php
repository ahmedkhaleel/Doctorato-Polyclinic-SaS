<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\OnlineConsultation;
use App\Models\Setting;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\Payment\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Assembles a weekly operations summary and mails it to the admin.
 *
 * Pulls numbers from bookings, invoices, online_consultations, plus the
 * same readiness signals /health uses — so the operator gets one
 * digest per week instead of eyeballing four different pages.
 *
 * Scheduled: every Monday at 08:00 (routes/console.php).
 *
 * Flags:
 *   --to=email@example.com  Override recipient (default: health_alert_email
 *                           setting, fallback to first super_admin)
 *   --dry-run               Render the mail body to stdout, don't send
 */
class WeeklyReportCommand extends Command
{
    protected $signature = 'reports:weekly
                            {--to= : Override recipient address}
                            {--dry-run : Render only, do not send}';

    protected $description = 'Send a weekly operations report to the admin';

    public function handle(): int
    {
        $data = $this->buildReport();

        if ($this->option('dry-run')) {
            $this->info("Dry-run — would send to: " . ($this->option('to') ?: $this->recipient() ?: 'nobody'));
            $this->line(str_repeat('-', 60));
            // Render HTML but only echo the section summary, not the full 600-line email
            $this->line("Range: {$data['rangeLabel']}");
            $this->line("Bookings this week: {$data['bookings']['this_week']} (confirmed {$data['bookings']['confirmed']}, cancelled {$data['bookings']['cancelled']})");
            $this->line("Revenue collected: {$data['revenue']['collected']} {$data['currency']}");
            $this->line("Revenue outstanding: {$data['revenue']['outstanding']} {$data['currency']}");
            if ($data['telemedicine']['enabled']) {
                $this->line("Telemedicine: {$data['telemedicine']['consultations_this_week']} consultations this week, gateway={$data['telemedicine']['gateway']}");
            }
            $this->line("Blockers: " . (empty($data['blockers']) ? 'none' : implode(', ', $data['blockers'])));
            return self::SUCCESS;
        }

        $recipient = $this->option('to') ?: $this->recipient();
        if (! $recipient) {
            $this->error('No recipient — set Setting::health_alert_email or create a super_admin user.');
            return self::FAILURE;
        }

        try {
            Mail::send('emails.weekly-report', $data, function ($m) use ($recipient, $data) {
                $m->to($recipient)
                  ->subject('[Doctorato] Weekly Report — ' . $data['rangeLabel']);
            });
            $this->info("Weekly report sent to {$recipient}");
        } catch (\Throwable $e) {
            Log::error('[reports:weekly] mail failed', ['error' => $e->getMessage()]);
            $this->error("Failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function buildReport(): array
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $prevStart = $weekStart->copy()->subWeek();
        $prevEnd   = $weekStart->copy()->subSecond();

        // ── Bookings ────────────────────────────────────
        $thisWeekBookings = Booking::whereBetween('created_at', [$weekStart, $weekEnd]);
        $lastWeekCount    = Booking::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $thisWeekCount    = (clone $thisWeekBookings)->count();
        $delta            = $thisWeekCount - $lastWeekCount;
        $deltaLabel       = $lastWeekCount > 0
            ? sprintf('%s%d%% vs last week', $delta >= 0 ? '+' : '', round($delta / max($lastWeekCount, 1) * 100))
            : ($delta > 0 ? 'First bookings!' : 'No change');

        // ── Revenue ─────────────────────────────────────
        $invoicesThisWeek = Invoice::whereBetween('created_at', [$weekStart, $weekEnd]);
        $collected   = (clone $invoicesThisWeek)->sum('paid_amount');
        $total       = (clone $invoicesThisWeek)->sum('total');
        $outstanding = max(0, $total - $collected);
        $invoiceCount = (clone $invoicesThisWeek)->count();

        // ── Telemedicine ────────────────────────────────
        $telemedEnabled   = ModuleManager::isEnabled('telemedicine');
        $gateway          = app(PaymentGatewayManager::class)->getActive();
        $consultationsWeek = OnlineConsultation::whereBetween('created_at', [$weekStart, $weekEnd])->count();
        $consultationsDone = OnlineConsultation::where('status', 'completed')->count();

        // ── Blockers ────────────────────────────────────
        $blockers = [];
        if ($telemedEnabled && ! $gateway)                      $blockers[] = 'No payment gateway configured';
        if ($telemedEnabled && Doctor::onlineEnabled()->count() === 0)
                                                                $blockers[] = 'No doctors are online-enabled';
        if ($telemedEnabled && DoctorSchedule::whereIn('mode', ['online', 'both'])
                ->where('is_active', true)->count() === 0)     $blockers[] = 'No bookable online schedules';

        return [
            'rangeLabel' => $weekStart->format('M j') . ' – ' . $weekEnd->format('M j, Y'),
            'currency'   => Setting::get('currency_code', 'EGP'),
            'bookings'   => [
                'this_week'   => $thisWeekCount,
                'confirmed'   => (clone $thisWeekBookings)->where('status', 'confirmed')->count(),
                'cancelled'   => (clone $thisWeekBookings)->where('status', 'cancelled')->count(),
                'delta_label' => $deltaLabel,
            ],
            'revenue' => [
                'collected'     => $collected,
                'outstanding'   => $outstanding,
                'invoice_count' => $invoiceCount,
            ],
            'telemedicine' => [
                'enabled'                   => $telemedEnabled,
                'consultations_this_week'  => $consultationsWeek,
                'completed'                 => $consultationsDone,
                'gateway'                   => $gateway ? class_basename($gateway) : null,
            ],
            'blockers'        => $blockers,
            'diagnosticsUrl'  => rtrim(config('app.url'), '/') . '/ar/admin/diagnostics',
        ];
    }

    private function recipient(): ?string
    {
        return Setting::get('health_alert_email')
            ?: User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->value('email');
    }
}
