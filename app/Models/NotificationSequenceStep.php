<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSequenceStep extends Model
{
    protected $fillable = ['sequence_id', 'position', 'delay_minutes', 'channel', 'subject', 'body_ar', 'body_en'];

    protected $casts = ['position' => 'integer', 'delay_minutes' => 'integer'];
}
