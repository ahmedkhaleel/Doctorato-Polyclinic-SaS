<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'name', 'channel', 'subject', 'body_ar', 'body_en', 'rules',
        'status', 'scheduled_at', 'sent_at', 'audience_count', 'sent_count', 'created_by',
        'ab_enabled', 'subject_b', 'body_ar_b', 'body_en_b',
    ];

    protected $casts = [
        'ab_enabled' => 'boolean',
        'rules' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
