<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalComparison extends Model
{
    use BelongsToBranch;
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'treatment_plan_id',
        'title_ar',
        'title_en',
        'description',
        'category',
        'before_image_path',
        'before_date',
        'before_notes',
        'after_image_path',
        'after_date',
        'after_notes',
        'tooth_numbers',
        'is_visible_to_patient',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'before_date' => 'date',
        'after_date' => 'date',
        'is_visible_to_patient' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['before_image_url', 'after_image_url'];

    const CATEGORY_ORTHODONTIC = 'orthodontic';
    const CATEGORY_COSMETIC = 'cosmetic';
    const CATEGORY_IMPLANT = 'implant';
    const CATEGORY_WHITENING = 'whitening';
    const CATEGORY_RESTORATION = 'restoration';
    const CATEGORY_SURGICAL = 'surgical';
    const CATEGORY_XRAY = 'xray';
    const CATEGORY_OTHER = 'other';

    const CATEGORIES = [
        self::CATEGORY_ORTHODONTIC,
        self::CATEGORY_COSMETIC,
        self::CATEGORY_IMPLANT,
        self::CATEGORY_WHITENING,
        self::CATEGORY_RESTORATION,
        self::CATEGORY_SURGICAL,
        self::CATEGORY_XRAY,
        self::CATEGORY_OTHER,
    ];

    // ─── Relationships ──────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(DentalTreatmentPlan::class, 'treatment_plan_id');
    }

    // ─── Accessors ──────────────────────────────────────────

    public function getBeforeImageUrlAttribute(): ?string
    {
        if (!$this->before_image_path) return null;
        if (str_starts_with($this->before_image_path, 'http')) return $this->before_image_path;
        return '/storage/' . $this->before_image_path;
    }

    public function getAfterImageUrlAttribute(): ?string
    {
        if (!$this->after_image_path) return null;
        if (str_starts_with($this->after_image_path, 'http')) return $this->after_image_path;
        return '/storage/' . $this->after_image_path;
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeVisibleToPatient($query)
    {
        return $query->where('is_visible_to_patient', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function getToothNumbersArray(): array
    {
        if (!$this->tooth_numbers) return [];
        return array_map('trim', explode(',', $this->tooth_numbers));
    }

    public function getDurationText(): ?string
    {
        if (!$this->before_date || !$this->after_date) return null;
        $diff = $this->before_date->diff($this->after_date);
        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ($diff->m > 0 ? " {$diff->m} months" : '');
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
    }
}
