<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id', 'name_ar', 'name_en', 'plan_code', 'class',
        'coverage_percentage', 'max_coverage_amount', 'copay_amount', 'deductible',
        'covers_dental', 'covers_dermatology', 'covers_cosmetic',
        'covers_lab', 'covers_xray', 'covers_medication',
        'exclusions', 'notes', 'is_active',
    ];

    protected $casts = [
        'coverage_percentage' => 'decimal:2',
        'max_coverage_amount' => 'decimal:2',
        'copay_amount' => 'decimal:2',
        'deductible' => 'decimal:2',
        'covers_dental' => 'boolean',
        'covers_dermatology' => 'boolean',
        'covers_cosmetic' => 'boolean',
        'covers_lab' => 'boolean',
        'covers_xray' => 'boolean',
        'covers_medication' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a specific service type is covered.
     */
    public function coversServiceType(string $module): bool
    {
        return match ($module) {
            'dental' => $this->covers_dental,
            'dermatology', 'derma' => $this->covers_dermatology,
            'cosmetic' => $this->covers_cosmetic,
            default => true,
        };
    }
}
