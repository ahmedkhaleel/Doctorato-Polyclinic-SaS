<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationConsent extends Model
{
    protected $fillable = [
        'recipient_type', 'recipient_id', 'channel', 'category', 'opted_in', 'source', 'ip',
    ];

    protected $casts = ['opted_in' => 'boolean'];

    public function recipient()
    {
        return $this->morphTo();
    }
}
