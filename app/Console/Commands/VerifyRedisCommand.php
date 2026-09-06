<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

/**
 * Live connectivity check for Redis/Valkey (cache + queue).
 *
 * Prints outcomes only — never hosts, usernames, passwords, or URLs.
 * Exit 0 on success, 1 on failure. Suitable for Laravel Cloud one-off commands.
 */
class VerifyRedisCommand extends Command
{
    protected $signature = 'klassapp:verify-redis
                            {--skip-queue : Only exercise ping + cache round-trip}';

    protected $description = 'Verify Redis/Valkey: ping, cache put/get, optional queue job';

    public function handle(): int
    {
        $this->info('Redis/Valkey verification');

        $client = (string) config('database.redis.client');
        $scheme = (string) config('database.redis.default.scheme', 'tcp');
        $hasUsername = filled(config('database.redis.default.username'));
        $cacheStore = (string) config('cache.default');
        $queueConnection = (string) config('queue.default');

        $this->line('  client          : '.$client);
        $this->line('  scheme          : '.$scheme);
        $this->line('  username_set    : '.($hasUsername ? 'yes' : 'no'));
        $this->line('  cache.default   : '.$cacheStore);
        $this->line('  queue.default   : '.$queueConnection);

        try {
            $pong = Redis::connection()->ping();
            $pongOk = is_object($pong)
                ? (method_exists($pong, 'getPayload') ? $pong->getPayload() === 'PONG' : true)
                : in_array($pong, [true, 'PONG', '+PONG'], true);
            if (! $pongOk) {
                $this->error('FAIL redis ping returned unexpected payload');

                return self::FAILURE;
            }
            $this->info('OK   redis ping');
        } catch (Throwable $e) {
            $this->error('FAIL redis ping: '.$e->getMessage());

            return self::FAILURE;
        }

        $cacheKey = 'klassapp:verify-redis:'.Str::lower(Str::random(12));
        $cacheValue = 'ok-'.Str::random(8);

        try {
            Cache::put($cacheKey, $cacheValue, 60);
            $read = Cache::get($cacheKey);
            Cache::forget($cacheKey);
            // Same operation that broke Cloud migrate on 2026_08_13_030000_backfill_school104_streams.
            Cache::forget('standardLink104_51');

            if ($read !== $cacheValue) {
                $this->error('FAIL cache round-trip mismatch');

                return self::FAILURE;
            }
            $this->info('OK   cache put/get/forget (incl. standardLink104_51)');
        } catch (Throwable $e) {
            $this->error('FAIL cache round-trip: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('skip-queue')) {
            $this->info('SKIP queue ( --skip-queue )');

            return self::SUCCESS;
        }

        if ($queueConnection !== 'redis') {
            $this->error('FAIL queue.default is "'.$queueConnection.'", expected redis');

            return self::FAILURE;
        }

        $jobKey = 'klassapp:verify-redis-job:'.Str::lower(Str::random(12));
        $jobValue = 'job-'.Str::random(8);

        try {
            dispatch(function () use ($jobKey, $jobValue) {
                Cache::put($jobKey, $jobValue, 60);
            })->onConnection('redis')->onQueue('default');

            $exit = Artisan::call('queue:work', [
                'connection' => 'redis',
                '--queue' => 'default',
                '--once' => true,
                '--stop-when-empty' => true,
                '--tries' => 1,
                '--timeout' => 30,
            ]);

            if ($exit !== 0) {
                $this->error('FAIL queue:work --once exit '.$exit);

                return self::FAILURE;
            }

            $read = Cache::get($jobKey);
            Cache::forget($jobKey);

            if ($read !== $jobValue) {
                $this->error('FAIL queued job did not write expected cache key');

                return self::FAILURE;
            }
            $this->info('OK   queue dispatch + queue:work --once');
        } catch (Throwable $e) {
            $this->error('FAIL queue exercise: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('All Redis/Valkey checks passed');

        return self::SUCCESS;
    }
}
