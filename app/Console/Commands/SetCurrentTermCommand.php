<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Setup\CurrentTermService;
use Illuminate\Console\Command;

/**
 * Operations fallback for the "Make this the current term" screen. Dry run unless --apply is given.
 * A term of another school is reported as not found, the same as a term that does not exist.
 */
class SetCurrentTermCommand extends Command
{
    protected $signature = 'terms:set-current
        {school : School id}
        {term : Term id (must belong to that school)}
        {--apply : Write the change (without it, only prints what would change)}';

    protected $description = 'Make a term the current term for a school (dry run by default)';

    public function handle(CurrentTermService $service): int
    {
        $schoolId = (int) $this->argument('school');
        $termId = (int) $this->argument('term');

        if (! School::query()->whereKey($schoolId)->exists()) {
            $this->error('School not found.');

            return self::FAILURE;
        }

        $plan = $service->plan($schoolId, $termId);
        if (! $plan['found']) {
            $this->error('Term not found for this school.');

            return self::FAILURE;
        }

        $this->line('School '.$schoolId.': term "'.$plan['term']->name.'" (id '.$termId.') is currently '.$plan['term']->status.'.');
        if ($plan['already_current']) {
            $this->info('It is already the current term. Nothing to do.');

            return self::SUCCESS;
        }
        foreach ($plan['demote'] as $row) {
            $this->line('  would move "'.$row['name'].'" (id '.$row['id'].') from current to '.$row['to']);
        }
        $this->line('  would make "'.$plan['term']->name.'" the current term');

        if (! $this->option('apply')) {
            $this->warn('Dry run: nothing was written. Run again with --apply to write.');

            return self::SUCCESS;
        }

        $service->apply($schoolId, $termId, null, 'command');
        $this->info('Done. "'.$plan['term']->name.'" is now the current term. A log line "setup.current-term" was written.');

        return self::SUCCESS;
    }
}
