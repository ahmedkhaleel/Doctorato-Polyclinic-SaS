<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\LogsActivity;

class PackageBundle extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name_ar', 'name_en', 'description_ar', 'description_en',
        'total_price', 'original_price', 'is_active', 'image', 'display_order',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url', 'savings'];

    // ─── Relationships ──────────────────────────────────

    public function services()
    {
        return $this->hasMany(PackageBundleService::class)->orderBy('display_order');
    }

    public function bundleBookings()
    {
        return $this->hasMany(PackageBundleBooking::class);
    }

    // ─── Accessors ──────────────────────────────────────

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image ? '/storage/' . $this->image : null);
    }

    protected function savings(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->original_price - $this->total_price));
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
