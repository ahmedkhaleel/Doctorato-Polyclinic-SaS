<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
    use BelongsToBranch, HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'shift_id', 'day_of_week', 'effective_from', 'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
