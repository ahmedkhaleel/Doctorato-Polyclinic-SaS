<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'value', 'group', 'branch_id'];

    /** branch_id sentinel for a GLOBAL setting (applies to every branch). */
    public const GLOBAL_BRANCH = 0;

    /**
     * The branch whose overrides apply right now, or 0 (global) when the
     * kill-switch is off, in all-branches mode, or no branch is resolved.
     * Keeps single-clinic behaviour identical while branches are disabled.
     */
    protected static function activeBranchId(): int
    {
        if (! config('branches.enabled')) {
            return self::GLOBAL_BRANCH;
        }
        try {
            $ctx = app(\App\Services\Branch\BranchContext::class);
            if ($ctx->isAllBranches()) {
                return self::GLOBAL_BRANCH;
            }

            return (int) ($ctx->currentId() ?? self::GLOBAL_BRANCH);
        } catch (\Throwable) {
            return self::GLOBAL_BRANCH;
        }
    }

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
        // SMS Misr (اس ام اس مصر) — new keys, safe to encrypt at rest
        'sms_smsmisr_password',
        'sms_smsmisr_token',
        // WhatsApp webhook shared secret
        'whatsapp_webhook_verify_token',
        // AI / OpenAI
        'ai_openai_api_key',
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
        $branchId = self::activeBranchId();
        $memKey = $branchId ? "b{$branchId}:{$key}" : $key;

        // Layer 1: In-memory cache (same request)
        if (array_key_exists($memKey, static::$memoryCache)) {
            return static::$memoryCache[$memKey] ?? $default;
        }

        // Layer 2: Cache store (database/redis/file).
        // Branch overrides fall back to the global (branch_id = 0) value.
        $cacheKey = $branchId ? "setting:b{$branchId}:{$key}" : "setting:{$key}";
        $value = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $branchId) {
            if ($branchId !== self::GLOBAL_BRANCH) {
                $override = static::where('key', $key)->where('branch_id', $branchId)->value('value');
                if ($override !== null) {
                    return $override;
                }
            }

            return static::where('key', $key)->where('branch_id', self::GLOBAL_BRANCH)->value('value');
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
        static::$memoryCache[$memKey] = $value;

        return $value ?? $default;
    }

    /**
     * Set a setting value and bust caches.
     *
     * Writes the GLOBAL row (branch_id = 0) by default — backward compatible.
     * Pass $branchId to store a per-branch override (see setForBranch()).
     */
    public static function set(string $key, $value, string $group = 'general', int $branchId = self::GLOBAL_BRANCH): void
    {
        $storedValue = $value;

        // Auto-encrypt sensitive keys before storage
        if (in_array($key, self::ENCRYPTED_KEYS, true) && $value !== '' && $value !== null) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        static::updateOrCreate(
            ['key' => $key, 'branch_id' => $branchId],
            ['value' => $storedValue, 'group' => $group]
        );

        // Bust caches (both the global and the per-branch cache keys)
        Cache::forget("setting:{$key}");
        Cache::forget("setting:b{$branchId}:{$key}");
        unset(static::$memoryCache["b{$branchId}:{$key}"]);
        // Keep plain (decrypted) value in memory cache for same-request reads
        $memKey = $branchId ? "b{$branchId}:{$key}" : $key;
        static::$memoryCache[$memKey] = $value;

        // Also bust the "all settings" cache if it exists
        Cache::forget('settings:all');
    }

    /** Store a per-branch override for $key. */
    public static function setForBranch(int $branchId, string $key, $value, string $group = 'general'): void
    {
        self::set($key, $value, $group, $branchId);
    }

    /** Remove a branch override so the branch falls back to the global value. */
    public static function clearBranchOverride(int $branchId, string $key): void
    {
        static::where('key', $key)->where('branch_id', $branchId)->delete();
        Cache::forget("setting:b{$branchId}:{$key}");
        unset(static::$memoryCache["b{$branchId}:{$key}"]);
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
