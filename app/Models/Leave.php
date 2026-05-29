<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Leave extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'leave_type', 'start_date', 'end_date',
        'reason', 'status', 'approved_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Paid leave types don't deduct from salary. */
    public function isPaid(): bool
    {
        return in_array($this->leave_type, ['annual', 'sick', 'personal'], true);
    }

    /** Count calendar days in this leave (inclusive). */
    public function totalDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Shortcut: the employee record linked through the leave's user. */
    public function employee()
    {
        return $this->user?->employee ?? null;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
