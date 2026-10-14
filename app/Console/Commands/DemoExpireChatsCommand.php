<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\WhatsAppUser;
use Illuminate\Console\Command;

/**
 * Removes demo chat links that are older than 30 minutes.
 *
 * Only is_demo schools are visited, and each visit is scoped by that
 * school's id. A non-demo school is refused. --dry-run prints a count
 * and changes nothing. A second run after the links are gone changes nothing.
 */
class DemoExpireChatsCommand extends Command
{
    protected $signature = 'demo:expire-chats
        {school? : Demo school id (omit to check every demo school)}
        {--dry-run : Print the count and change nothing}';

    protected $description = 'Unlink demo chat numbers that have been linked for 30 minutes';

    public function handle(): int
    {
        $schoolId = $this->argument('school');
        if ($schoolId !== null) {
            $school = School::query()->find($schoolId);
            if (! $school || ! $school->is_demo) {
                $this->error('Refusing: school is not a demo school.');

                return self::FAILURE;
            }
            $schools = collect([$school]);
        } else {
            $schools = School::query()->where('is_demo', 1)->get();
        }

        $cutoff = now()->subMinutes(30);
        $total = 0;
        foreach ($schools as $school) {
            $query = WhatsAppUser::query()
                ->where('school_id', $school->id)
                ->whereNotNull('demo_linked_at')
                ->where('demo_linked_at', '<=', $cutoff);
            $count = (clone $query)->count();
            $total += $count;
            if ($count === 0 || $this->option('dry-run')) {
                continue;
            }
            $query->update([
                'user_id' => null,
                'school_id' => null,
                'opted_in' => false,
                'demo_linked_at' => null,
            ]);
        }

        if ($this->option('dry-run')) {
            $this->info("would unlink chats={$total} (dry run — nothing changed)");
        } else {
            $this->info("unlinked chats={$total}");
        }

        return self::SUCCESS;
    }
}
