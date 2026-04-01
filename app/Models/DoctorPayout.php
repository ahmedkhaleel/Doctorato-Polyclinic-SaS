<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class DoctorPayout extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payout_number', 'doctor_id', 'period_start', 'period_end',
        'total_visits', 'total_revenue', 'total_commission',
        'deductions', 'deduction_notes', 'net_amount', 'status', 'notes',
        'confirmed_at', 'confirmed_by', 'paid_at', 'paid_by',
        'payment_method', 'payment_reference',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_revenue' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'status_color'];

    // ─── Relationships ──────────────────────────────────

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visits()
    {
        return $this->belongsToMany(Visit::class, 'doctor_payout_visits')
            ->withPivot('visit_amount', 'commission_rate', 'commission_amount')
            ->withTimestamps();
    }

    public function confirmedByUser()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Accessors ──────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => match ($this->status) {
            'draft' => 'Draft',
            'confirmed' => 'Confirmed',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        });
    }

    protected function statusColor(): Attribute
    {
        return Attribute::get(fn () => match ($this->status) {
            'draft' => 'gray',
            'confirmed' => 'amber',
            'paid' => 'emerald',
            'cancelled' => 'red',
            default => 'gray',
        });
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'confirmed', 'paid']);
    }

    // ─── Auto-generate payout number ────────────────────

    public static function generatePayoutNumber(): string
    {
        $prefix = 'PAY-' . now()->format('Ym') . '-';
        $last = static::where('payout_number', 'like', $prefix . '%')
            ->orderByDesc('payout_number')
            ->value('payout_number');

        $number = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // ─── Helpers ────────────────────────────────────────

    public function recalculateTotals(): void
    {
        $pivotData = $this->visits()->get();

        $this->update([
            'total_visits' => $pivotData->count(),
            'total_revenue' => $pivotData->sum('pivot.visit_amount'),
            'total_commission' => $pivotData->sum('pivot.commission_amount'),
            'net_amount' => $pivotData->sum('pivot.commission_amount') - $this->deductions,
        ]);
    }
}
