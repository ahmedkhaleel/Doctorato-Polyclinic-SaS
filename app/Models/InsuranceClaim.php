<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatusTransitions;

class InsuranceClaim extends Model
{
    use BelongsToBranch;
    use HasFactory, HasStatusTransitions;

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_PARTIALLY_APPROVED = 'partially_approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';

    const ALLOWED_TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_review', 'approved', 'rejected'],
        'under_review' => ['approved', 'partially_approved', 'rejected'],
        'approved' => ['paid', 'partially_paid'],
        'partially_approved' => ['paid', 'partially_paid'],
        'rejected' => ['draft'],
        'paid' => [],
        'partially_paid' => ['paid'],
    ];

    protected $fillable = [
        'claim_number', 'patient_insurance_id', 'patient_id',
        'invoice_id', 'visit_id', 'payment_id', 'pre_authorization_id',
        'service_date', 'diagnosis', 'services_description',
        'total_amount', 'covered_amount', 'patient_share',
        'approved_amount', 'paid_amount',
        'status', 'submitted_at', 'approved_at', 'paid_at',
        'rejection_reason', 'notes', 'reference_number', 'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'submitted_at' => 'date',
        'approved_at' => 'date',
        'paid_at' => 'date',
        'total_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────

    public function patientInsurance()
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function preAuthorization()
    {
        return $this->belongsTo(InsurancePreAuthorization::class, 'pre_authorization_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Auto-generate claim number ─────────────────────

    public static function generateClaimNumber(): string
    {
        $prefix = 'CLM-' . now()->format('Ym') . '-';
        $last = static::where('claim_number', 'like', $prefix . '%')
            ->orderByDesc('claim_number')
            ->value('claim_number');

        $number = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'submitted', 'under_review']);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['approved', 'partially_approved', 'partially_paid']);
    }
}
