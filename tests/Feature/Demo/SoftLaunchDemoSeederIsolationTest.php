<?php

namespace Tests\Feature\Demo;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Soft-launch demo seeders must be run on purpose only: never from
 * DatabaseSeeder, migrations, or console/deploy wiring.
 */
class SoftLaunchDemoSeederIsolationTest extends TestCase
{
    private const SEEDERS = [
        'DemoJuniorSchoolSeeder',
        'DemoSeniorSchoolSeeder',
    ];

    private function codeOnly(string $path): string
    {
        $stripped = php_strip_whitespace($path);
        $this->assertNotFalse($stripped, 'could not parse '.$path);

        return (string) $stripped;
    }

    public function test_no_other_seeder_references_soft_launch_demo_seeders(): void
    {
        foreach (File::files(database_path('seeders')) as $file) {
            $code = $this->codeOnly($file->getPathname());
            foreach (self::SEEDERS as $needle) {
                if ($file->getFilename() === $needle.'.php') {
                    continue;
                }
                $this->assertStringNotContainsString(
                    $needle,
                    $code,
                    $file->getFilename().' must not reference '.$needle
                );
            }
        }
    }

    public function test_no_migration_references_soft_launch_demo_seeders(): void
    {
        foreach (File::allFiles(database_path('migrations')) as $file) {
            $code = $this->codeOnly($file->getPathname());
            foreach (self::SEEDERS as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $code,
                    $file->getFilename().' must never seed '.$needle
                );
            }
        }
    }

    public function test_console_and_deploy_wiring_never_call_soft_launch_demo_seeders(): void
    {
        $paths = array_filter([
            app_path('Console/Kernel.php'),
            base_path('routes/console.php'),
        ], 'file_exists');

        foreach ($paths as $path) {
            $code = $this->codeOnly($path);
            foreach (self::SEEDERS as $needle) {
                $this->assertStringNotContainsString($needle, $code, $path);
            }
        }
    }
}
