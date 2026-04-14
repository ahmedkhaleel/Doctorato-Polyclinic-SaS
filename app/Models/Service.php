<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsActivity;

class Service extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'category_id', 'name_ar', 'name_en', 'slug',
        'short_desc_ar', 'short_desc_en', 'full_desc_ar', 'full_desc_en',
        'icon', 'featured_image', 'benefits_ar', 'benefits_en',
        'sessions_count', 'results_ar', 'results_en',
        'display_order', 'status', 'module', 'show_on_home', 'show_on_website', 'bookable',
        'seo_title_ar', 'seo_title_en', 'seo_desc_ar', 'seo_desc_en',
        // Clinic management fields
        'price', 'price_after_discount', 'default_sessions',
        'session_duration_minutes', 'supply_cost', 'medical_fee',
        'doctor_commission_percentage', 'clinic_notes',
    ];

    protected $casts = [
        'show_on_home' => 'boolean',
        'show_on_website' => 'boolean',
        'bookable' => 'boolean',
        'price' => 'decimal:2',
        'price_after_discount' => 'decimal:2',
        'supply_cost' => 'decimal:2',
        'medical_fee' => 'decimal:2',
    ];

    protected $appends = ['image'];

    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            if ($service->medical_fee !== null) {
                $service->price = (float) ($service->supply_cost ?? 0) + (float) $service->medical_fee;
            }
        });
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->featured_image
                ? (str_starts_with($this->featured_image, 'http') ? $this->featured_image : '/storage/' . $this->featured_image)
                : null,
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ServiceGallery::class)->orderBy('display_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ServiceFaq::class)->orderBy('display_order');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHomepage($query)
    {
        return $query->where('show_on_home', true);
    }

    public function scopeWebsite($query)
    {
        return $query->where('show_on_website', true);
    }

    public function scopeBookable($query)
    {
        return $query->where('bookable', true);
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeDental($query)
    {
        return $query->where('module', 'dental');
    }

    public function scopeDerma($query)
    {
        return $query->where('module', 'derma');
    }

    public function scopePediatric($query)
    {
        return $query->where('module', 'pediatric');
    }

    // ─── Clinic Relationships ───────────────────────────

    public function serviceSupplies()
    {
        return $this->hasMany(ServiceSupply::class);
    }

    public function doctorServiceRates()
    {
        return $this->hasMany(DoctorServiceRate::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
