<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use BelongsToBranch, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['visit_id', 'patient_id', 'doctor_id', 'dental_treatment_id', 'diagnosis', 'notes'];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function dentalTreatment()
    {
        return $this->belongsTo(DentalTreatment::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('sort_order');
    }
}
