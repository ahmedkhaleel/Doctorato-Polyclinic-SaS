<?php

namespace App\Services\Ai\Features;

use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;
use Illuminate\Support\Facades\DB;

/**
 * Natural-language analytics. Instead of generating SQL (risky), it gathers a
 * safe, read-only snapshot of key metrics and lets the model answer the question
 * from that snapshot. No raw data or PHI leaves the system.
 */
class InsightAnalyst
{
    public function __construct(private readonly AiManager $ai) {}

    public function ask(string $question, array $options = []): AiResult
    {
        $snapshot = $this->snapshot();
        $locale = $options['locale'] ?? app()->getLocale();

        $system = $locale === 'ar'
            ? 'أنت محلل بيانات للعيادة. أجب عن السؤال بالاعتماد فقط على لقطة المؤشرات المرفقة. إن لم تكفِ البيانات فاذكر ذلك. أجب بإيجاز بالعربية.'
            : 'You are a clinic data analyst. Answer using only the metrics snapshot provided. If insufficient, say so. Be concise.';

        $user = "Question: {$question}\n\nMetrics snapshot (JSON):\n".json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this->ai->generate('nl_analytics', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], $options);
    }

    /** A small read-only metrics snapshot (current month + totals). */
    public function snapshot(): array
    {
        $now = now();
        $safe = fn (callable $cb) => (function () use ($cb) {
            try {
                return $cb();
            } catch (\Throwable) {
                return null;
            }
        })();

        return [
            'today' => $now->toDateString(),
            'patients_total' => $safe(fn () => DB::table('patients')->count()),
            'visits_this_month' => $safe(fn () => DB::table('visits')->whereYear('visit_date', $now->year)->whereMonth('visit_date', $now->month)->count()),
            'bookings_pending' => $safe(fn () => DB::table('bookings')->where('status', 'new')->count()),
            'revenue_this_month' => $safe(fn () => round((float) DB::table('payments')->whereYear('payment_date', $now->year)->whereMonth('payment_date', $now->month)->sum('amount'), 2)),
            'expenses_this_month' => $safe(fn () => round((float) DB::table('expenses')->whereYear('expense_date', $now->year)->whereMonth('expense_date', $now->month)->sum('amount'), 2)),
            'unpaid_invoices' => $safe(fn () => DB::table('invoices')->whereIn('status', ['unpaid', 'partial'])->count()),
            'unpaid_balance' => $safe(fn () => round((float) DB::table('invoices')->whereIn('status', ['unpaid', 'partial'])->sum(DB::raw('total - paid_amount')), 2)),
            'doctors_active' => $safe(fn () => DB::table('doctors')->where('status', 'active')->count()),
        ];
    }
}
