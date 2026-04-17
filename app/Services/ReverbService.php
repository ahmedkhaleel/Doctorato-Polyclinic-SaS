<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class ReverbService
{
    public function isConfigured(): bool
    {
        return !empty(Setting::get('reverb_app_key'))
            && !empty(Setting::get('reverb_app_secret'))
            && !empty(Setting::get('reverb_host'));
    }

    public function getConfig(): array
    {
        return [
            'app_id' => (string) Setting::get('reverb_app_id', 'telemedicine'),
            'app_key' => (string) Setting::get('reverb_app_key', ''),
            'host' => (string) Setting::get('reverb_host', 'localhost'),
            'port' => (int) Setting::get('reverb_port', 8080),
            'scheme' => (string) Setting::get('reverb_scheme', 'https'),
        ];
    }

    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Reverb is not configured'];
        }

        try {
            $config = $this->getConfig();
            $url = $config['scheme'] . '://' . $config['host'] . ':' . $config['port'] . '/app/' . $config['app_key'];
            // Just check if the port responds
            Http::timeout(5)->get($url);
            // Reverb returns something on WebSocket upgrade attempts; any non-timeout is "reachable"
            return ['success' => true, 'message' => 'Reverb endpoint reachable at ' . $config['host'] . ':' . $config['port']];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Reverb error: ' . $e->getMessage()];
        }
    }
}
