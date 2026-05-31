<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannel extends Model
{
    protected $fillable = ['channel', 'enabled', 'provider', 'config', 'from_name', 'daily_cap', 'monthly_cap'];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'encrypted:array', // credentials encrypted at rest
        'daily_cap' => 'integer',
        'monthly_cap' => 'integer',
    ];

    public static function for(string $channel): ?self
    {
        return static::where('channel', $channel)->first();
    }

    public function isUsable(): bool
    {
        return $this->enabled;
    }
}
