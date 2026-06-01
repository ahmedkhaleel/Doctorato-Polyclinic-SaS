<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\Ai\AiCostMeter;
use App\Services\Ai\AiGate;
use App\Services\Ai\AiManager;
use App\Services\Ai\Contracts\AiDriver;
use App\Services\Ai\Drivers\NullDriver;
use App\Services\Ai\Drivers\OpenAiDriver;
use App\Services\Ai\PhiRedactor;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Resolve the active driver from the configured provider. Falls back to
        // NullDriver when AI is disabled or the driver has no API key, so nothing
        // ever 500s when AI is off.
        $this->app->bind(AiDriver::class, function () {
            try {
                if (! Setting::get('ai_enabled', false)) {
                    return new NullDriver;
                }
                $provider = Setting::get('ai_provider', config('ai.provider', 'openai'));
                $driver = match ($provider) {
                    'openai' => new OpenAiDriver,
                    default => new NullDriver,
                };

                return $driver->isReady() ? $driver : new NullDriver;
            } catch (\Throwable) {
                return new NullDriver;
            }
        });

        $this->app->singleton(AiCostMeter::class);
        $this->app->singleton(PhiRedactor::class);
        $this->app->singleton(AiGate::class, fn ($app) => new AiGate($app->make(AiCostMeter::class)));

        $this->app->singleton(AiManager::class, fn ($app) => new AiManager(
            $app->make(AiDriver::class),
            $app->make(AiGate::class),
            $app->make(AiCostMeter::class),
            $app->make(PhiRedactor::class),
        ));
    }
}
