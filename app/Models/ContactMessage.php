<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ContactMessage extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
