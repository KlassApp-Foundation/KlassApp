<?php

namespace Tests\Feature\Admin;

use App\Helpers\DashboardCache;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardRosterCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ttl_defaults_when_cache_time_missing_or_non_positive(): void
    {
        putenv('CACHE_TIME');
        unset($_ENV['CACHE_TIME'], $_SERVER['CACHE_TIME']);
        $this->assertSame(DashboardCache::DEFAULT_TTL_SECONDS, DashboardCache::ttl());

        putenv('CACHE_TIME=0');
        $_ENV['CACHE_TIME'] = '0';
        $_SERVER['CACHE_TIME'] = '0';
        $this->assertSame(DashboardCache::DEFAULT_TTL_SECONDS, DashboardCache::ttl());

        putenv('CACHE_TIME=120');
        $_ENV['CACHE_TIME'] = '120';
        $_SERVER['CACHE_TIME'] = '120';
        $this->assertSame(120, DashboardCache::ttl());

        putenv('CACHE_TIME');
        unset($_ENV['CACHE_TIME'], $_SERVER['CACHE_TIME']);
    }

    public function test_user_observer_forgets_roster_count_keys_on_create(): void
    {
        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Cache School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
        ]);

        $keys = DashboardCache::rosterCountKeys((int) $school->id);
        foreach ($keys as $key) {
            Cache::forever($key, 999);
            $this->assertSame(999, Cache::get($key));
        }

        User::create([
            'name' => 'New Student',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('secret'),
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'status' => 'active',
        ]);

        foreach ($keys as $key) {
            $this->assertNull(Cache::get($key), "Expected {$key} forgotten after User create");
        }
    }

    public function test_forget_roster_counts_is_noop_for_invalid_school(): void
    {
        DashboardCache::forgetRosterCounts(null);
        DashboardCache::forgetRosterCounts(0);
        $this->assertTrue(true);
    }
}
