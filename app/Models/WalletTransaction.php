<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    const TYPES = ['deposit', 'withdrawal', 'refund_credit', 'payment', 'adjustment'];

    protected $fillable = [
        'patient_id', 'type', 'amount', 'balance_after',
        'description', 'reference_type', 'reference_id',
        'created_by', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
