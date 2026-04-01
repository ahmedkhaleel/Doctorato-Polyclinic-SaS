<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalPrescriptionTemplate extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'treatment_type',
        'diagnosis_ar',
        'diagnosis_en',
        'notes_ar',
        'notes_en',
        'auto_apply',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'auto_apply' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(DentalPrescriptionTemplateItem::class, 'template_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoApply($query)
    {
        return $query->where('auto_apply', true);
    }

    public function scopeForTreatmentType($query, string $type)
    {
        return $query->where('treatment_type', $type);
    }
}
