<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** NP5 — a neurology procedure (EMG/LP/EEG/botox/nerve block). Branch-scoped. */
class NeuroProcedure extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const TYPES = ['emg_ncs', 'lumbar_puncture', 'eeg', 'botox', 'nerve_block'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'type', 'performed_at', 'findings', 'report_path',
        'cost', 'invoice_id', 'invoice_item_id', 'supply_id', 'consumption_qty',
        'supply_transaction_id', 'completed_at', 'notes',
    ];

    protected $casts = [
        'performed_at' => 'date',
        'findings' => 'array',
        'cost' => 'decimal:2',
        'consumption_qty' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function supplyTransaction()
    {
        return $this->belongsTo(SupplyTransaction::class);
    }
}
