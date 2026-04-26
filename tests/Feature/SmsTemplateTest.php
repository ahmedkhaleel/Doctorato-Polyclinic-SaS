<?php

namespace Tests\Feature;

use App\Models\SmsTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Pins SmsTemplate behavior:
 *  - Variable substitution
 *  - Locale fallback (missing en → ar)
 *  - Missing key → null + warning (no crash)
 *  - Cache busts on save
 *  - Inactive templates ignored
 */
class SmsTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_substitutes_placeholders(): void
    {
        SmsTemplate::create([
            'key'      => 'test_key',
            'category' => 'bookings',
            'body_ar'  => 'مرحباً {{name}}، موعدك {{date}}',
            'body_en'  => 'Hi {{name}}, your appointment is {{date}}',
            'is_active' => true,
        ]);

        $ar = SmsTemplate::render('test_key', ['name' => 'سارة', 'date' => '2026-05-01'], 'ar');
        $this->assertSame('مرحباً سارة، موعدك 2026-05-01', $ar);

        $en = SmsTemplate::render('test_key', ['name' => 'Sara', 'date' => '2026-05-01'], 'en');
        $this->assertSame('Hi Sara, your appointment is 2026-05-01', $en);
    }

    public function test_render_falls_back_to_arabic_when_english_missing(): void
    {
        SmsTemplate::create([
            'key'      => 'ar_only',
            'category' => 'bookings',
            'body_ar'  => 'النص العربي',
            'body_en'  => '',  // empty
            'is_active' => true,
        ]);

        $this->assertSame('النص العربي', SmsTemplate::render('ar_only', [], 'en'));
    }

    public function test_render_returns_null_for_missing_key(): void
    {
        $this->assertNull(SmsTemplate::render('does_not_exist'));
    }

    public function test_render_uses_fallback_when_provided_for_missing_key(): void
    {
        $this->assertSame(
            'fallback string',
            SmsTemplate::render('still_missing', [], 'ar', 'fallback string')
        );
    }

    public function test_render_skips_inactive_templates(): void
    {
        SmsTemplate::create([
            'key'      => 'inactive_one',
            'category' => 'bookings',
            'body_ar'  => 'should not appear',
            'body_en'  => 'should not appear',
            'is_active' => false,
        ]);

        $this->assertNull(SmsTemplate::render('inactive_one'));
    }

    public function test_cache_is_busted_on_save(): void
    {
        $t = SmsTemplate::create([
            'key' => 'cached_one', 'category' => 'bookings',
            'body_ar' => 'النسخة الأولى', 'body_en' => 'V1',
            'is_active' => true,
        ]);

        // First read — populates cache
        $this->assertSame('V1', SmsTemplate::render('cached_one', [], 'en'));

        // Update body — saved() hook should bust the cache
        $t->update(['body_en' => 'V2']);

        $this->assertSame('V2', SmsTemplate::render('cached_one', [], 'en'));
    }
}
