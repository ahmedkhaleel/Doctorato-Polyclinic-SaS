<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\LeadSequenceEnrollment;
use App\Services\CommunicationService;
use App\Models\CommunicationTemplate;
use App\Models\FollowUpSequenceStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for processing a single lead's follow-up sequence step.
 * Sends the configured communication (email/SMS) for the current step.
 */
class ProcessFollowUpSequenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 600];

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public LeadSequenceEnrollment $enrollment,
        public FollowUpSequenceStep $step,
    ) {}

    public function handle(): void
    {
        $lead = $this->enrollment->lead;

        if (! $lead || $this->enrollment->status !== 'active') {
            Log::info('Skipping sequence step — lead missing or enrollment inactive', [
                'enrollment_id' => $this->enrollment->id,
            ]);

            return;
        }

        $template = $this->step->communicationTemplate;

        if (! $template) {
            Log::warning('Sequence step has no template', [
                'step_id' => $this->step->id,
                'enrollment_id' => $this->enrollment->id,
            ]);

            return;
        }

        $channel = $this->step->channel ?? 'email';

        $result = CommunicationService::send(
            lead: $lead,
            template: $template,
            channel: $channel,
            language: $this->step->language ?? 'en',
        );

        // Update enrollment progress
        $this->enrollment->update([
            'current_step' => $this->step->order,
            'last_step_at' => now(),
        ]);

        Log::info('Follow-up sequence step processed', [
            'enrollment_id' => $this->enrollment->id,
            'step_id' => $this->step->id,
            'channel' => $channel,
            'success' => $result['success'],
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Follow-up sequence step failed permanently', [
            'enrollment_id' => $this->enrollment->id,
            'step_id' => $this->step->id,
            'error' => $exception?->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['crm', 'sequence', "enrollment:{$this->enrollment->id}"];
    }
}
