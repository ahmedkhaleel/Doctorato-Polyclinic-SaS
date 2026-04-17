<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class TelemedicineServiceProvider extends ServiceProvider
{
    /**
     * Override Reverb broadcasting config from UI-managed Settings at runtime.
     * Silently fails when DB is not ready (fresh installs, migrations pending).
     */
    public function boot(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $appKey = Setting::get('reverb_app_key');
            if (empty($appKey)) {
                return;
            }

            config([
                'broadcasting.connections.reverb.key' => $appKey,
                'broadcasting.connections.reverb.secret' => Setting::get('reverb_app_secret', ''),
                'broadcasting.connections.reverb.app_id' => Setting::get('reverb_app_id', 'telemedicine'),
                'broadcasting.connections.reverb.options.host' => Setting::get('reverb_host', 'localhost'),
                'broadcasting.connections.reverb.options.port' => (int) Setting::get('reverb_port', 8080),
                'broadcasting.connections.reverb.options.scheme' => Setting::get('reverb_scheme', 'https'),
            ]);
        } catch (\Throwable) {
            // Silent failure — broadcasting simply uses env defaults.
        }
    }
}
