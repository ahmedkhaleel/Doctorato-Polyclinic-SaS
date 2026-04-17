<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'gateway', 'gateway_reference', 'intent_id',
        'online_consultation_id', 'invoice_id', 'patient_id',
        'amount', 'currency', 'status', 'type',
        'gateway_request', 'gateway_response', 'failure_reason',
        'checkout_url', 'paid_at', 'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_request' => 'array',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (self $model) {
            if (empty($model->transaction_id)) {
                $model->transaction_id = 'TXN-' . strtoupper(Str::random(12));
            }
        });
    }

    public function onlineConsultation() { return $this->belongsTo(OnlineConsultation::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
}
