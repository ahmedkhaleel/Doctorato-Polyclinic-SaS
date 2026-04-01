<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ActivateRedis extends Command
{
    protected $signature = 'system:check-redis';

    protected $description = 'Check Redis connection and show activation instructions';

    public function handle(): int
    {
        $this->info('Checking Redis connection...');

        try {
            $redis = Redis::connection();
            $redis->ping();

            $this->info('✅ Redis is connected and responding.');
            $this->newLine();

            // Show current config
            $this->table(['Setting', 'Value'], [
                ['CACHE_STORE', config('cache.default')],
                ['QUEUE_CONNECTION', config('queue.default')],
                ['SESSION_DRIVER', config('session.driver')],
                ['Redis Host', config('database.redis.default.host')],
                ['Redis Port', config('database.redis.default.port')],
            ]);

            if (config('cache.default') !== 'redis') {
                $this->newLine();
                $this->warn('To activate Redis for cache, queue, and sessions, update your .env:');
                $this->line('  CACHE_STORE=redis');
                $this->line('  QUEUE_CONNECTION=redis');
                $this->line('  SESSION_DRIVER=redis');
                $this->newLine();
                $this->line('Then run: php artisan config:cache');
            } else {
                $this->info('Redis is fully activated for caching.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Redis connection failed: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Make sure Redis server is installed and running:');
            $this->line('  brew install redis && brew services start redis    (macOS)');
            $this->line('  sudo apt install redis-server && sudo systemctl start redis  (Ubuntu)');

            return self::FAILURE;
        }
    }
}
