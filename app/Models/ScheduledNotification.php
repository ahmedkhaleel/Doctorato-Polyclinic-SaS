<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledNotification extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'event_key', 'recipient_type', 'recipient_id', 'data', 'channels',
        'reason', 'send_after', 'status', 'processed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'send_after' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->morphTo();
    }
}
