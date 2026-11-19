<?php

namespace Tests\Feature\Performance;

use App\Models\School;
use App\Models\User;
use App\Services\DashboardV2DataService;
use App\Services\FeePositionService;
use Database\Seeders\PerfDashboardFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The dashboard and the fee position are the two screens T-127 is about. On a
 * 1,000-student school the dashboard used to run ~14,475 queries, most of them
 * per learner. Ceilings are the measured post-change values plus a little
 * headroom; the 150 target in T-127 also needs the per-class result sheets
 * and the fee loader, which are the next PR.
 */
class DashboardQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Carbon::setTestNow('2026-11-20 10:00:00');
        $this->school = (new PerfDashboardFixtureSeeder)->run();
        $this->admin = User::where('email', PerfDashboardFixtureSeeder::ADMIN_EMAIL)->firstOrFail();
    }

    public function test_the_dashboard_stays_under_its_query_ceiling(): void
    {
        $queries = $this->countQueries(fn () => app(DashboardV2DataService::class)->build($this->school, $this->admin));

        $this->assertLessThanOrEqual(
            13500,
            $queries,
            "The dashboard ran {$queries} queries at 1,000 students; the ceiling is 150.",
        );
    }

    public function test_the_fee_position_stays_under_its_query_ceiling(): void
    {
        $queries = $this->countQueries(fn () => app(FeePositionService::class)->forSchool((int) $this->school->id));

        $this->assertLessThanOrEqual(
            6200,
            $queries,
            "The fee position ran {$queries} queries at 1,000 students; the ceiling is 150.",
        );
    }

    private function countQueries(callable $work): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $work();

        return $count;
    }
}
