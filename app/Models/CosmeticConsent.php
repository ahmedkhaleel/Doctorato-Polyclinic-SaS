<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CosmeticConsent extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'procedure_id', 'session_id',
        'consent_text', 'signed_at', 'signature_path', 'witnessed_by',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function procedure() { return $this->belongsTo(CosmeticProcedure::class, 'procedure_id'); }
    public function session() { return $this->belongsTo(CosmeticSession::class, 'session_id'); }
}
