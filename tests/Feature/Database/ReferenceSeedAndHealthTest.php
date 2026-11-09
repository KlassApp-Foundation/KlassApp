<?php

namespace Tests\Feature\Database;

use App\Reference\CountryCodes;
use App\Reference\Geography;
use App\Reference\ReferenceSnapshot;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ReferenceSeedAndHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_seed_is_idempotent_and_links_every_city_to_a_state(): void
    {
        $this->seed(ReferenceSeeder::class);

        $uganda = DB::table('countries')->where('name', 'Uganda')->first();
        $this->assertNotNull($uganda);
        $this->assertSame(['UG', 'UGA', '256'], CountryCodes::triple($uganda));
        $this->assertSame(count(CountryCodes::corrected()), DB::table('countries')->count());
        $this->assertSame(Geography::expectedStateCount(), DB::table('states')->count());
        $this->assertSame(Geography::expectedCityCount(), DB::table('cities')->count());
        $this->assertSame(0, DB::table('schools')->count());
        $this->assertSame(4, DB::table('absent_reasons')->count());

        $orphanStates = DB::table('states')
            ->leftJoin('countries', 'countries.id', '=', 'states.country_id')
            ->whereNull('countries.id')
            ->count();
        $orphanCities = DB::table('cities')
            ->leftJoin('states', 'states.id', '=', 'cities.state_id')
            ->whereNull('states.id')
            ->count();
        $this->assertSame(0, $orphanStates);
        $this->assertSame(0, $orphanCities);

        $updatedAt = DB::table('countries')->where('name', 'Uganda')->value('updated_at');
        $before = ReferenceSnapshot::capture();
        $this->assertSame([], $before['problems']);

        $this->seed(ReferenceSeeder::class);

        $after = ReferenceSnapshot::capture();
        $this->assertSame($before['checksum'], $after['checksum']);
        $this->assertSame($before['counts'], $after['counts']);
        $this->assertSame($updatedAt, DB::table('countries')->where('name', 'Uganda')->value('updated_at'));
        $this->assertSame(Geography::expectedCityCount(), DB::table('cities')->count());
    }

    public function test_database_seeder_does_not_create_demo_schools(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, DB::table('schools')->count());
        $this->assertSame('UG', DB::table('countries')->where('name', 'Uganda')->value('short_name'));
    }

    public function test_demo_seeder_creates_marked_demo_schools_and_can_run_twice(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(5, DB::table('schools')->count());
        $this->assertSame(5, (int) DB::table('schools')->where('is_demo', 1)->where('toshi_enabled', 0)->where('toshi_mode', 'preview')->count());
        $this->assertSame(Geography::expectedCityCount(), DB::table('cities')->count());
        $this->assertSame(0, DB::table('cities')->whereNull('state_id')->count());
        $this->assertGreaterThan(0, DB::table('exams')->count());

        $schools = DB::table('schools')->count();
        $exams = DB::table('exams')->count();
        $this->seed(DemoSeeder::class);
        $this->assertSame($schools, DB::table('schools')->count());
        $this->assertSame($exams, DB::table('exams')->count());
    }

    public function test_demo_seeder_refuses_production_and_writes_nothing(): void
    {
        $this->seed(ReferenceSeeder::class);
        $before = ReferenceSnapshot::capture();
        $this->app['env'] = 'production';
        // Call run() directly. Artisan's seed command asks for confirmation
        // in production before the seeder starts; --force skips that prompt
        // and then this refusal still runs.
        $seeder = $this->app->make(DemoSeeder::class);

        try {
            $seeder->run();
            $this->fail('DemoSeeder must refuse APP_ENV=production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('production', $exception->getMessage());
        }

        $this->assertSame(0, DB::table('schools')->count());
        $this->assertSame($before['checksum'], ReferenceSnapshot::capture()['checksum']);
    }

    public function test_db_health_performs_no_writes(): void
    {
        $this->seed(ReferenceSeeder::class);
        $before = ReferenceSnapshot::capture();
        $writes = [];
        DB::listen(function ($query) use (&$writes): void {
            if (preg_match('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });

        $this->artisan('db:health', ['--fail' => true])->assertExitCode(0);

        $this->assertSame([], $writes);
        $this->assertSame($before['checksum'], ReferenceSnapshot::capture()['checksum']);
        $this->assertSame($before['counts'], ReferenceSnapshot::capture()['counts']);
    }

    public function test_db_health_reports_problems_and_can_exit_nonzero(): void
    {
        $this->seed(ReferenceSeeder::class);
        Schema::disableForeignKeyConstraints();

        DB::table('countries')->where('name', 'Uganda')->update([
            'short_name' => 'AF',
            'iso_code' => 'AFG',
            'tel_prefix' => '256',
        ]);
        DB::table('states')->insert([
            'country_id' => 999999,
            'name' => 'Nowhere',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'usergroup_id' => 6,
            'school_id' => null,
            'name' => 'Student Without Id',
            'email' => 'dup@school.test',
            'mobile_no' => '+256700111222',
            'password' => 'secret',
            'registration_number' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'usergroup_id' => 6,
            'school_id' => null,
            'name' => 'Student Duplicate',
            'email' => 'DUP@school.test',
            'mobile_no' => '+256700111222',
            'password' => 'secret',
            'registration_number' => '',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::enableForeignKeyConstraints();

        // One token per printed line. The test output mock matches
        // each write against a single expectation.
        $this->artisan('db:health')
            ->expectsOutputToContain('AF/AFG/256')
            ->expectsOutputToContain('states.country_id')
            ->expectsOutputToContain('students have no registration_number')
            ->expectsOutputToContain('non-site-admin rows have no school_id')
            ->expectsOutputToContain('dup@school.test')
            ->expectsOutputToContain('+256700111222')
            ->assertExitCode(0);

        $this->artisan('db:health', ['--fail' => true])->assertExitCode(1);
    }
}
