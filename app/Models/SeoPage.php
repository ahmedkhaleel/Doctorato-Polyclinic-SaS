<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SeoPage extends Model
{
    use LogsActivity;
    protected $fillable = [
        'page_identifier',
        'page_name_en',
        'page_name_ar',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'keywords',
        'og_image',
        'canonical_url',
        'is_indexable',
        'structured_data',
    ];

    protected $casts = [
        'is_indexable' => 'boolean',
        'structured_data' => 'array',
    ];

    /**
     * Get a SeoPage by its page identifier.
     */
    public static function getByIdentifier(string $identifier): ?self
    {
        return static::where('page_identifier', $identifier)->first();
    }
}
