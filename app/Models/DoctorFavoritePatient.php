<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorFavoritePatient extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'doctor_id',
        'patient_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Only the created_at column is used (no updated_at).
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
