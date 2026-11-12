<?php

namespace App\Console\Commands;

use App\Services\Academics\ExamTypeCatalogSync;
use Illuminate\Console\Command;

/**
 * Idempotent shared exam-type catalog. Setup runs this through the reference
 * seeder. An existing environment also gets it when an admin opens exam sets
 * or exam type settings. A second run changes nothing and never edits a
 * school's own types.
 */
class SyncExamTypesCommand extends Command
{
    protected $signature = 'exam-types:sync';

    protected $description = 'Ensure the shared exam-type catalog exists';

    public function handle(ExamTypeCatalogSync $sync): int
    {
        $touched = $sync->syncShared();
        $this->info('Shared exam types ready. '.$touched.' row(s) written.');

        return self::SUCCESS;
    }
}
