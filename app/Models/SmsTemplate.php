<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SMS template editable from admin UI. Holds AR + EN bodies and a
 * stable lookup key. SmsService and any caller should use:
 *
 *   SmsTemplate::render('booking_confirmed', [
 *     'patient_name' => 'Sara',
 *     'date'         => '2026-05-01',
 *     'time'         => '14:30',
 *   ], 'ar');
 *
 * Templates are cached for 30 minutes; clearCache() / a model save
 * invalidates the cache. Falls back to a hard-coded default when the
 * key isn't seeded.
 */
class SmsTemplate extends Model
{
    protected $fillable = [
        'key', 'category', 'body_ar', 'body_en', 'description',
        'placeholders', 'is_active',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active'    => 'boolean',
    ];

    public const CATEGORIES = ['bookings', 'reminders', 'marketing'];

    protected static function booted(): void
    {
        static::saved(fn ($t) => Cache::forget("sms_template:{$t->key}"));
        static::deleted(fn ($t) => Cache::forget("sms_template:{$t->key}"));
    }

    /**
     * Look up a template by key, render placeholders, and return the
     * final string. Defaults to AR if locale is missing or empty.
     *
     * @param  string  $key       Template key
     * @param  array   $vars      ['patient_name' => 'X', ...]
     * @param  string  $locale    'ar' | 'en'
     * @param  ?string $fallback  Hard-coded fallback if template missing
     */
    public static function render(string $key, array $vars = [], string $locale = 'ar', ?string $fallback = null): ?string
    {
        $template = Cache::remember("sms_template:{$key}", 1800, function () use ($key) {
            return static::where('key', $key)->where('is_active', true)->first();
        });

        $body = match (true) {
            $template === null && $fallback !== null => $fallback,
            $template === null                       => null,
            default => ($locale === 'en' && ! empty($template->body_en))
                ? $template->body_en
                : $template->body_ar,
        };

        if ($body === null) {
            Log::warning('[sms-template] missing key', ['key' => $key]);
            return null;
        }

        // Replace {{variable}} placeholders
        foreach ($vars as $k => $v) {
            $body = str_replace('{{' . $k . '}}', (string) $v, $body);
        }
        return $body;
    }
}
