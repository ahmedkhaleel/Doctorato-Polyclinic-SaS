<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\LogsActivity;

class Setting extends Model
{
    use LogsActivity;
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Cache TTL in seconds (30 minutes).
     * Settings rarely change, so aggressive caching is safe.
     */
    const CACHE_TTL = 1800;

    /**
     * In-memory cache for the current request (avoids repeated Cache::get calls).
     */
    protected static array $memoryCache = [];

    /**
     * Get a setting value with multi-layer caching:
     * 1. In-memory (same request) → 2. Cache store → 3. Database
     */
    public static function get(string $key, $default = null)
    {
        // Layer 1: In-memory cache (same request)
        if (array_key_exists($key, static::$memoryCache)) {
            return static::$memoryCache[$key] ?? $default;
        }

        // Layer 2: Cache store (database/redis/file)
        $value = Cache::remember("setting:{$key}", self::CACHE_TTL, function () use ($key) {
            return static::where('key', $key)->value('value');
        });

        // Store in memory for repeated calls within same request
        static::$memoryCache[$key] = $value;

        return $value ?? $default;
    }

    /**
     * Set a setting value and bust caches.
     */
    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        // Bust caches
        Cache::forget("setting:{$key}");
        static::$memoryCache[$key] = $value;

        // Also bust the "all settings" cache if it exists
        Cache::forget('settings:all');
    }

    /**
     * Preload all settings into memory (call once at boot for heavy pages).
     * Reduces N queries to 1 query + 1 cache hit.
     */
    public static function preload(): void
    {
        $settings = Cache::remember('settings:all', self::CACHE_TTL, function () {
            return static::pluck('value', 'key')->toArray();
        });

        static::$memoryCache = array_merge(static::$memoryCache, $settings);
    }

    /**
     * Clear all setting caches.
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("setting:{$key}");
            unset(static::$memoryCache[$key]);
        } else {
            // Clear all known setting caches
            foreach (static::$memoryCache as $k => $v) {
                Cache::forget("setting:{$k}");
            }
            static::$memoryCache = [];
            Cache::forget('settings:all');
        }
    }
}

