<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = ['name', 'language', 'event_key', 'variables', 'body_preview', 'is_active'];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];
}
