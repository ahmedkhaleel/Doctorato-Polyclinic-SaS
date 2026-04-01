<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalPrescriptionTemplateItem extends Model
{
    protected $fillable = [
        'template_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'instructions_ar',
        'instructions_en',
        'sort_order',
    ];

    public function template()
    {
        return $this->belongsTo(DentalPrescriptionTemplate::class, 'template_id');
    }
}
