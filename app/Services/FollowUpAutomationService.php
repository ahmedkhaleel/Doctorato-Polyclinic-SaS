<?php

namespace App\Services;

use App\Models\CommunicationTemplate;
use App\Models\FollowUpSequence;
use App\Models\FollowUpSequenceStep;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadSequenceEnrollment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SequenceStepNotification;
use Illuminate\Support\Facades\Log;

class FollowUpAutomationService
{
    /**
     * Trigger sequences for a given event on a lead.
     */
    public static function triggerEvent(Lead $lead, string $event): void
    {
        if (Setting::get('automation_enabled', '1') !== '1') {
            return;
        }

        $sequences = FollowUpSequence::active()
            ->forEvent($event)
            ->with('steps')
            ->get();

        foreach ($sequences as $sequence) {
            static::enrollLead($lead, $sequence);
        }
    }

    /**
     * Trigger status change event.
     */
    public static function triggerStatusChange(Lead $lead, string $newStatus): void
    {
        $event = 'status_changed_to_'.$newStatus;
        static::triggerEvent($lead, $event);
    }

    /**
     * Enroll a lead into a sequence.
     */
    public static function enrollLead(Lead $lead, FollowUpSequence $sequence): ?LeadSequenceEnrollment
    {
        // Check if already enrolled
        $existing = LeadSequenceEnrollment::where('lead_id', $lead->id)
            ->where('sequence_id', $sequence->id)
            ->whereIn('status', [LeadSequenceEnrollment::STATUS_ACTIVE, LeadSequenceEnrollment::STATUS_PAUSED])
            ->exists();

        if ($existing) {
            return null;
        }

        // Check conditions
        if (! $sequence->canEnroll() || ! $sequence->matchesConditions($lead)) {
            return null;
        }

        // Skip if lead is already converted or lost
        if (in_array($lead->status, [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])) {
            return null;
        }

        // Get first active step
        $firstStep = $sequence->steps->where('is_active', true)->sortBy('step_order')->first();
        if (! $firstStep) {
            return null;
        }

        $enrollment = LeadSequenceEnrollment::create([
            'lead_id' => $lead->id,
            'sequence_id' => $sequence->id,
            'current_step_index' => $firstStep->step_order,
            'status' => LeadSequenceEnrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
            'next_step_at' => now()->addMinutes($firstStep->delay_minutes),
        ]);

        $sequence->incrementEnrollment();

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => "Auto-enrolled in sequence: {$sequence->name}",
            'metadata' => ['sequence_id' => $sequence->id, 'enrollment_id' => $enrollment->id],
        ]);

        return $enrollment;
    }

    /**
     * Process all due enrollment steps (called by cron).
     */
    public static function processDueSteps(): array
    {
        $processed = 0;
        $failed = 0;

        $enrollments = LeadSequenceEnrollment::with(['lead', 'sequence.steps'])
            ->due()
            ->limit(100) // batch size
            ->get();

        foreach ($enrollments as $enrollment) {
            try {
                static::executeStep($enrollment);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Sequence step execution failed', [
                    'enrollment_id' => $enrollment->id,
                    'lead_id' => $enrollment->lead_id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    /**
     * Execute the current step for an enrollment.
     */
    protected static function executeStep(LeadSequenceEnrollment $enrollment): void
    {
        $lead = $enrollment->lead;
        $sequence = $enrollment->sequence;

        // Check if lead was converted/lost → stop
        if ($sequence->stop_on_conversion && in_array($lead->status, [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])) {
            $enrollment->cancel(LeadSequenceEnrollment::STATUS_STOPPED_CONVERSION);

            return;
        }

        // Check if lead replied (was contacted after enrollment) → stop
        if ($sequence->stop_on_reply && $lead->last_contacted_at && $lead->last_contacted_at->gt($enrollment->enrolled_at)) {
            // Only stop if there was inbound activity
            $hasInbound = LeadActivity::where('lead_id', $lead->id)
                ->where('direction', 'inbound')
                ->where('created_at', '>', $enrollment->enrolled_at)
                ->exists();

            if ($hasInbound) {
                $enrollment->cancel(LeadSequenceEnrollment::STATUS_STOPPED_REPLY);

                return;
            }
        }

        // Find the current step
        $step = $sequence->steps
            ->where('step_order', $enrollment->current_step_index)
            ->where('is_active', true)
            ->first();

        if (! $step) {
            $enrollment->markCompleted();

            return;
        }

        // Execute the action
        $result = static::executeAction($lead, $step);

        // Log the execution
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'subject' => "Sequence step executed: {$step->action_type}",
            'description' => $result['message'] ?? null,
            'metadata' => [
                'sequence_id' => $sequence->id,
                'step_id' => $step->id,
                'step_order' => $step->step_order,
                'action_type' => $step->action_type,
                'success' => $result['success'],
            ],
        ]);

        // Advance to next step
        $enrollment->advanceToNextStep();
    }

    /**
     * Execute a specific step action.
     */
    protected static function executeAction(Lead $lead, FollowUpSequenceStep $step): array
    {
        return match ($step->action_type) {
            FollowUpSequenceStep::ACTION_CREATE_FOLLOW_UP => static::actionCreateFollowUp($lead, $step),
            FollowUpSequenceStep::ACTION_SEND_WHATSAPP => static::actionSendMessage($lead, $step, 'whatsapp'),
            FollowUpSequenceStep::ACTION_SEND_EMAIL => static::actionSendMessage($lead, $step, 'email'),
            FollowUpSequenceStep::ACTION_SEND_SMS => static::actionSendMessage($lead, $step, 'sms'),
            FollowUpSequenceStep::ACTION_SEND_AI_MESSAGE => static::actionSendAiMessage($lead, $step),
            FollowUpSequenceStep::ACTION_NOTIFY_STAFF => static::actionNotifyStaff($lead, $step),
            FollowUpSequenceStep::ACTION_CHANGE_STATUS => static::actionChangeStatus($lead, $step),
            FollowUpSequenceStep::ACTION_ADD_SCORE => static::actionAddScore($lead, $step),
            default => ['success' => false, 'message' => 'Unknown action type: '.$step->action_type],
        };
    }

    /**
     * CRM-3 — AI-personalized WhatsApp step. Drafts via the gated CrmAssistant
     * (lead_reply flag); when AI is off/unavailable it falls back to the step's
     * template/custom message so the sequence NEVER stalls on the AI layer.
     */
    protected static function actionSendAiMessage(Lead $lead, FollowUpSequenceStep $step): array
    {
        if ($lead->phone) {
            try {
                $result = app(\App\Services\Ai\Features\CrmAssistant::class)
                    ->draftMessage($lead, 'whatsapp', 'friendly', app()->getLocale());

                $phone = preg_replace('/[^0-9]/', '', $lead->phone);
                $redirectUrl = 'https://wa.me/'.$phone.'?text='.urlencode($result->text);

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'whatsapp',
                    'subject' => 'Auto WhatsApp (Sequence, AI)',
                    'description' => $result->text,
                    'direction' => 'outbound',
                    'metadata' => ['automated' => true, 'ai' => true, 'model' => $result->model, 'redirect_url' => $redirectUrl],
                ]);

                $lead->markAsContacted();

                return ['success' => true, 'message' => 'AI WhatsApp message prepared.', 'redirect_url' => $redirectUrl];
            } catch (\App\Services\Ai\Exceptions\AiUnavailableException) {
                // fall through to the template/custom-message path below
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return static::actionSendMessage($lead, $step, 'whatsapp');
    }

    /**
     * Create a follow-up task for the lead.
     */
    protected static function actionCreateFollowUp(Lead $lead, FollowUpSequenceStep $step): array
    {
        // If no assigned user, try to assign to first admin
        $assignedTo = $lead->assigned_to;
        if (! $assignedTo) {
            $assignedTo = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))
                ->first()?->id;
        }

        if (! $assignedTo) {
            return ['success' => false, 'message' => 'No user available to assign follow-up.'];
        }

        $followUp = LeadFollowUp::create([
            'lead_id' => $lead->id,
            'type' => $step->follow_up_type ?? 'call',
            'scheduled_at' => now()->addHours(1),
            'notes' => $step->notification_message ?? "Auto-created by sequence: {$step->sequence->name}",
            'status' => 'pending',
            'assigned_to' => $assignedTo,
        ]);

        // Notify assigned user
        if ($lead->assigned_to) {
            $user = User::find($lead->assigned_to);
            if ($user) {
                $user->notify(new \App\Notifications\FollowUpReminderNotification($followUp, 'upcoming'));
            }
        }

        return ['success' => true, 'message' => "Follow-up created: {$step->follow_up_type}"];
    }

    /**
     * Send a message via a communication channel.
     */
    protected static function actionSendMessage(Lead $lead, FollowUpSequenceStep $step, string $channel): array
    {
        // Use template if provided
        if ($step->template_id) {
            $template = CommunicationTemplate::find($step->template_id);
            if ($template) {
                $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

                return CommunicationService::send($lead, $template, $channel, $lang);
            }
        }

        // Use custom message
        $message = app()->getLocale() === 'ar'
            ? ($step->message_ar ?? $step->message_en)
            : ($step->message_en ?? $step->message_ar);

        if (! $message) {
            return ['success' => false, 'message' => 'No template or message configured for this step.'];
        }

        // Replace variables in custom message
        $variables = CommunicationService::buildVariables($lead);
        foreach ($variables as $key => $value) {
            $message = str_replace('{{'.$key.'}}', $value, $message);
        }

        // For WhatsApp, generate link
        if ($channel === 'whatsapp' && $lead->phone) {
            $phone = preg_replace('/[^0-9]/', '', $lead->phone);
            $redirectUrl = 'https://wa.me/'.$phone.'?text='.urlencode($message);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'type' => 'whatsapp',
                'subject' => 'Auto WhatsApp (Sequence)',
                'description' => $message,
                'direction' => 'outbound',
                'metadata' => ['automated' => true, 'redirect_url' => $redirectUrl],
            ]);

            $lead->markAsContacted();

            return ['success' => true, 'message' => 'WhatsApp message prepared.', 'redirect_url' => $redirectUrl];
        }

        // For email
        if ($channel === 'email' && $lead->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($lead->email)
                    ->send(new \App\Mail\LeadTemplateMail('Follow-up', $message, $lead->full_name));

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'email',
                    'subject' => 'Auto Email (Sequence)',
                    'description' => $message,
                    'direction' => 'outbound',
                    'metadata' => ['automated' => true],
                ]);

                $lead->markAsContacted();

                return ['success' => true, 'message' => 'Email sent.'];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Email failed: '.$e->getMessage()];
            }
        }

        // SMS
        if ($channel === 'sms') {
            LeadActivity::create([
                'lead_id' => $lead->id,
                'type' => 'sms',
                'subject' => 'Auto SMS (Sequence)',
                'description' => $message,
                'direction' => 'outbound',
                'metadata' => ['automated' => true],
            ]);

            $lead->markAsContacted();

            return ['success' => true, 'message' => 'SMS logged.'];
        }

        return ['success' => false, 'message' => 'Missing contact info for channel: '.$channel];
    }

    /**
     * Notify assigned staff member.
     */
    protected static function actionNotifyStaff(Lead $lead, FollowUpSequenceStep $step): array
    {
        $userId = $lead->assigned_to;
        if (! $userId) {
            // Notify all admins
            $admins = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))->get();
            foreach ($admins as $admin) {
                $admin->notify(new SequenceStepNotification($lead, $step));
            }

            return ['success' => true, 'message' => 'Admins notified.'];
        }

        $user = User::find($userId);
        if ($user) {
            $user->notify(new SequenceStepNotification($lead, $step));

            return ['success' => true, 'message' => "Staff notified: {$user->name}"];
        }

        return ['success' => false, 'message' => 'Assigned user not found.'];
    }

    /**
     * Change lead status.
     */
    protected static function actionChangeStatus(Lead $lead, FollowUpSequenceStep $step): array
    {
        if (! $step->target_status) {
            return ['success' => false, 'message' => 'No target status configured.'];
        }

        $oldStatus = $lead->status;
        $lead->update(['status' => $step->target_status]);
        LeadActivity::logStatusChange($lead, $oldStatus, $step->target_status);

        return ['success' => true, 'message' => "Status changed: {$oldStatus} → {$step->target_status}"];
    }

    /**
     * Add or remove score points.
     */
    protected static function actionAddScore(Lead $lead, FollowUpSequenceStep $step): array
    {
        $points = $step->score_points ?? 0;
        if ($points === 0) {
            return ['success' => false, 'message' => 'No score points configured.'];
        }

        $lead->addScore($points);

        return ['success' => true, 'message' => "Score adjusted by {$points} points. New score: {$lead->score}"];
    }

    /**
     * Cancel all active enrollments for a lead (e.g., on conversion).
     */
    public static function cancelAllForLead(Lead $lead, string $reason = 'cancelled'): int
    {
        return LeadSequenceEnrollment::where('lead_id', $lead->id)
            ->active()
            ->update([
                'status' => $reason,
                'cancelled_at' => now(),
                'next_step_at' => null,
            ]);
    }
}
