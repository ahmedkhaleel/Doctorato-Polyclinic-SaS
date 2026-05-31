<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'recipient_type', 'recipient_id', 'to', 'channel', 'provider', 'event_key',
        'template_id', 'status', 'cost', 'error', 'dedup_key', 'meta',
        'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'cost' => 'decimal:4',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->morphTo();
    }
}
