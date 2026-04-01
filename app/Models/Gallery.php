<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Gallery extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'gallery';

    protected $fillable = [
        'category', 'image_path', 'video_url',
        'caption_ar', 'caption_en',
        'is_before_after', 'before_image', 'after_image',
        'display_order', 'is_visible',
    ];

    protected $casts = [
        'is_before_after' => 'boolean',
        'is_visible' => 'boolean',
    ];

    protected $appends = ['image', 'before_image_url', 'after_image_url'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? (str_starts_with($this->image_path, 'http') ? $this->image_path : '/storage/' . $this->image_path)
                : null,
        );
    }

    protected function beforeImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->before_image
                ? (str_starts_with($this->before_image, 'http') ? $this->before_image : '/storage/' . $this->before_image)
                : null,
        );
    }

    protected function afterImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->after_image
                ? (str_starts_with($this->after_image, 'http') ? $this->after_image : '/storage/' . $this->after_image)
                : null,
        );
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
