<?php

namespace App\Services\Ai\Features;

use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;
use Illuminate\Support\Facades\DB;

/**
 * No-show risk insights. Computes a safe, aggregate snapshot of attendance
 * history + upcoming appointments (counts only, no identifiers), then asks the
 * model to flag risk and suggest concrete actions (e.g. extra reminders).
 */
class NoShowPredictor
{
    private const NO_SHOW_STATUSES = ['no_show', 'missed', 'absent'];

    public function __construct(private readonly AiManager $ai) {}

    public function analyze(array $options = []): AiResult
    {
        $snapshot = $this->snapshot();
        $locale = $options['locale'] ?? app()->getLocale();

        $system = $locale === 'ar'
            ? 'أنت محلل تشغيلي لعيادة. بناءً على لقطة بيانات الحضور والمواعيد القادمة، قيّم خطر الغياب واقترح إجراءات عملية لتقليله (تذكيرات إضافية، تأكيد هاتفي، حجز مزدوج للفترات عالية الخطورة). أجب بإيجاز بالعربية.'
            : 'You are a clinic operations analyst. From the attendance snapshot and upcoming appointments, assess no-show risk and suggest concrete actions to reduce it (extra reminders, phone confirmation, overbooking high-risk slots). Be concise.';

        $user = "Attendance snapshot (JSON):\n".json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this->ai->generate('no_show_prediction', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], $options);
    }

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

        $total = $safe(fn () => DB::table('booking_appointments')->count()) ?? 0;
        $noShows = $safe(fn () => DB::table('booking_appointments')->whereIn('status', self::NO_SHOW_STATUSES)->count()) ?? 0;

        return [
            'total_appointments' => $total,
            'no_show_count' => $noShows,
            'no_show_rate_pct' => $total > 0 ? round($noShows / $total * 100, 1) : 0,
            'upcoming_7_days' => $safe(fn () => DB::table('booking_appointments')
                ->whereBetween('appointment_date', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
                ->whereNotIn('status', ['completed', 'cancelled'])->count()) ?? 0,
            'upcoming_today' => $safe(fn () => DB::table('booking_appointments')
                ->whereDate('appointment_date', $now->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled'])->count()) ?? 0,
        ];
    }
}
