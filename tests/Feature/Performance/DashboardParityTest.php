<?php

namespace Tests\Feature\Performance;

use App\Models\School;
use App\Models\User;
use App\Services\DashboardV2DataService;
use App\Services\FeePositionService;
use Database\Seeders\PerfDashboardFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Characterisation test for T-127: the dashboard and the fee position must print
 * exactly the numbers they print today, at 1,000 students, after the query work.
 * The snapshot was taken from the pre-change implementation.
 */
class DashboardParityTest extends TestCase
{
    use RefreshDatabase;

    private const SNAPSHOT = 'tests/fixtures/perf/dashboard-snapshot.json';

    public function test_the_dashboard_and_fee_position_output_is_unchanged(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-11-20 10:00:00');
        $school = (new PerfDashboardFixtureSeeder)->run();
        $admin = User::where('email', PerfDashboardFixtureSeeder::ADMIN_EMAIL)->firstOrFail();

        $actual = [
            'dashboard' => app(DashboardV2DataService::class)->build($school, $admin),
            'fee_position' => app(FeePositionService::class)->forSchool((int) $school->id),
        ];

        $path = base_path(self::SNAPSHOT);
        $json = json_encode($this->normalise($actual), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (getenv('PERF_SNAPSHOT_WRITE') === '1') {
            @mkdir(dirname($path), 0755, true);
            file_put_contents($path, $json."\n");
            $this->assertFileExists($path);

            return;
        }

        \Illuminate\Support\Carbon::setTestNow();
        $this->assertFileExists($path, 'The dashboard snapshot is missing.');
        $expected = json_decode((string) file_get_contents($path), true);

        $this->assertSame($expected, json_decode($json, true), 'The dashboard output changed.');
    }

    /** Dates and floats are compared in a stable form. */
    private function normalise(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = $this->normalise($item);
            }

            return $out;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_float($value)) {
            return round($value, 4);
        }

        return $value;
    }
}
