<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientWallet extends Model
{
    protected $fillable = ['patient_id', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'patient_id', 'patient_id');
    }
}
