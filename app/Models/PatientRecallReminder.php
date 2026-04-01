<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRecallReminder extends Model
{
    protected $fillable = [
        'patient_id', 'module', 'type', 'last_visit_date',
        'reminder_sent_at', 'sms_status', 'notes',
    ];

    protected $casts = [
        'last_visit_date' => 'date',
        'reminder_sent_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
