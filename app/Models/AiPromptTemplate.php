<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptTemplate extends Model
{
    protected $fillable = [
        'feature', 'locale', 'system_prompt', 'user_template', 'version', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    /** Resolve a template for a feature+locale, falling back to 'ar' then any. */
    public static function resolve(string $feature, string $locale = 'ar'): ?self
    {
        return static::where('feature', $feature)->where('locale', $locale)->where('is_active', true)->first()
            ?? static::where('feature', $feature)->where('is_active', true)->first();
    }
}
