<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class SkinCondition extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    const CATEGORIES = ['acne', 'psoriasis', 'eczema', 'melasma', 'vitiligo', 'rosacea', 'dermatitis', 'fungal', 'other'];
    const SEVERITIES = ['mild', 'moderate', 'severe'];
    const STATUSES = ['active', 'resolved', 'monitoring'];

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id',
        'name_ar', 'name_en', 'category', 'severity',
        'body_area', 'diagnosed_at', 'status', 'notes',
    ];

    protected $casts = [
        'diagnosed_at' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeForPatient($q, $id) { return $q->where('patient_id', $id); }
}
