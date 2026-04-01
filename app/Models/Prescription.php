<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Prescription extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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
