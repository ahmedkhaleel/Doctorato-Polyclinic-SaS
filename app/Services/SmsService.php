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
    public static function send(string $phone, string $body, ?string $senderName = null): array
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
                'smsmisr' => static::sendViaSmsMisr($phone, $body, $senderName),
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
                'message' => 'SMS send failed: '.$e->getMessage(),
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
                'To' => '+'.$phone,
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
     * Send SMS via SMS Misr (اس ام اس مصر), the Egyptian provider.
     *
     * API: https://smsmisr.com/api/SMS/  (token OR username/password auth).
     * environment: 1 = live, 2 = test. language: 1 = English, 2 = Arabic-Unicode.
     * A successful send returns JSON {"code":"1901", "SMSID": ...}.
     */
    protected static function sendViaSmsMisr(string $phone, string $body, string $senderName): array
    {
        $username = Setting::get('sms_smsmisr_username', '');
        $password = Setting::get('sms_smsmisr_password', '');
        $token = Setting::get('sms_smsmisr_token', '');
        $sender = $senderName ?: Setting::get('sms_smsmisr_sender', '');
        $environment = Setting::get('sms_smsmisr_environment', '1') === '2' ? '2' : '1';

        // Auth is either a token, or a username+password pair.
        if ((! $token && (! $username || ! $password)) || ! $sender) {
            return ['success' => false, 'message' => 'SMS Misr credentials not configured.', 'provider' => 'smsmisr'];
        }

        // Arabic content must be flagged as Unicode (language=2); otherwise English (1).
        $language = preg_match('/[\x{0600}-\x{06FF}]/u', $body) ? '2' : '1';

        $payload = array_filter([
            'environment' => $environment,
            'token' => $token ?: null,
            'username' => $token ? null : $username,
            'password' => $token ? null : $password,
            'sender' => $sender,
            'mobile' => $phone,
            'language' => $language,
            'message' => $body,
        ], fn ($v) => $v !== null);

        $response = Http::asForm()->post('https://smsmisr.com/api/SMS/', $payload);
        $data = $response->json() ?? [];
        $code = (string) ($data['code'] ?? $data['Code'] ?? '');

        // 1901 = success. Anything else is an error code from the provider.
        if ($response->successful() && $code === '1901') {
            Log::info('SMS sent via SMS Misr', ['phone' => $phone, 'smsid' => $data['SMSID'] ?? null]);

            return ['success' => true, 'message' => 'SMS sent successfully via SMS Misr.', 'provider' => 'smsmisr'];
        }

        Log::warning('SMS Misr send failed', ['phone' => $phone, 'code' => $code, 'body' => $response->body()]);

        return ['success' => false, 'message' => "SMS Misr error (code {$code}).", 'provider' => 'smsmisr'];
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

        // SSRF guard: reject URLs that point at local/internal addresses or use
        // a non-HTTP(S) scheme. Even though only an admin can set the gateway
        // URL via the settings UI, a leaked admin credential should not turn
        // into a free-form request to internal services like 127.0.0.1, the
        // metadata IP (169.254.169.254), or file:// URLs.
        if (! self::isSafeOutboundUrl($gatewayUrl)) {
            Log::warning('[sms-gateway] refused unsafe URL', ['host' => parse_url($gatewayUrl, PHP_URL_HOST)]);

            return ['success' => false, 'message' => 'SMS gateway URL is unsafe and was rejected.', 'provider' => 'gateway'];
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
            'message' => 'Gateway SMS failed with status '.$response->status(),
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

        // If it starts with 0 it's a local number — prepend the configured
        // country code (default 20 = Egypt, for the SMS Misr deployment).
        if (str_starts_with($phone, '0')) {
            $cc = preg_replace('/[^0-9]/', '', (string) Setting::get('sms_default_country_code', '20')) ?: '20';
            $phone = $cc.substr($phone, 1);
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
    public static function sendToPatient(string $phone, string $messageTemplate, array $variables = [], ?string $category = null, ?\App\Models\Patient $patient = null): array
    {
        // Honor opt-out preferences when caller passes patient + category.
        // Legacy callers that don't pass these still send (back-compat).
        if ($patient && $category && ! $patient->wantsNotification($category, 'sms')) {
            return [
                'success' => false,
                'message' => "Patient opted out of {$category} SMS.",
                'provider' => 'skipped',
            ];
        }

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
            $body = str_replace('{{'.$key.'}}', $value, $body);
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

    /**
     * Rough cost estimate for a message body, used for cost caps / analytics.
     * GSM-7 = 160 chars/segment, unicode (e.g. Arabic) = 70 chars/segment.
     * Per-segment price comes from the `sms_cost_per_segment` setting.
     */
    public static function estimateCost(string $body): float
    {
        $isUnicode = (bool) preg_match('/[^\x00-\x7F]/', $body);
        $perSegment = $isUnicode ? 70 : 160;
        $segments = max(1, (int) ceil(mb_strlen($body) / $perSegment));
        $price = (float) Setting::get('sms_cost_per_segment', '0');

        return round($segments * $price, 4);
    }

    /**
     * Validate that an outbound URL is HTTP(S) and does not target a private,
     * loopback, link-local, or cloud-metadata address. Returns false for
     * anything that looks remotely like an SSRF target.
     */
    private static function isSafeOutboundUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);

        // Reject obvious internal hostnames before DNS resolution.
        if (in_array($host, ['localhost', 'localhost.localdomain', '127.0.0.1', '::1'], true)) {
            return false;
        }

        // Resolve and reject any private/reserved address families.
        $ip = filter_var($host, FILTER_VALIDATE_IP) ?: gethostbyname($host);
        if (! $ip || $ip === $host) {
            // gethostbyname returns the hostname unchanged on failure — treat as unresolved.
            // Allow only if it's a literal IP that resolved as the same string.
            if (! filter_var($host, FILTER_VALIDATE_IP)) {
                return false;
            }
            $ip = $host;
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
