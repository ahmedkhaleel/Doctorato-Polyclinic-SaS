<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en', 'code', 'phone', 'email',
        'contact_person', 'logo', 'address', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public function plans()
    {
        return $this->hasMany(InsurancePlan::class);
    }

    public function patientInsurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo ? '/storage/' . $this->logo : null);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
