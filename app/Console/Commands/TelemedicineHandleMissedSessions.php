<?php

namespace App\Console\Commands;

use App\Models\OnlineConsultation;
use App\Services\OnlineConsultationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TelemedicineHandleMissedSessions extends Command
{
    protected $signature = 'telemedicine:handle-missed';
    protected $description = 'Mark telemedicine consultations as missed if the scheduled window has passed without both participants joining';

    public function handle(OnlineConsultationService $service): int
    {
        $threshold = now()->subMinutes(30);

        $consultations = OnlineConsultation::whereIn('status', [
                OnlineConsultation::STATUS_SCHEDULED,
                OnlineConsultation::STATUS_WAITING,
                OnlineConsultation::STATUS_IN_PROGRESS,
            ])
            ->where('payment_status', 'paid')
            ->whereDate('scheduled_date', '<=', $threshold->toDateString())
            ->get()
            ->filter(function ($c) use ($threshold) {
                $end = Carbon::parse($c->scheduled_date->format('Y-m-d') . ' ' . $c->end_time);
                return $end->lessThan($threshold);
            });

        $count = 0;
        foreach ($consultations as $consultation) {
            try {
                if ($consultation->status === OnlineConsultation::STATUS_IN_PROGRESS
                    && $consultation->session_started_at) {
                    // Session actually happened, just wasn't properly closed → auto-complete
                    $service->completeSession($consultation, []);
                } else {
                    $service->handleMissedSession($consultation);
                }
                $count++;
            } catch (\Throwable $e) {
                $this->error("Failed to process consultation {$consultation->id}: " . $e->getMessage());
                report($e);
            }
        }

        $this->info("Processed {$count} telemedicine consultation(s)");
        return self::SUCCESS;
    }
}
