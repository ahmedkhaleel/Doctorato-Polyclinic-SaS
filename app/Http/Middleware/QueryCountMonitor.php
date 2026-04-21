<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dev-only middleware that logs slow pages + probable N+1 queries.
 *
 * Enable via APP_DEBUG=true and optionally QUERY_MONITOR_THRESHOLD=50
 * (number of queries per request that counts as "too many"). Logs are
 * written at info level to laravel.log — ops just greps for
 * [query-monitor].
 *
 * Never active when APP_ENV=production, so even if someone leaves
 * APP_DEBUG on by mistake, production stays clean.
 */
class QueryCountMonitor
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hard guard: never active in production.
        if (app()->environment('production') || !config('app.debug')) {
            return $next($request);
        }

        // Skip static/asset requests — they don't hit the DB anyway.
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            $queries[] = [
                'sql'  => $q->sql,
                'time' => $q->time,
            ];
        });

        $startMem = memory_get_usage(true);
        $startTime = microtime(true);

        $response = $next($request);

        $elapsed = round((microtime(true) - $startTime) * 1000, 1);
        $mem = round((memory_get_usage(true) - $startMem) / 1024 / 1024, 2);
        $count = count($queries);
        $threshold = (int) env('QUERY_MONITOR_THRESHOLD', 50);

        if ($count >= $threshold) {
            // Aggregate duplicate SQL to spot N+1
            $buckets = [];
            foreach ($queries as $q) {
                $normalized = preg_replace('/\?/', '?', $q['sql']);
                $buckets[$normalized] = ($buckets[$normalized] ?? 0) + 1;
            }
            arsort($buckets);
            $topDups = array_slice($buckets, 0, 3, true);

            Log::info('[query-monitor] heavy page', [
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'queries'     => $count,
                'elapsed_ms'  => $elapsed,
                'memory_mb'   => $mem,
                'top_repeats' => $topDups,
            ]);
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();
        return str_starts_with($path, 'build/')
            || str_starts_with($path, 'storage/')
            || str_ends_with($path, '.css')
            || str_ends_with($path, '.js')
            || str_ends_with($path, '.map')
            || str_ends_with($path, '.ico')
            || str_starts_with($path, 'health');  // don't include /health in stats
    }
}
