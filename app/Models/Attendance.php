<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Attendance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'date', 'check_in', 'check_in_lat', 'check_in_lng',
        'check_out', 'check_out_lat', 'check_out_lng',
        'status', 'overtime_hours', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'overtime_hours' => 'decimal:2',
        'check_in_lat' => 'decimal:7',
        'check_in_lng' => 'decimal:7',
        'check_out_lat' => 'decimal:7',
        'check_out_lng' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
