<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearDentalCache extends Command
{
    protected $signature = 'dental:clear-cache {--all : Also clear settings and module caches}';

    protected $description = 'Clear cached dental dashboard, report, and notification data';

    public function handle(): int
    {
        $this->info('Clearing dental caches...');

        // Dashboard caches
        $month = now()->startOfMonth()->format('Y-m');
        Cache::forget("dental_dashboard_monthly:{$month}");
        Cache::forget('dental_dashboard_slow_counts');
        $this->line('  Dashboard caches cleared');

        // Report caches
        Cache::forget('dental_report_monthly_revenue');
        Cache::forget('dental_report_risk_patients');
        Cache::forget('dental_report_chart_stats');
        $this->line('  Report caches cleared');

        if ($this->option('all')) {
            // Settings cache
            Setting::clearCache();
            $this->line('  Settings cache cleared');

            // Module manager caches
            \App\Services\ModuleManager::clearCache();
            $this->line('  Module caches cleared');
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
