<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePreAuthorization extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'auth_number', 'patient_insurance_id', 'patient_id', 'doctor_id',
        'procedure_description', 'icd_code', 'cpt_code', 'estimated_cost',
        'status', 'approved_amount', 'valid_from', 'valid_until',
        'conditions', 'rejection_reason', 'requested_by',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    protected $appends = ['is_valid'];

    public function patientInsurance()
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getIsValidAttribute(): bool
    {
        return $this->status === 'approved'
            && (!$this->valid_until || $this->valid_until->isFuture());
    }

    public static function generateAuthNumber(): string
    {
        $prefix = \App\Services\Branch\BranchNumber::prefix('PA');
        $last = static::where('auth_number', 'like', $prefix . '%')
            ->orderByDesc('auth_number')
            ->value('auth_number');
        $number = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });
    }
}
