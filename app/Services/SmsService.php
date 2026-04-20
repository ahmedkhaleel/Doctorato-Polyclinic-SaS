<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS message to a phone number.
     *
     * @return array{success: bool, message: string, provider: string}
     */
    public static function send(string $phone, string $body, string $senderName = null): array
    {
        $provider = Setting::get('sms_provider', 'none');
        $senderName = $senderName ?: Setting::get('sms_sender_name', 'Doctorato');

        if ($provider === 'none' || ! $provider) {
            Log::info('SMS not sent (no provider configured)', [
                'phone' => $phone,
                'body_length' => strlen($body),
            ]);

            return [
                'success' => false,
                'message' => 'No SMS provider configured.',
                'provider' => 'none',
            ];
        }

        // Normalize phone number (ensure starts with country code)
        $phone = static::normalizePhone($phone);

        if (! $phone) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
                'provider' => $provider,
            ];
        }

        try {
            return match ($provider) {
                'unifonic' => static::sendViaUnifonic($phone, $body, $senderName),
                'twilio' => static::sendViaTwilio($phone, $body, $senderName),
                'gateway' => static::sendViaGateway($phone, $body, $senderName),
                default => [
                    'success' => false,
                    'message' => "Unknown SMS provider: {$provider}",
                    'provider' => $provider,
                ],
            };
        } catch (\Exception $e) {
            Log::error("SMS send failed via {$provider}", [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS send failed: ' . $e->getMessage(),
                'provider' => $provider,
            ];
        }
    }

    /**
     * Send SMS via Unifonic (popular in Middle East / Saudi Arabia).
     */
    protected static function sendViaUnifonic(string $phone, string $body, string $senderName): array
    {
        $appSid = Setting::get('sms_unifonic_app_sid', '');

        if (! $appSid) {
            return ['success' => false, 'message' => 'Unifonic App SID not configured.', 'provider' => 'unifonic'];
        }

        $response = Http::asForm()->post('https://el.cloud.unifonic.com/rest/SMS/messages', [
            'AppSid' => $appSid,
            'SenderID' => $senderName,
            'Body' => $body,
            'Recipient' => $phone,
            'responseType' => 'JSON',
            'CorrelationID' => uniqid('aura_'),
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['success'] ?? false)) {
            Log::info('SMS sent via Unifonic', ['phone' => $phone, 'message_id' => $data['data']['MessageID'] ?? null]);

            return [
                'success' => true,
                'message' => 'SMS sent successfully via Unifonic.',
                'provider' => 'unifonic',
            ];
        }

        $errorMsg = $data['message'] ?? $data['errorCode'] ?? 'Unknown Unifonic error';
        Log::warning('Unifonic SMS failed', ['phone' => $phone, 'error' => $errorMsg, 'response' => $data]);

        return [
            'success' => false,
            'message' => "Unifonic error: {$errorMsg}",
            'provider' => 'unifonic',
        ];
    }

    /**
     * Send SMS via Twilio.
     */
    protected static function sendViaTwilio(string $phone, string $body, string $senderName): array
    {
        $accountSid = Setting::get('sms_twilio_account_sid', '');
        $authToken = Setting::get('sms_twilio_auth_token', '');
        $fromNumber = Setting::get('sms_twilio_from_number', '');

        if (! $accountSid || ! $authToken || ! $fromNumber) {
            return ['success' => false, 'message' => 'Twilio credentials not configured.', 'provider' => 'twilio'];
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'To' => '+' . $phone,
                'From' => $fromNumber,
                'Body' => $body,
            ]);

        $data = $response->json();

        if ($response->successful() && isset($data['sid'])) {
            Log::info('SMS sent via Twilio', ['phone' => $phone, 'sid' => $data['sid']]);

            return [
                'success' => true,
                'message' => 'SMS sent successfully via Twilio.',
                'provider' => 'twilio',
            ];
        }

        $errorMsg = $data['message'] ?? 'Unknown Twilio error';
        Log::warning('Twilio SMS failed', ['phone' => $phone, 'error' => $errorMsg]);

        return [
            'success' => false,
            'message' => "Twilio error: {$errorMsg}",
            'provider' => 'twilio',
        ];
    }

    /**
     * Send SMS via a generic HTTP gateway.
     * Configurable URL with placeholder substitution.
     */
    protected static function sendViaGateway(string $phone, string $body, string $senderName): array
    {
        $gatewayUrl = Setting::get('sms_gateway_url', '');
        $gatewayMethod = strtoupper(Setting::get('sms_gateway_method', 'GET'));
        $gatewayApiKey = Setting::get('sms_gateway_api_key', '');

        if (! $gatewayUrl) {
            return ['success' => false, 'message' => 'SMS Gateway URL not configured.', 'provider' => 'gateway'];
        }

        // Replace placeholders in URL
        $url = str_replace(
            ['{phone}', '{message}', '{sender}', '{api_key}'],
            [urlencode($phone), urlencode($body), urlencode($senderName), urlencode($gatewayApiKey)],
            $gatewayUrl
        );

        $response = $gatewayMethod === 'POST'
            ? Http::post($url, [
                'phone' => $phone,
                'message' => $body,
                'sender' => $senderName,
                'api_key' => $gatewayApiKey,
            ])
            : Http::get($url);

        if ($response->successful()) {
            Log::info('SMS sent via Gateway', ['phone' => $phone, 'status' => $response->status()]);

            return [
                'success' => true,
                'message' => 'SMS sent successfully via Gateway.',
                'provider' => 'gateway',
            ];
        }

        Log::warning('Gateway SMS failed', ['phone' => $phone, 'status' => $response->status(), 'body' => $response->body()]);

        return [
            'success' => false,
            'message' => 'Gateway SMS failed with status ' . $response->status(),
            'provider' => 'gateway',
        ];
    }

    /**
     * Normalize a phone number to international format (digits only, with country code).
     */
    public static function normalizePhone(string $phone): ?string
    {
        // Strip everything non-digit except leading +
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));

        // Remove leading +
        $phone = ltrim($phone, '+');

        // If starts with 0, assume Iraqi number → add 964
        if (str_starts_with($phone, '0')) {
            $phone = '964' . substr($phone, 1);
        }

        // Must have at least 10 digits
        if (strlen($phone) < 10) {
            return null;
        }

        return $phone;
    }

    /**
     * Send a quick SMS to a patient (convenience method).
     * Uses patient name for variable replacement.
     */
    public static function sendToPatient(string $phone, string $messageTemplate, array $variables = []): array
    {
        $clinicName = Setting::get('clinic_name_en', 'Doctorato Polyclinic');
        $clinicPhone = Setting::get('clinic_phone', '');

        $defaults = [
            'clinic_name' => $clinicName,
            'clinic_phone' => $clinicPhone,
            'date' => now()->format('d/m/Y'),
        ];

        $variables = array_merge($defaults, $variables);

        // Replace {{variable}} placeholders
        $body = $messageTemplate;
        foreach ($variables as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        return static::send($phone, $body);
    }

    /**
     * Check if SMS is enabled and configured.
     */
    public static function isEnabled(): bool
    {
        $provider = Setting::get('sms_provider', 'none');
        $enabled = Setting::get('sms_enabled', '0');

        return $enabled === '1' && $provider !== 'none';
    }

    /**
     * Test SMS configuration by sending a test message.
     */
    public static function sendTest(string $phone): array
    {
        return static::send($phone, 'This is a test message from Doctorato Polyclinic SMS system. If you received this, your SMS configuration is working correctly.');
    }
}
