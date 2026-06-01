<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Rebuilds the demo environment from scratch: fresh schema + full demo data +
 * demo accounts. So any changes prospects made during a demo are wiped clean.
 *
 * SAFETY: refuses to run unless DEMO_ENV=true, because it DROPS ALL TABLES.
 * Never enable DEMO_ENV on a real clinic install.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Skip confirmation}';

    protected $description = 'Reset the demo environment (fresh migrate + full demo data + demo users). Requires DEMO_ENV=true.';

    public function handle(): int
    {
        if (! filter_var(env('DEMO_ENV', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->error('demo:reset is disabled. Set DEMO_ENV=true in .env on the DEMO install only (this drops all tables).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This DROPS ALL TABLES and rebuilds demo data. Continue?')) {
            return self::FAILURE;
        }

        $this->info('Resetting demo environment…');

        // Force demo data on during the seed regardless of APP_ENV.
        putenv('SEED_DEMO_DATA=true');
        $_ENV['SEED_DEMO_DATA'] = 'true';
        $_SERVER['SEED_DEMO_DATA'] = 'true';

        Artisan::call('migrate:fresh', ['--force' => true], $this->output);
        Artisan::call('db:seed', ['--force' => true], $this->output);
        Artisan::call('cache:clear', [], $this->output);

        $this->info('Demo environment reset complete.');

        return self::SUCCESS;
    }
}
