<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationChannel;
use App\Services\Notifications\Contracts\ChannelDriver;
use App\Services\Notifications\DeliveryResult;
use App\Services\Notifications\NotificationMessage;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp channel with two interchangeable transports, selected by the channel's
 * `provider` field:
 *
 *   - cloud_api : Meta WhatsApp Cloud API (graph.facebook.com). Official, requires
 *                 phone_number_id + access_token. Free-form text only works inside
 *                 the 24h customer-service window; business-initiated sends need an
 *                 approved template (pass template_name/components via meta).
 *   - bridge    : a self-hosted/unofficial HTTP bridge (e.g. whatsapp-web.js).
 *                 Configurable base_url + api_key.
 *
 * Credentials live (encrypted) in notification_channels.config.
 */
class WhatsAppChannel implements ChannelDriver
{
    private const GRAPH_VERSION = 'v21.0';

    public function key(): string
    {
        return 'whatsapp';
    }

    public function isConfigured(): bool
    {
        $channel = NotificationChannel::for('whatsapp');
        if (! $channel || ! $channel->enabled) {
            return false;
        }

        $config = $channel->config ?? [];

        return $channel->provider === 'bridge'
            ? ! empty($config['base_url'])
            : ! empty($config['phone_number_id']) && ! empty($config['access_token']);
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        if (! $message->to) {
            return DeliveryResult::fail('No WhatsApp number.', 'whatsapp');
        }

        $channel = NotificationChannel::for('whatsapp');
        $config = $channel?->config ?? [];

        return ($channel?->provider === 'bridge')
            ? $this->sendViaBridge($message, $config)
            : $this->sendViaCloudApi($message, $config);
    }

    private function sendViaCloudApi(NotificationMessage $message, array $config): DeliveryResult
    {
        $phoneNumberId = $config['phone_number_id'] ?? null;
        $token = $config['access_token'] ?? null;

        if (! $phoneNumberId || ! $token) {
            return DeliveryResult::fail('WhatsApp Cloud API not configured.', 'cloud_api');
        }

        $to = ltrim($message->to, '+');
        $payload = $this->cloudPayload($to, $message);

        try {
            $response = Http::withToken($token)
                ->post('https://graph.facebook.com/'.self::GRAPH_VERSION."/{$phoneNumberId}/messages", $payload);

            $data = $response->json() ?? [];
            $wamid = $data['messages'][0]['id'] ?? null;

            if ($response->successful() && $wamid) {
                return DeliveryResult::ok('cloud_api', 0.0, $wamid);
            }

            $error = $data['error']['message'] ?? 'WhatsApp Cloud API send failed.';
            Log::warning('WhatsApp Cloud API failed', ['to' => $to, 'error' => $error]);

            return DeliveryResult::fail($error, 'cloud_api');
        } catch (\Throwable $e) {
            return DeliveryResult::fail('WhatsApp Cloud API error: '.$e->getMessage(), 'cloud_api');
        }
    }

    /** Build either a template message (if meta.template_name set) or plain text. */
    private function cloudPayload(string $to, NotificationMessage $message): array
    {
        $templateName = $message->meta['template_name'] ?? null;

        if ($templateName) {
            return [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $message->meta['template_lang'] ?? 'ar'],
                    'components' => $message->meta['template_components'] ?? [],
                ],
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message->body],
        ];
    }

    private function sendViaBridge(NotificationMessage $message, array $config): DeliveryResult
    {
        $baseUrl = rtrim($config['base_url'] ?? '', '/');
        if (! $baseUrl) {
            return DeliveryResult::fail('WhatsApp bridge not configured.', 'bridge');
        }

        $endpoint = $baseUrl.'/'.ltrim($config['send_path'] ?? 'send', '/');
        if (! SafeUrl::isSafe($endpoint)) {
            Log::warning('[whatsapp-bridge] refused unsafe URL', ['host' => parse_url($endpoint, PHP_URL_HOST)]);

            return DeliveryResult::fail('WhatsApp bridge URL is unsafe and was rejected.', 'bridge');
        }

        try {
            $request = Http::asJson();
            if (! empty($config['api_key'])) {
                $request = $request->withHeaders(['Authorization' => 'Bearer '.$config['api_key']]);
            }

            $response = $request->post($endpoint, [
                'to' => ltrim($message->to, '+'),
                'message' => $message->body,
                'session' => $config['session'] ?? null,
            ]);

            $data = $response->json() ?? [];

            if ($response->successful() && ($data['success'] ?? $response->successful())) {
                return DeliveryResult::ok('bridge', 0.0, $data['id'] ?? $data['messageId'] ?? null);
            }

            return DeliveryResult::fail($data['error'] ?? 'WhatsApp bridge send failed.', 'bridge');
        } catch (\Throwable $e) {
            return DeliveryResult::fail('WhatsApp bridge error: '.$e->getMessage(), 'bridge');
        }
    }
}
