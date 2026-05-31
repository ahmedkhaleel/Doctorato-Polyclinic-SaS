<?php

namespace App\Services\Notifications;

use App\Models\NotificationSequence;
use App\Models\NotificationSequenceEnrollment as Enrollment;
use Illuminate\Database\Eloquent\Model;

/**
 * Drip sequence engine: enrol recipients and advance them step by step.
 * Each step's delay is the wait before it fires (from enrolment for step 0,
 * else from the prior step). Sends route through the hub (sequence.message),
 * so consent / quiet-hours / frequency-cap apply automatically.
 */
class SequenceService
{
    /** Enrol a recipient (idempotent: one active enrolment per sequence+recipient). */
    public function enroll(NotificationSequence $sequence, Model $recipient): ?Enrollment
    {
        if (! $sequence->is_active) {
            return null;
        }

        $exists = Enrollment::where('sequence_id', $sequence->id)
            ->where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey())
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->exists();
        if ($exists) {
            return null;
        }

        $first = $sequence->steps()->first();
        if (! $first) {
            return null; // empty sequence
        }

        return Enrollment::create([
            'sequence_id' => $sequence->id,
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
            'current_step' => 0,
            'status' => Enrollment::STATUS_ACTIVE,
            'next_run_at' => now()->addMinutes($first->delay_minutes),
        ]);
    }

    /** Auto-enrol recipients into any active sequence triggered by this event. */
    public function enrollForEvent(string $eventKey, Model $recipient): void
    {
        NotificationSequence::where('trigger_event', $eventKey)->where('is_active', true)
            ->get()->each(fn ($seq) => $this->enroll($seq, $recipient));
    }

    /** Process all due enrolments (worker). Returns count advanced. */
    public function advanceDue(int $limit = 500): int
    {
        $due = Enrollment::where('status', Enrollment::STATUS_ACTIVE)
            ->whereNotNull('next_run_at')->where('next_run_at', '<=', now())
            ->with('sequence.steps')->limit($limit)->get();

        $count = 0;
        foreach ($due as $enrollment) {
            $this->advance($enrollment);
            $count++;
        }

        return $count;
    }

    public function advance(Enrollment $enrollment): void
    {
        $steps = $enrollment->sequence?->steps->values();
        if (! $steps || $steps->isEmpty()) {
            $enrollment->update(['status' => Enrollment::STATUS_COMPLETED, 'next_run_at' => null]);

            return;
        }

        $step = $steps->get($enrollment->current_step);
        if ($step && ($recipient = $enrollment->recipient)) {
            Notifier::eventNow('sequence.message', $recipient, [
                'body' => $step->body_ar ?: $step->body_en,
                'subject' => $step->subject,
                'meta' => ['sequence_id' => $enrollment->sequence_id, 'step' => $enrollment->current_step],
            ], $step->channel ? [$step->channel] : null);
        }

        $nextIndex = $enrollment->current_step + 1;
        $nextStep = $steps->get($nextIndex);

        if ($nextStep) {
            $enrollment->update([
                'current_step' => $nextIndex,
                'next_run_at' => now()->addMinutes($nextStep->delay_minutes),
            ]);
        } else {
            $enrollment->update(['status' => Enrollment::STATUS_COMPLETED, 'next_run_at' => null]);
        }
    }
}
