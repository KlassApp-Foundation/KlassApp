<?php

namespace Tests\Feature\Migrations;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Safety of the two pending data migrations that run on production at the
 * next deploy:
 *  - the existing-schools Toshi reset records its undo capture both in
 *    storage/logs and in the application log (Cloud log stream), and only
 *    contains school ids plus old toshi_enabled / toshi_mode values;
 *  - the student-size bucket map never throws for odd values and leaves
 *    unknown values unchanged.
 */
class DataMigrationSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(array $attrs = []): School
    {
        return School::create(array_merge([
            'name' => 'Mig '.uniqid(), 'slug' => 'mig-'.uniqid(), 'email' => uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999), 'status' => 1,
        ], $attrs));
    }

    private function toshiMigration(): object
    {
        return require database_path('migrations/2026_10_02_183000_backfill_existing_schools_toshi_safe_defaults.php');
    }

    public function test_toshi_reset_capture_reaches_the_application_log(): void
    {
        $assistant = $this->makeSchool(['name' => 'Old Assistant']);
        $assistant->forceFill(['toshi_enabled' => 1, 'toshi_mode' => 'assistant'])->save();

        $previewOn = $this->makeSchool(['name' => 'Preview With Stale Flag']);
        $previewOn->forceFill(['toshi_enabled' => 1, 'toshi_mode' => 'preview'])->save();

        $plain = $this->makeSchool(['name' => 'Plain Preview']);
        $plain->forceFill(['toshi_enabled' => 0, 'toshi_mode' => 'preview'])->save();

        $captured = null;
        Log::listen(function ($message) use (&$captured) {
            if ($message->message === '[toshi backfill] capture before reset') {
                $captured = $message->context['schools'] ?? null;
            }
        });

        $this->toshiMigration()->up();

        $this->assertNotNull($captured, 'the reset must log its capture to the application log');
        $byId = collect($captured)->keyBy('id');
        $this->assertSame(1, $byId[$assistant->id]['toshi_enabled']);
        $this->assertSame('assistant', $byId[$assistant->id]['toshi_mode']);
        $this->assertSame(1, $byId[$previewOn->id]['toshi_enabled']);
        $this->assertSame('preview', $byId[$previewOn->id]['toshi_mode']);

        // Only ids and the two flag values travel in the capture.
        $this->assertSame(['id', 'toshi_enabled', 'toshi_mode'], array_keys((array) $captured[0]));

        // The reset still flips exactly as before.
        $this->assertSame('preview', $assistant->fresh()->toshi_mode->value);
        $this->assertSame(0, (int) $assistant->fresh()->toshi_enabled);

        // And the storage file keeps the restore list for down().
        $file = storage_path('logs/toshi-backfill-restore-'.date('Ymd').'.json');
        $this->assertFileExists($file);
        $json = json_decode((string) file_get_contents($file), true);
        $this->assertContains($assistant->id, $json['restore_ids']);
        $this->assertArrayHasKey('schools', $json);
    }

    public function test_student_size_bucket_map_handles_odd_values_without_throwing(): void
    {
        $cases = [
            'Under 100 students' => 'Up to 500',
            '100-300 students' => 'Up to 500',
            '300-500 students' => 'Up to 500',
            '500+ students' => 'Up to 1,000',
            'Up to 500' => 'Up to 500',
            'Up to 1,000' => 'Up to 1,000',
            '1000+ students' => '1000+ students',
            'About two hundred' => 'About two hundred',
            ' 500+ students ' => ' 500+ students ',
        ];

        $schoolIds = [];
        foreach (array_keys($cases) as $value) {
            $school = $this->makeSchool();
            $school->forceFill(['student_size' => $value])->save();
            $schoolIds[$value] = $school->id;
        }

        $nullSchool = $this->makeSchool();
        $emptySchool = $this->makeSchool();
        $emptySchool->forceFill(['student_size' => ''])->save();

        $migration = require database_path('migrations/2026_09_30_150000_map_school_student_size_to_new_buckets.php');
        $migration->up();

        foreach ($cases as $value => $want) {
            $this->assertSame(
                $want,
                School::find($schoolIds[$value])->student_size,
                "value must map safely: '{$value}'",
            );
        }

        $this->assertNull(School::find($nullSchool->id)->student_size, 'null must stay null');
        $this->assertSame('', School::find($emptySchool->id)->student_size, 'empty must stay empty');
    }
}
