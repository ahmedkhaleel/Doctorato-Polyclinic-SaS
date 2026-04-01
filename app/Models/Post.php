<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\LogsActivity;

class Post extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'category_id', 'author_id', 'title_ar', 'title_en', 'slug',
        'excerpt_ar', 'excerpt_en', 'content_ar', 'content_en',
        'featured_image', 'seo_title_ar', 'seo_title_en',
        'seo_desc_ar', 'seo_desc_en', 'status', 'is_featured', 'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = ['image_url'];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->featured_image
                ? '/storage/' . $this->featured_image
                : null,
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getReadingTimeAttribute(): int
    {
        $locale = app()->getLocale();
        $content = $locale === 'ar' ? $this->content_ar : $this->content_en;
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int) ceil($wordCount / 200));
    }
}
