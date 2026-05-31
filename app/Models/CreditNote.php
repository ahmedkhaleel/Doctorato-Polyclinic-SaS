<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use BelongsToBranch, HasFactory;

    const TYPES = ['full_refund', 'partial_refund', 'adjustment', 'cancellation'];

    const ALLOWED_TRANSITIONS = [
        'draft' => ['pending_approval', 'approved'],
        'pending_approval' => ['approved', 'rejected'],
        'approved' => ['refunded'],
    ];

    protected $fillable = [
        'credit_note_number', 'invoice_id', 'patient_id', 'created_by', 'approved_by',
        'type', 'status', 'amount', 'reason', 'notes',
        'refund_method', 'approved_at', 'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateNumber(): string
    {
        $prefix = 'CN-'.now()->format('Ym').'-';
        $last = static::where('credit_note_number', 'like', $prefix.'%')
            ->orderByDesc('credit_note_number')
            ->value('credit_note_number');
        $number = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::ALLOWED_TRANSITIONS[$this->status] ?? []);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }
}
