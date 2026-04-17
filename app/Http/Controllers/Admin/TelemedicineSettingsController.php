<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AgoraService;
use App\Services\AuditLogger;
use App\Services\ModuleManager;
use App\Services\Payment\Drivers\PaymobGateway;
use App\Services\Payment\Drivers\StripeGateway;
use App\Services\ReverbService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TelemedicineSettingsController extends Controller
{
    /**
     * Map setting keys to their logical group for validation/organization.
     */
    private const ALL_KEYS = [
        // Feature flag & windows
        'telemedicine_enabled' => 'feature',
        'telemedicine_join_window_minutes' => 'feature',
        'telemedicine_wait_timeout_minutes' => 'feature',
        'telemedicine_cancellation_hours' => 'feature',
        'telemedicine_refund_percentage' => 'feature',
        'telemedicine_extra_fee_percentage' => 'feature',

        // Agora
        'agora_app_id' => 'agora',
        'agora_app_certificate' => 'agora',
        'agora_token_expiry_seconds' => 'agora',

        // Payment gateway selection
        'payment_active_gateway' => 'payment',

        // Paymob
        'paymob_enabled' => 'payment',
        'paymob_test_mode' => 'payment',
        'paymob_api_key' => 'payment',
        'paymob_hmac_secret' => 'payment',
        'paymob_iframe_id' => 'payment',
        'paymob_integration_id' => 'payment',

        // Stripe
        'stripe_enabled' => 'payment',
        'stripe_test_mode' => 'payment',
        'stripe_secret_key' => 'payment',
        'stripe_publishable_key' => 'payment',
        'stripe_webhook_secret' => 'payment',

        // Reverb
        'reverb_app_id' => 'reverb',
        'reverb_app_key' => 'reverb',
        'reverb_app_secret' => 'reverb',
        'reverb_host' => 'reverb',
        'reverb_port' => 'reverb',
        'reverb_scheme' => 'reverb',
    ];

    private const BOOLEAN_KEYS = [
        'telemedicine_enabled',
        'paymob_enabled',
        'paymob_test_mode',
        'stripe_enabled',
        'stripe_test_mode',
    ];

    public function show()
    {
        $settings = [];
        foreach (array_keys(self::ALL_KEYS) as $key) {
            if (Setting::isEncryptedKey($key)) {
                // Never send the actual value - just whether it's set
                $settings[$key] = [
                    'is_set' => Setting::hasValue($key),
                    'masked' => Setting::maskedValue($key),
                ];
            } else {
                $settings[$key] = Setting::get($key, $this->getDefault($key));
            }
        }

        // Status checks
        $agora = app(AgoraService::class);
        $paymob = app(PaymobGateway::class);
        $stripe = app(StripeGateway::class);
        $reverb = app(ReverbService::class);

        return Inertia::render('Admin/Settings/Telemedicine', [
            'settings' => $settings,
            'status' => [
                'module_enabled' => ModuleManager::isEnabled('telemedicine'),
                'agora_configured' => $agora->isConfigured(),
                'paymob_configured' => $paymob->isEnabled(),
                'stripe_configured' => $stripe->isEnabled(),
                'reverb_configured' => $reverb->isConfigured(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string',
            'value' => 'nullable',
        ]);

        $key = $data['key'];

        if (!isset(self::ALL_KEYS[$key])) {
            abort(422, 'Invalid setting key');
        }

        $value = $data['value'];

        // Don't overwrite encrypted keys with the masked placeholder
        if (Setting::isEncryptedKey($key) && is_string($value) && str_contains($value, '•')) {
            return back()->with('info', 'Setting unchanged (masked placeholder ignored).');
        }

        // Normalize booleans
        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        Setting::set($key, $value);
        Setting::clearCache();

        // Audit log (without sensitive value)
        try {
            AuditLogger::log(
                action: 'telemedicine_setting_updated',
                description: 'Updated setting: ' . $key . ($this->isBooleanKey($key) ? ' = ' . $value : ''),
            );
        } catch (\Throwable) {
            // non-fatal
        }

        return back()->with('success', 'Setting saved.');
    }

    public function testAgora()
    {
        return response()->json(app(AgoraService::class)->testConnection());
    }

    public function testPaymob()
    {
        return response()->json(app(PaymobGateway::class)->testConnection());
    }

    public function testStripe()
    {
        return response()->json(app(StripeGateway::class)->testConnection());
    }

    public function testReverb()
    {
        return response()->json(app(ReverbService::class)->testConnection());
    }

    public function toggleModule(Request $request)
    {
        $enable = filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN);

        if ($enable) {
            $agoraOk = app(AgoraService::class)->isConfigured();
            $payOk = app(PaymobGateway::class)->isEnabled() || app(StripeGateway::class)->isEnabled();
            if (!$agoraOk) {
                return back()->with('error', 'Agora must be configured before enabling the module.');
            }
            if (!$payOk) {
                return back()->with('error', 'At least one payment gateway must be configured.');
            }
            ModuleManager::enable('telemedicine');
            Setting::set('telemedicine_enabled', '1');
        } else {
            ModuleManager::disable('telemedicine');
            Setting::set('telemedicine_enabled', '0');
        }
        Setting::clearCache();

        try {
            AuditLogger::log(
                action: 'telemedicine_module_toggled',
                description: $enable ? 'Telemedicine module enabled' : 'Telemedicine module disabled',
            );
        } catch (\Throwable) {
            // non-fatal
        }

        return back()->with('success', $enable ? 'Telemedicine enabled.' : 'Telemedicine disabled.');
    }

    private function isBooleanKey(string $key): bool
    {
        return in_array($key, self::BOOLEAN_KEYS, true);
    }

    private function getDefault(string $key): mixed
    {
        return [
            'telemedicine_enabled' => '0',
            'telemedicine_join_window_minutes' => '15',
            'telemedicine_wait_timeout_minutes' => '10',
            'telemedicine_cancellation_hours' => '24',
            'telemedicine_refund_percentage' => '100',
            'telemedicine_extra_fee_percentage' => '0',
            'agora_token_expiry_seconds' => '7200',
            'payment_active_gateway' => 'paymob',
            'paymob_enabled' => '0',
            'paymob_test_mode' => '1',
            'stripe_enabled' => '0',
            'stripe_test_mode' => '1',
            'reverb_app_id' => 'telemedicine',
            'reverb_scheme' => 'https',
            'reverb_port' => '8080',
            'reverb_host' => '',
        ][$key] ?? '';
    }
}
