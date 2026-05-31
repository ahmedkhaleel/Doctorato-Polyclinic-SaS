<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class MedicalCertificate extends Model
{
    use BelongsToBranch, LogsActivity;

    const TYPES = ['sick_leave', 'fitness', 'medical_report', 'referral_letter', 'follow_up'];

    protected $fillable = [
        'certificate_number', 'patient_id', 'doctor_id', 'visit_id', 'created_by',
        'type', 'issue_date', 'start_date', 'end_date', 'days',
        'diagnosis', 'notes', 'recommendations', 'status', 'issued_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'issued_at' => 'datetime',
    ];

    public static function generateNumber(): string
    {
        $prefix = \App\Services\Branch\BranchNumber::prefix('MC');
        $last = static::where('certificate_number', 'like', $prefix.'%')
            ->orderByDesc('certificate_number')
            ->value('certificate_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
