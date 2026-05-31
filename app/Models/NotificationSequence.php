<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSequence extends Model
{
    protected $fillable = ['name', 'trigger_event', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function steps()
    {
        return $this->hasMany(NotificationSequenceStep::class, 'sequence_id')->orderBy('position');
    }

    public function enrollments()
    {
        return $this->hasMany(NotificationSequenceEnrollment::class, 'sequence_id');
    }
}
