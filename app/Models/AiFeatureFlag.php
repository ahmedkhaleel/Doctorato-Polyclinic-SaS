<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiFeatureFlag extends Model
{
    protected $fillable = [
        'key', 'enabled', 'model_override', 'label_ar', 'label_en', 'group', 'display_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'display_order' => 'integer',
    ];

    /** Is a feature enabled? Cached briefly. Unknown feature => false (safe default). */
    public static function isEnabled(string $key): bool
    {
        return (bool) Cache::remember("ai_flag:{$key}", 300, function () use ($key) {
            return (bool) static::where('key', $key)->value('enabled');
        });
    }

    public static function modelFor(string $key): ?string
    {
        return Cache::remember("ai_flag_model:{$key}", 300, function () use ($key) {
            return static::where('key', $key)->value('model_override');
        });
    }

    protected static function booted(): void
    {
        $bust = function (self $flag) {
            Cache::forget("ai_flag:{$flag->key}");
            Cache::forget("ai_flag_model:{$flag->key}");
        };
        static::saved($bust);
        static::deleted($bust);
    }
}
