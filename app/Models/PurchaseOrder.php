<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use BelongsToBranch, HasFactory;

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_ORDERED = 'ordered';

    const STATUS_PARTIAL = 'partially_received';

    const STATUS_RECEIVED = 'received';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'po_number', 'supplier_id', 'created_by', 'approved_by',
        'status', 'subtotal', 'tax_amount', 'total',
        'order_date', 'expected_delivery_date', 'received_date',
        'notes', 'delivery_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Recalculate totals from items.
     */
    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('total_price');
        $this->total = $this->subtotal + $this->tax_amount;
        $this->save();
    }

    /**
     * Check if all items are fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->items()->whereColumn('quantity_received', '<', 'quantity_ordered')->doesntExist();
    }

    public static function generatePoNumber(): string
    {
        $prefix = \App\Services\Branch\BranchNumber::prefix('PO');
        $last = static::where('po_number', 'like', $prefix.'%')
            ->orderByDesc('po_number')
            ->value('po_number');
        $number = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'received']);
    }
}
