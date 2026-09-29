<?php

namespace Tests\Feature\Demo;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Demo Academy must be seeded on purpose only: never from DatabaseSeeder,
 * never from a migration, never from console/deploy wiring.
 */
class DemoAcademySeederIsolationTest extends TestCase
{
    private const NEEDLE = 'DemoAcademySeeder';

    /** Code without comments, so documentation may name the seeder freely. */
    private function codeOnly(string $path): string
    {
        $stripped = php_strip_whitespace($path);
        $this->assertNotFalse($stripped, 'could not parse ' . $path);

        return (string) $stripped;
    }

    public function test_no_other_seeder_references_the_demo_academy_seeder(): void
    {
        foreach (File::files(database_path('seeders')) as $file) {
            if ($file->getFilename() === 'DemoAcademySeeder.php') {
                continue;
            }

            $this->assertStringNotContainsString(
                self::NEEDLE,
                $this->codeOnly($file->getPathname()),
                $file->getFilename() . ' must not reference the demo seeder in code'
            );
        }
    }

    public function test_no_migration_references_the_demo_academy_seeder(): void
    {
        foreach (File::allFiles(database_path('migrations')) as $file) {
            $this->assertStringNotContainsString(
                self::NEEDLE,
                $this->codeOnly($file->getPathname()),
                $file->getFilename() . ' must never seed demo data'
            );
        }
    }

    public function test_console_and_deploy_wiring_never_call_the_demo_academy_seeder(): void
    {
        $paths = array_filter([
            app_path('Console/Kernel.php'),
            base_path('routes/console.php'),
        ], 'file_exists');

        foreach ($paths as $path) {
            $this->assertStringNotContainsString(self::NEEDLE, $this->codeOnly($path), $path);
        }
    }
}
