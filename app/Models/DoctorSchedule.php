<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class DoctorSchedule extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'doctor_id', 'day_of_week', 'start_time', 'end_time', 'is_active',
        'mode', 'slot_duration_minutes', 'buffer_minutes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
        'slot_duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
    ];

    public const DAYS = [
        0 => 'Saturday',
        1 => 'Sunday',
        2 => 'Monday',
        3 => 'Tuesday',
        4 => 'Wednesday',
        5 => 'Thursday',
        6 => 'Friday',
    ];

    public const MODE_IN_PERSON = 'in_person';
    public const MODE_ONLINE = 'online';
    public const MODE_BOTH = 'both';

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function supportsOnline(): bool
    {
        return in_array($this->mode, [self::MODE_ONLINE, self::MODE_BOTH], true);
    }

    public function supportsInPerson(): bool
    {
        return in_array($this->mode, [self::MODE_IN_PERSON, self::MODE_BOTH], true);
    }
}
