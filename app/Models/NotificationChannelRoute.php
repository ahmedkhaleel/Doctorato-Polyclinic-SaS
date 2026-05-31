<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannelRoute extends Model
{
    protected $fillable = ['event_key', 'channel', 'enabled', 'priority'];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
    ];
}
