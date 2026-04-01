<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Testimonial extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'client_name_ar', 'client_name_en',
        'service_id', 'rating',
        'review_ar', 'review_en',
        'photo', 'video_url',
        'status', 'display_order',
    ];

    protected $appends = ['photo_url'];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo
                ? '/storage/' . $this->photo
                : null,
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
