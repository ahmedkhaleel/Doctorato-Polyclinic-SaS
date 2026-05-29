<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Keys whose values are encrypted at rest.
     * Accessed via get()/set() auto-(en|de)crypts transparently.
     */
    public const ENCRYPTED_KEYS = [
        // Agora
        'agora_app_id',
        'agora_app_certificate',
        'agora_customer_key',
        'agora_customer_secret',
        // Paymob
        'paymob_api_key',
        'paymob_hmac_secret',
        'paymob_iframe_id',
        'paymob_integration_id',
        // Stripe
        'stripe_secret_key',
        'stripe_webhook_secret',
        'stripe_publishable_key',
        // Reverb
        'reverb_app_key',
        'reverb_app_secret',
    ];

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

        // Auto-decrypt encrypted keys (gracefully handle legacy plain-text values)
        if ($value !== null && $value !== '' && in_array($key, self::ENCRYPTED_KEYS, true)) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Throwable) {
                // Legacy plain-text value or corrupted payload — return as-is
            }
        }

        // Store in memory for repeated calls within same request
        static::$memoryCache[$key] = $value;

        return $value ?? $default;
    }

    /**
     * Set a setting value and bust caches.
     */
    public static function set(string $key, $value, string $group = 'general'): void
    {
        $storedValue = $value;

        // Auto-encrypt sensitive keys before storage
        if (in_array($key, self::ENCRYPTED_KEYS, true) && $value !== '' && $value !== null) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'group' => $group]
        );

        // Bust caches
        Cache::forget("setting:{$key}");
        // Keep plain (decrypted) value in memory cache for same-request reads
        static::$memoryCache[$key] = $value;

        // Also bust the "all settings" cache if it exists
        Cache::forget('settings:all');
    }

    /**
     * Whether the setting has a non-empty value stored.
     */
    public static function hasValue(string $key): bool
    {
        $value = self::get($key);

        return $value !== null && $value !== '';
    }

    /**
     * Return a masked placeholder string when a secret is set, empty otherwise.
     */
    public static function maskedValue(string $key): string
    {
        return self::hasValue($key) ? '••••••••••••' : '';
    }

    /**
     * Whether a given key is treated as encrypted.
     */
    public static function isEncryptedKey(string $key): bool
    {
        return in_array($key, self::ENCRYPTED_KEYS, true);
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

        // CRITICAL: decrypt encrypted keys before seeding the memory cache.
        // get() short-circuits on the in-memory layer (Layer 1) and returns the
        // stored value WITHOUT running the decrypt step. Since the DB / settings:all
        // cache holds ciphertext for ENCRYPTED_KEYS, seeding it raw would make every
        // post-preload get('agora_app_id'|'paymob_api_key'|'stripe_secret_key'|…)
        // return ciphertext, silently breaking payments, video, and broadcasting.
        // Mirror get()'s decrypt logic here so all three cache layers agree.
        foreach (self::ENCRYPTED_KEYS as $key) {
            if (isset($settings[$key]) && $settings[$key] !== '') {
                try {
                    $settings[$key] = Crypt::decryptString($settings[$key]);
                } catch (\Throwable) {
                    // Legacy plain-text or corrupted payload — keep as-is.
                }
            }
        }

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
