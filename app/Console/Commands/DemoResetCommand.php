<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Support\DemoSeedManifest;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Illuminate\Console\Command;

/**
 * Restores the seeded baseline for exactly one is_demo school.
 * Refuses every other school. Password hashes already on the school stay.
 */
class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset
        {school : Demo school id}
        {--dry-run : Print the counts and change nothing}';

    protected $description = 'Restore one demo school to its seeded baseline';

    public function handle(): int
    {
        $school = School::query()->find($this->argument('school'));
        if (! $school || (int) $school->is_demo !== 1) {
            $this->error('Refusing: school is not a demo school.');

            return self::FAILURE;
        }

        $manifest = DemoSeedManifest::read((int) $school->id);
        $previous = DemoJuniorSchoolSeeder::$provision;
        if (is_array($manifest['provision'] ?? null)) {
            DemoJuniorSchoolSeeder::$provision = $manifest['provision'];
        }

        try {
            return $this->call('demo:refresh', [
                'school' => $school->id,
                '--dry-run' => (bool) $this->option('dry-run'),
            ]);
        } finally {
            DemoJuniorSchoolSeeder::$provision = $previous;
        }
    }
}
