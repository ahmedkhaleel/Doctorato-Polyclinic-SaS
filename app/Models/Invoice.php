<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Mass-assignable fields.
     * SECURITY NOTE: paid_amount and status are intentionally excluded
     * to prevent financial fraud via mass assignment. Use recalculateStatus()
     * or direct assignment for these fields.
     */
    protected $fillable = [
        'invoice_number', 'invoice_date', 'patient_id', 'visit_id', 'booking_id',
        'package_bundle_booking_id',
        'subtotal', 'discount_amount', 'discount_code_id', 'tax_amount',
        'total', 'module', 'notes', 'created_by',
        'patient_insurance_id', 'insurance_covered', 'patient_net_amount',
        'is_installment', 'installment_count', 'installment_amount', 'next_installment_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected $appends = ['balance'];

    // ─── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function packageBundleBooking()
    {
        return $this->belongsTo(PackageBundleBooking::class);
    }

    public function discountCode()
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function patientInsurance()
    {
        return $this->belongsTo(\App\Models\PatientInsurance::class);
    }

    public function insuranceClaims()
    {
        return $this->hasMany(\App\Models\InsuranceClaim::class);
    }

    // ─── Accessors ──────────────────────────────────────

    protected function balance(): Attribute
    {
        return Attribute::get(fn () => $this->total - $this->paid_amount);
    }

    // ─── Module Scopes ────────────────────────────────────

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    // ─── Auto-generate invoice number ───────────────────

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';
        $last = static::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $number = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // ─── Update status based on payments ────────────────

    public function recalculateStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;

        if ($totalPaid >= $this->total) {
            $this->status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}
