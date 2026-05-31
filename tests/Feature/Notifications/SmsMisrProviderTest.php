<?php

namespace Tests\Feature\Notifications;

use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsMisrProviderTest extends TestCase
{
    use RefreshDatabase;

    private function configureSmsMisr(): void
    {
        Setting::set('sms_provider', 'smsmisr');
        Setting::set('sms_smsmisr_username', 'user');
        Setting::set('sms_smsmisr_password', 'pass');
        Setting::set('sms_smsmisr_sender', 'Doctorato');
        Setting::set('sms_smsmisr_environment', '1');
    }

    public function test_sends_successfully_on_code_1901(): void
    {
        $this->configureSmsMisr();
        Http::fake(['smsmisr.com/*' => Http::response(['code' => '1901', 'SMSID' => '123'], 200)]);

        $result = SmsService::send('01012345678', 'مرحبا بك في عيادة دكتوراتو');

        $this->assertTrue($result['success']);
        $this->assertSame('smsmisr', $result['provider']);
    }

    public function test_fails_on_error_code(): void
    {
        $this->configureSmsMisr();
        Http::fake(['smsmisr.com/*' => Http::response(['code' => '1903'], 200)]); // invalid credentials

        $result = SmsService::send('01012345678', 'test');

        $this->assertFalse($result['success']);
        $this->assertSame('smsmisr', $result['provider']);
        $this->assertStringContainsString('1903', $result['message']);
    }

    public function test_missing_credentials_short_circuits(): void
    {
        Setting::set('sms_provider', 'smsmisr');
        // no username/password/sender configured
        Http::fake();

        $result = SmsService::send('01012345678', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not configured', $result['message']);
        Http::assertNothingSent();
    }

    public function test_arabic_body_flagged_as_unicode_language(): void
    {
        $this->configureSmsMisr();
        Http::fake(['smsmisr.com/*' => Http::response(['code' => '1901', 'SMSID' => '1'], 200)]);

        SmsService::send('01012345678', 'رسالة عربية');

        Http::assertSent(fn ($request) => $request['language'] === '2'
            && str_starts_with($request['mobile'], '20'));
    }

    public function test_english_body_flagged_as_english_language(): void
    {
        $this->configureSmsMisr();
        Http::fake(['smsmisr.com/*' => Http::response(['code' => '1901', 'SMSID' => '1'], 200)]);

        SmsService::send('01012345678', 'English message');

        Http::assertSent(fn ($request) => $request['language'] === '1');
    }

    public function test_egyptian_phone_normalization(): void
    {
        Setting::set('sms_default_country_code', '20');

        $this->assertSame('201012345678', SmsService::normalizePhone('01012345678'));
        $this->assertSame('201012345678', SmsService::normalizePhone('+201012345678'));
        $this->assertSame('201012345678', SmsService::normalizePhone('0101 234 5678'));
    }
}
