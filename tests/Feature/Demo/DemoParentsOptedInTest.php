<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Task 3: the staging demo seeders flag about 60 percent of demo parents as
 * opted in to WhatsApp (a flag only, fictional +1 555 numbers, nothing sent),
 * and demo:refresh keeps that flag. Foreign keys on, non-UTC timezone
 * (AGENTS.md rules 35 and 42).
 */
class DemoParentsOptedInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');
        \App\Services\DemoSchoolCommsGuard::flushCache();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_T3_junior_demo_seeder_flags_about_60_percent_of_parents_opted_in(): void
    {
        $this->seed(DemoJuniorSchoolSeeder::class);
        $school = School::where('email', 'demo-junior@klassapp.xyz')->firstOrFail();

        $this->assertAboutSixtyPercentOptedIn($school);
    }

    public function test_T3_senior_demo_seeder_flags_about_60_percent_of_parents_opted_in(): void
    {
        $this->seed(DemoSeniorSchoolSeeder::class);
        $school = School::where('email', 'demo-senior@klassapp.xyz')->firstOrFail();

        $this->assertAboutSixtyPercentOptedIn($school);
    }

    public function test_T3_demo_refresh_keeps_the_opted_in_flag(): void
    {
        $this->seed(DemoJuniorSchoolSeeder::class);
        $school = School::where('email', 'demo-junior@klassapp.xyz')->firstOrFail();
        $before = $this->optedInParentIds($school);
        $this->assertNotEmpty($before);

        // A demo visitor's session flips flags; refresh puts the baseline back.
        WhatsAppUser::whereIn('user_id', $before)->limit(2)->get()->each->update(['opted_in' => false]);

        $this->artisan('demo:refresh', ['school' => $school->id])->assertExitCode(0);

        $this->assertSame($before, $this->optedInParentIds($school));
        $this->assertAboutSixtyPercentOptedIn($school);
    }

    private function assertAboutSixtyPercentOptedIn(School $school): void
    {
        $parents = User::where('school_id', $school->id)->where('usergroup_id', 7)->pluck('id');
        $this->assertGreaterThan(0, $parents->count());

        $rows = WhatsAppUser::where('school_id', $school->id)->whereIn('user_id', $parents)->get();
        $this->assertCount($parents->count(), $rows, 'every demo parent has a flag row');
        $this->assertTrue($rows->every(fn ($row) => str_starts_with($row->phone, '+1555')), 'fictional numbers only');

        $opted = $rows->where('opted_in', true)->count();
        $this->assertEqualsWithDelta(0.6, $opted / $parents->count(), 0.1);
    }

    /** @return list<int> */
    private function optedInParentIds(School $school): array
    {
        return WhatsAppUser::where('school_id', $school->id)
            ->where('opted_in', true)
            ->whereIn('user_id', User::where('school_id', $school->id)->where('usergroup_id', 7)->select('id'))
            ->orderBy('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
