<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Setup\SectionClassLinker;
use Illuminate\Console\Command;

/**
 * Operations fallback for the Classes page "Link to a class" screen. Dry run unless --apply is given.
 * A section, class level or year of another school is reported as not found.
 */
class LinkSectionToClassCommand extends Command
{
    protected $signature = 'classes:link-section
        {school : School id}
        {section : Section id (must belong to that school)}
        {standard : Class level id (must belong to that school)}
        {--year= : Academic year id (default: the school\'s current year)}
        {--apply : Write the link (without it, only prints what would change)}';

    protected $description = 'Link a section to a class level for an academic year (dry run by default)';

    public function handle(SectionClassLinker $linker): int
    {
        $schoolId = (int) $this->argument('school');
        if (! School::query()->whereKey($schoolId)->exists()) {
            $this->error('School not found.');

            return self::FAILURE;
        }

        $yearId = $this->option('year') === null ? null : (int) $this->option('year');
        $plan = $linker->plan($schoolId, (int) $this->argument('section'), (int) $this->argument('standard'), $yearId);

        if ($plan['result'] === SectionClassLinker::NOT_FOUND) {
            $this->error('Section, class level or academic year not found for this school.');

            return self::FAILURE;
        }
        if ($plan['result'] === SectionClassLinker::ALREADY_LINKED) {
            $this->info('Section "'.$plan['section']->name.'" is already linked to a class for '.$plan['year']->name.'. Nothing to do.');

            return self::SUCCESS;
        }

        $this->line('Would link section "'.$plan['section']->name.'" (id '.$plan['section']->id.') to class level "'.$plan['standard']->name.'" (id '.$plan['standard']->id.') for '.$plan['year']->name.' (id '.$plan['year']->id.').');

        if (! $this->option('apply')) {
            $this->warn('Dry run: nothing was written. Run again with --apply to write.');

            return self::SUCCESS;
        }

        $linker->apply($schoolId, (int) $plan['section']->id, (int) $plan['standard']->id, (int) $plan['year']->id, null, 'command');
        $this->info('Done. A log line "setup.link-section" was written.');

        return self::SUCCESS;
    }
}
