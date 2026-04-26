<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPoint extends Model
{
    protected $fillable = [
        'patient_id', 'points', 'type', 'description',
        'reference_type', 'reference_id',
        'expires_at', 'admin_user_id',
    ];

    protected $casts = [
        'points'     => 'integer',
        'expires_at' => 'datetime',
    ];

    public const TYPE_EARN   = 'earn';
    public const TYPE_REDEEM = 'redeem';
    public const TYPE_EXPIRE = 'expire';
    public const TYPE_ADJUST = 'adjust';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
