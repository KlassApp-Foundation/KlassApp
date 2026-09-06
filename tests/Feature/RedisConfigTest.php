<?php

namespace Tests\Feature;

use Tests\TestCase;

class RedisConfigTest extends TestCase
{
    public function test_redis_config_exposes_tls_and_username_env_hooks(): void
    {
        $redis = config('database.redis');

        $this->assertSame(env('REDIS_CLIENT', 'predis'), $redis['client']);
        $this->assertArrayHasKey('default', $redis);
        $this->assertArrayHasKey('cache', $redis);

        foreach (['default', 'cache'] as $connection) {
            $this->assertArrayHasKey('scheme', $redis[$connection]);
            $this->assertArrayHasKey('username', $redis[$connection]);
            $this->assertArrayHasKey('password', $redis[$connection]);
            $this->assertArrayHasKey('url', $redis[$connection]);
            $this->assertArrayHasKey('host', $redis[$connection]);
        }
    }

    public function test_cache_default_reads_cache_store_env(): void
    {
        $this->assertSame(
            env('CACHE_STORE', env('CACHE_DRIVER', 'file')),
            config('cache.default')
        );
    }
}
