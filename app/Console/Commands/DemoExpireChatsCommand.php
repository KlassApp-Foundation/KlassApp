<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoFollowAlong;
use Illuminate\Console\Command;

/**
 * Removes demo chat links: a Try Demo link after 30 minutes, a number bound
 * with "BIND <code>" after DEMO_BIND_TTL_MINUTES (default 45).
 *
 * The command runs every minute and unlinks each kind one interval before
 * its limit, so no link is kept longer than the time the chat promises.
 * It unlinks only; it does not delete the whatsapp_users row.
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

    protected $description = 'Unlink demo chat numbers (Try Demo after 30 minutes, BIND after DEMO_BIND_TTL_MINUTES)';

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

        $interval = DemoFollowAlong::EXPIRY_INTERVAL_MINUTES;
        $tryDemoCutoff = now()->subMinutes(30 - $interval);
        $bindCutoff = DemoFollowAlong::bindCutoff();
        $total = 0;
        foreach ($schools as $school) {
            $query = WhatsAppUser::query()
                ->where('school_id', $school->id)
                ->whereNotNull('demo_linked_at')
                ->where(function ($q) use ($tryDemoCutoff, $bindCutoff) {
                    $q->where(fn ($w) => $w->whereNull('demo_student_id')->where('demo_linked_at', '<=', $tryDemoCutoff))
                        ->orWhere(fn ($w) => $w->whereNotNull('demo_student_id')->where('demo_linked_at', '<=', $bindCutoff));
                });
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
                'demo_student_id' => null,
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
