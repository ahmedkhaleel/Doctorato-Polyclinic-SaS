<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEvent extends Model
{
    protected $fillable = ['key', 'label_ar', 'label_en', 'category', 'default_channels', 'is_active'];

    protected $casts = [
        'default_channels' => 'array',
        'is_active' => 'boolean',
    ];

    public function routes()
    {
        return $this->hasMany(NotificationChannelRoute::class, 'event_key', 'key');
    }
}
