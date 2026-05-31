<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSequenceEnrollment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['sequence_id', 'recipient_type', 'recipient_id', 'current_step', 'status', 'next_run_at'];

    protected $casts = ['current_step' => 'integer', 'next_run_at' => 'datetime'];

    public function sequence()
    {
        return $this->belongsTo(NotificationSequence::class, 'sequence_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
