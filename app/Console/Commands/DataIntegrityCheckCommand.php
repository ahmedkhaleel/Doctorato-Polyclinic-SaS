<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\OnlineConsultation;
use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Non-destructive audit of the database looking for inconsistent state
 * that accumulates over time and quietly breaks things:
 *
 *   - Bookings / invoices / consultations pointing to a deleted patient
 *   - Invoices whose totals don't match the sum of their items
 *   - Doctors flagged online_consultation_enabled=true with NO online
 *     schedule (the exact state that caused our booking 404 loop)
 *   - OnlineConsultation rows stuck in "pending_payment" for > 24h
 *
 * Schedule weekly; output is a table by default, or JSON with --json so
 * it can be piped into monitoring.
 */
class DataIntegrityCheckCommand extends Command
{
    protected $signature = 'data:integrity-check
                            {--json : Output JSON instead of a table}
                            {--log : Write findings to laravel.log as well}
                            {--alert : Email admin if issues are found (respects 24h cooldown)}';

    protected $description = 'Find orphaned, inconsistent, or stuck records';

    public function handle(): int
    {
        $findings = [];

        // ── 1. Bookings referring to a deleted patient ──────
        $orphanBookings = Booking::whereDoesntHave('patient')->count();
        if ($orphanBookings > 0) {
            $findings[] = [
                'check' => 'orphan_bookings',
                'count' => $orphanBookings,
                'detail' => 'Bookings whose patient no longer exists (even in trashed)',
            ];
        }

        // ── 2. Invoices whose patient is gone ───────────────
        $orphanInvoices = Invoice::whereDoesntHave('patient')->count();
        if ($orphanInvoices > 0) {
            $findings[] = [
                'check' => 'orphan_invoices',
                'count' => $orphanInvoices,
                'detail' => 'Invoices whose patient no longer exists',
            ];
        }

        // ── 3. Invoices where total_amount != sum(items) ────
        try {
            $invoiceDrift = DB::table('invoices')
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->select('invoices.id', 'invoices.total_amount',
                    DB::raw('SUM(invoice_items.total_price) as items_total'))
                ->groupBy('invoices.id', 'invoices.total_amount')
                ->havingRaw('ABS(invoices.total_amount - SUM(invoice_items.total_price)) > 0.01')
                ->limit(50)
                ->get();
            if ($invoiceDrift->isNotEmpty()) {
                $findings[] = [
                    'check' => 'invoice_total_drift',
                    'count' => $invoiceDrift->count(),
                    'detail' => 'Invoices whose total_amount disagrees with sum(items.total_price)',
                    'ids' => $invoiceDrift->pluck('id')->toArray(),
                ];
            }
        } catch (\Throwable $e) {
            // Columns may differ; not fatal.
        }

        // ── 4. Online-enabled doctors with no online schedule ─
        $badDoctors = Doctor::onlineEnabled()
            ->whereDoesntHave('schedules', fn ($q) => $q
                ->whereIn('mode', ['online', 'both'])
                ->where('is_active', true))
            ->pluck('id')
            ->toArray();
        if (! empty($badDoctors)) {
            $findings[] = [
                'check' => 'online_doctor_no_schedule',
                'count' => count($badDoctors),
                'detail' => 'Doctors with online_consultation_enabled=true but zero online/both schedules',
                'ids' => $badDoctors,
            ];
        }

        // ── 5. Online consultations stuck pending_payment > 24h ─
        $stuck = OnlineConsultation::where('payment_status', 'pending')
            ->where('created_at', '<', now()->subDay())
            ->pluck('id')
            ->toArray();
        if (! empty($stuck)) {
            $findings[] = [
                'check' => 'stuck_pending_consultations',
                'count' => count($stuck),
                'detail' => 'OnlineConsultation rows still pending payment for 24h+',
                'ids' => array_slice($stuck, 0, 50),
            ];
        }

        // ── 6. Active patients with no user account ─────────
        $patientsNoUser = Patient::whereNull('user_id')->where('is_active', true)->count();
        if ($patientsNoUser > 0) {
            $findings[] = [
                'check' => 'active_patients_without_user',
                'count' => $patientsNoUser,
                'detail' => 'Active patients with no linked user — cannot log in to the portal',
            ];
        }

        // ── 7. Paid salary slips / doctor payouts with no expense ──
        // Guards the labor→expense link: paid labor that never hit the ledger
        // means the financial reports understate cost.
        try {
            $slipsNoExpense = \App\Models\SalarySlip::where('status', 'paid')
                ->whereNull('expense_id')
                ->where('net_salary', '>', 0)
                ->count();
            if ($slipsNoExpense > 0) {
                $findings[] = [
                    'check' => 'paid_slip_without_expense',
                    'count' => $slipsNoExpense,
                    'detail' => 'Paid salary slips not recorded in the expense ledger',
                ];
            }

            $payoutsNoExpense = \App\Models\DoctorPayout::where('status', 'paid')
                ->whereNull('expense_id')
                ->where('net_amount', '>', 0)
                ->count();
            if ($payoutsNoExpense > 0) {
                $findings[] = [
                    'check' => 'paid_payout_without_expense',
                    'count' => $payoutsNoExpense,
                    'detail' => 'Paid doctor payouts not recorded in the expense ledger',
                ];
            }
        } catch (\Throwable $e) {
            // Columns may not exist on older schema; not fatal.
        }

        // ── 8. Visit-based invoices missing a module tag ──────
        // Guards the invoice-module hook: untagged visit invoices vanish from
        // per-module revenue dashboards.
        try {
            $untaggedInvoices = Invoice::whereNull('module')
                ->whereNotNull('visit_id')
                ->whereHas('visit', fn ($q) => $q->whereNotNull('module'))
                ->count();
            if ($untaggedInvoices > 0) {
                $findings[] = [
                    'check' => 'invoice_missing_module',
                    'count' => $untaggedInvoices,
                    'detail' => 'Visit-based invoices with no module tag (excluded from module revenue)',
                ];
            }
        } catch (\Throwable $e) {
            // not fatal
        }

        // ── 9. Salary-mode doctors with a cash-disbursed payout ──
        // Guards the hybrid payment model: a salary-mode doctor should never
        // have a 'paid' (cash-disbursed) payout — that would be a double pay.
        try {
            $doubleRisk = \App\Models\DoctorPayout::where('doctor_payouts.status', 'paid')
                ->whereHas('doctor', fn ($q) => $q->where('payment_mode', 'salary'))
                ->count();
            if ($doubleRisk > 0) {
                $findings[] = [
                    'check' => 'salary_doctor_cash_payout',
                    'count' => $doubleRisk,
                    'detail' => 'Salary-mode doctors with a cash-paid payout (double-pay risk — commission also on slip)',
                ];
            }
        } catch (\Throwable $e) {
            // column may not exist yet; not fatal
        }

        // ── 10. Paid insurance claims with no recorded payment ──
        // Guards the claim→payment link: a reimbursed claim with no Payment
        // means the insurer's money never landed on the invoice / in revenue.
        try {
            $claimsNoPayment = \App\Models\InsuranceClaim::whereIn('status', ['paid', 'partially_paid'])
                ->whereNotNull('invoice_id')
                ->whereNull('payment_id')
                ->where('paid_amount', '>', 0)
                ->count();
            if ($claimsNoPayment > 0) {
                $findings[] = [
                    'check' => 'paid_claim_without_payment',
                    'count' => $claimsNoPayment,
                    'detail' => 'Reimbursed insurance claims not recorded as an invoice payment (income missing)',
                ];
            }
        } catch (\Throwable $e) {
            // column may not exist yet; not fatal
        }

        // ── 11. Paid marketer commissions with no expense ─────
        try {
            $commNoExpense = \App\Models\MarketerCommission::where('status', 'paid')
                ->whereNull('expense_id')
                ->where('commission_amount', '>', 0)
                ->count();
            if ($commNoExpense > 0) {
                $findings[] = [
                    'check' => 'paid_commission_without_expense',
                    'count' => $commNoExpense,
                    'detail' => 'Paid marketer commissions not recorded in the expense ledger',
                ];
            }
        } catch (\Throwable $e) {
            // column may not exist yet; not fatal
        }

        // ── 12. Paid online consultations with no invoice ─────
        try {
            $consultNoInvoice = \App\Models\OnlineConsultation::where('payment_status', 'paid')
                ->whereNull('invoice_id')
                ->where('fee', '>', 0)
                ->count();
            if ($consultNoInvoice > 0) {
                $findings[] = [
                    'check' => 'paid_consultation_without_invoice',
                    'count' => $consultNoInvoice,
                    'detail' => 'Paid telemedicine consultations not recorded as an invoice (income missing)',
                ];
            }
        } catch (\Throwable $e) {
            // not fatal
        }

        // ── 13. Delivered pregnancies with no delivery record ──
        // Guards the obstetric flow: a pregnancy marked delivered must carry a
        // DeliveryRecord, else the delivery (and its billing) is unrecorded.
        try {
            $deliveredNoRecord = \App\Models\Pregnancy::where('status', 'delivered')
                ->whereDoesntHave('delivery')
                ->count();
            if ($deliveredNoRecord > 0) {
                $findings[] = [
                    'check' => 'pregnancy_delivered_without_record',
                    'count' => $deliveredNoRecord,
                    'detail' => 'Pregnancies marked delivered with no delivery record',
                ];
            }
        } catch (\Throwable $e) {
            // not fatal
        }

        // ── 14. Active pregnancies with no antenatal visit in 6+ weeks ──
        try {
            $stalePregnancies = \App\Models\Pregnancy::where('status', 'active')
                ->where('created_at', '<', now()->subWeeks(6))
                ->whereDoesntHave('antenatalVisits', fn ($q) => $q->where('visit_date', '>=', now()->subWeeks(6)))
                ->count();
            if ($stalePregnancies > 0) {
                $findings[] = [
                    'check' => 'active_pregnancy_no_recent_anc',
                    'count' => $stalePregnancies,
                    'detail' => 'Active pregnancies with no antenatal visit in the last 6 weeks',
                ];
            }
        } catch (\Throwable $e) {
            // not fatal
        }

        // ── 15. Obstetric records on a non-female patient ──────
        // OB/GYN is female-only; a pregnancy on a non-female patient is a
        // data-entry error worth surfacing.
        try {
            $wrongGender = \App\Models\Pregnancy::whereHas('patient', fn ($q) => $q->where('gender', '!=', 'female'))->count();
            if ($wrongGender > 0) {
                $findings[] = [
                    'check' => 'pregnancy_non_female_patient',
                    'count' => $wrongGender,
                    'detail' => 'Pregnancy records linked to a non-female patient',
                ];
            }
        } catch (\Throwable $e) {
            // not fatal
        }

        // ── Report ──────────────────────────────────────────
        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => empty($findings),
                'at' => now()->toIso8601String(),
                'findings' => $findings,
            ], JSON_PRETTY_PRINT));
        } else {
            if (empty($findings)) {
                $this->info('✓ No integrity issues found.');
            } else {
                $this->warn('Found '.count($findings)." integrity issue(s):\n");
                $rows = array_map(fn ($f) => [$f['check'], $f['count'], $f['detail']], $findings);
                $this->table(['Check', 'Count', 'Detail'], $rows);
            }
        }

        if ($this->option('log') && ! empty($findings)) {
            Log::warning('[data:integrity-check] issues found', ['findings' => $findings]);
        }

        if ($this->option('alert') && ! empty($findings)) {
            $this->sendAlertIfNotRecent($findings);
        }

        // Non-zero exit when there's something to investigate, so cron
        // wrappers can pipe it to alerting if they want.
        return empty($findings) ? self::SUCCESS : 1;
    }

    private function sendAlertIfNotRecent(array $findings): void
    {
        $cacheKey = 'integrity_alert:last_sent';
        $last = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
        if ((time() - $last) < 86400) {  // 24h cooldown
            $this->warn('Integrity alert skipped (cooldown — last sent '.(time() - $last).'s ago).');

            return;
        }

        $email = \App\Models\Setting::get('health_alert_email')
            ?: \App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->value('email');

        if (! $email) {
            $this->warn('No admin email configured — skipping alert.');

            return;
        }

        $subject = '[Doctorato] Data integrity issues: '.count($findings).' check(s)';
        $body = "Weekly integrity sweep found issues that need attention.\n\n";
        foreach ($findings as $f) {
            $body .= "• {$f['check']}: {$f['count']} — {$f['detail']}\n";
            if (! empty($f['ids'])) {
                $body .= '  Sample IDs: '.implode(', ', array_slice($f['ids'], 0, 10))."\n";
            }
        }
        $body .= "\nRun 'php artisan data:integrity-check' for full details.\n";
        $body .= 'Diagnostics: '.rtrim(config('app.url'), '/')."/ar/admin/diagnostics\n";

        try {
            \Illuminate\Support\Facades\Mail::raw($body, function ($m) use ($email, $subject) {
                $m->to($email)->subject($subject);
            });
            \Illuminate\Support\Facades\Cache::put($cacheKey, time(), 86400 * 2);
            $this->info("Alert sent to {$email}");
        } catch (\Throwable $e) {
            Log::error('[data:integrity-check] alert mail failed', ['error' => $e->getMessage()]);
        }
    }
}
