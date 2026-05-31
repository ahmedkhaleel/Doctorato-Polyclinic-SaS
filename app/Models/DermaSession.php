<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class DermaSession extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const TYPES = ['laser', 'peel', 'phototherapy', 'injection', 'cryotherapy', 'other'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'visit_id', 'treatment_plan_id',
        'session_type', 'area_treated', 'product_used',
        'settings_json', 'session_number', 'total_sessions',
        'cost', 'invoice_id', 'invoice_item_id', 'completed_at', 'next_session_date', 'notes',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'cost' => 'decimal:2',
        'completed_at' => 'datetime',
        'next_session_date' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function treatmentPlan() { return $this->belongsTo(DermaTreatmentPlan::class, 'treatment_plan_id'); }
    public function photos() { return $this->hasMany(DermaPhoto::class, 'session_id'); }
}
