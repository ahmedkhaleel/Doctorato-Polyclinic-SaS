<?php

namespace App\Console\Commands;

use App\Models\Visit;
use App\Models\PatientSatisfaction;
use App\Services\Branch\BranchContext;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSatisfactionSurveys extends Command
{
    protected $signature = 'satisfaction:send-surveys';
    protected $description = 'Send satisfaction survey SMS to patients who completed visits yesterday';

    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();

        $completedVisits = Visit::where('status', 'completed')
            ->where('visit_date', $yesterday)
            ->with('patient:id,full_name,phone')
            ->whereHas('patient', fn ($q) => $q->whereNotNull('phone'))
            ->get();

        $sent = 0;
        foreach ($completedVisits as $visit) {
            // Skip if survey already exists for this visit
            if (PatientSatisfaction::where('visit_id', $visit->id)->exists()) {
                continue;
            }

            // Create pending survey record — inherit the visit's branch
            // (cron runs in all-branches mode, so the create would otherwise
            // fall back to the default branch).
            $survey = app(BranchContext::class)->runForBranch(
                (int) ($visit->branch_id ?? config('branches.default_id', 1)),
                fn () => PatientSatisfaction::create([
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'doctor_id' => $visit->doctor_id,
                    'source' => 'sms',
                ])
            );

            // Send SMS with survey link
            $url = url("/survey/{$survey->token}");
            $message = "شكراً لزيارتك عيادة دكتوراتو. نرجو تقييم تجربتك: {$url}";

            try {
                $smsService = app(SmsService::class);
                $smsService->send($visit->patient->phone, $message);
                $sent++;
            } catch (\Exception $e) {
                Log::warning("Failed to send satisfaction SMS to patient #{$visit->patient_id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} satisfaction surveys for visits on {$yesterday}.");
    }
}
