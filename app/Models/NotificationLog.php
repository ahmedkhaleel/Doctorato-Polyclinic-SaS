<?php

namespace App\Models;

use App\Models\Concerns\StampsBranch;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use StampsBranch;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'recipient_type', 'recipient_id', 'to', 'channel', 'provider', 'provider_ref', 'event_key',
        'campaign_id', 'ab_variant', 'template_id', 'status', 'cost', 'error', 'dedup_key', 'meta',
        'sent_at', 'delivered_at', 'read_at', 'branch_id',
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
