<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\HasStatusTransitions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalLabOrder extends Model
{
    use BelongsToBranch;
    use HasStatusTransitions;

    /**
     * Allowed status transitions:
     *   ordered → in_production, cancelled
     *   in_production → ready, cancelled
     *   ready → delivered, adjustment
     *   adjustment → in_production
     *   delivered → completed
     *   completed → (none — final)
     */
    const ALLOWED_TRANSITIONS = [
        'ordered' => ['in_production', 'cancelled'],
        'in_production' => ['ready', 'cancelled'],
        'ready' => ['delivered', 'adjustment'],
        'adjustment' => ['in_production'],
        'delivered' => ['completed'],
        'completed' => [],
    ];
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'dental_treatment_id',
        'invoice_item_id',
        'lab_name',
        'order_number',
        'item_type',
        'tooth_number',
        'shade',
        'material',
        'cost',
        'patient_charge',
        'status',
        'order_date',
        'expected_date',
        'delivered_date',
        'notes',
        'special_instructions',
        'notification_sent_at',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'patient_charge' => 'decimal:2',
        'order_date' => 'date',
        'expected_date' => 'date',
        'delivered_date' => 'date',
        'notification_sent_at' => 'datetime',
    ];

    const ITEM_CROWN = 'crown';
    const ITEM_BRIDGE = 'bridge';
    const ITEM_DENTURE = 'denture';
    const ITEM_RETAINER = 'retainer';
    const ITEM_ALIGNER = 'aligner';
    const ITEM_VENEER = 'veneer';
    const ITEM_IMPLANT_ABUTMENT = 'implant_abutment';
    const ITEM_NIGHT_GUARD = 'night_guard';
    const ITEM_INLAY_ONLAY = 'inlay_onlay';

    const ITEM_TYPES = [
        self::ITEM_CROWN,
        self::ITEM_BRIDGE,
        self::ITEM_DENTURE,
        self::ITEM_RETAINER,
        self::ITEM_ALIGNER,
        self::ITEM_VENEER,
        self::ITEM_IMPLANT_ABUTMENT,
        self::ITEM_NIGHT_GUARD,
        self::ITEM_INLAY_ONLAY,
    ];

    const MATERIAL_ZIRCONIA = 'zirconia';
    const MATERIAL_PORCELAIN = 'porcelain';
    const MATERIAL_EMAX = 'emax';
    const MATERIAL_METAL = 'metal';
    const MATERIAL_COMPOSITE = 'composite';
    const MATERIAL_ACRYLIC = 'acrylic';
    const MATERIAL_PFM = 'pfm';

    const MATERIALS = [
        self::MATERIAL_ZIRCONIA,
        self::MATERIAL_PORCELAIN,
        self::MATERIAL_EMAX,
        self::MATERIAL_METAL,
        self::MATERIAL_COMPOSITE,
        self::MATERIAL_ACRYLIC,
        self::MATERIAL_PFM,
    ];

    const STATUS_ORDERED = 'ordered';
    const STATUS_IN_PRODUCTION = 'in_production';
    const STATUS_READY = 'ready';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_ADJUSTMENT = 'adjustment';
    const STATUS_COMPLETED = 'completed';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(DentalTreatment::class, 'dental_treatment_id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function getProfitAttribute(): float
    {
        return $this->patient_charge - $this->cost;
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->expected_date) return false;
        return now()->greaterThan($this->expected_date) && !in_array($this->status, ['delivered', 'completed']);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', ['delivered', 'completed']);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('expected_date')
            ->where('expected_date', '<', now())
            ->whereNotIn('status', ['delivered', 'completed']);
    }
}
