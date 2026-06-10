<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpSequenceStep extends Model
{
    protected $fillable = [
        'sequence_id',
        'step_order',
        'delay_minutes',
        'action_type',
        'template_id',
        'follow_up_type',
        'target_status',
        'score_points',
        'message_en',
        'message_ar',
        'notification_message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'delay_minutes' => 'integer',
            'score_points' => 'integer',
        ];
    }

    // ─── Action Type Constants ────────────────────────────

    const ACTION_CREATE_FOLLOW_UP = 'create_follow_up';

    const ACTION_SEND_WHATSAPP = 'send_whatsapp';

    const ACTION_SEND_EMAIL = 'send_email';

    const ACTION_SEND_SMS = 'send_sms';

    const ACTION_NOTIFY_STAFF = 'notify_staff';

    const ACTION_CHANGE_STATUS = 'change_status';

    const ACTION_ADD_SCORE = 'add_score';

    const ACTION_SEND_AI_MESSAGE = 'send_ai_message';

    const ACTION_TYPES = [
        self::ACTION_CREATE_FOLLOW_UP => 'Create Follow-up Task',
        self::ACTION_SEND_WHATSAPP => 'Send WhatsApp Message',
        self::ACTION_SEND_EMAIL => 'Send Email',
        self::ACTION_SEND_SMS => 'Send SMS',
        self::ACTION_SEND_AI_MESSAGE => 'Send AI-personalized WhatsApp (template fallback)',
        self::ACTION_NOTIFY_STAFF => 'Notify Staff',
        self::ACTION_CHANGE_STATUS => 'Change Lead Status',
        self::ACTION_ADD_SCORE => 'Add/Remove Score Points',
    ];

    // ─── Relationships ────────────────────────────────────

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(FollowUpSequence::class, 'sequence_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    // ─── Helpers ──────────────────────────────────────────

    /**
     * Get human-readable delay description.
     */
    public function getDelayDescription(): string
    {
        $minutes = $this->delay_minutes;

        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0
                ? "{$hours}h {$remainingMinutes}m"
                : "{$hours}h";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? "{$days}d {$remainingHours}h"
            : "{$days}d";
    }
}
