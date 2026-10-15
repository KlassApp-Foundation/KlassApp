<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoBindApproval;
use App\Services\Demo\DemoFollowAlong;
use Illuminate\Console\Command;

/**
 * Removes demo chat links: a Try Demo link after 30 minutes, a number bound
 * with "BIND <code>" after DEMO_BIND_TTL_MINUTES (default 45).
 *
 * The command runs every minute and unlinks each kind one interval before
 * its limit, so no link is kept longer than the time the chat promises.
 * It unlinks only; it does not delete the whatsapp_users row. A BIND
 * request still waiting for approval (DEMO_BIND_REQUIRES_APPROVAL) after
 * the same TTL is closed: its approval is rejected as expired.
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
        $requests = 0;
        $approval = app(DemoBindApproval::class);
        foreach ($schools as $school) {
            // BIND requests nobody approved within the TTL are closed too.
            $requests += $approval->expirePending($school, $bindCutoff, (bool) $this->option('dry-run'));

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
            $query->update(DemoFollowAlong::unlinkAttributes());
        }

        if ($this->option('dry-run')) {
            $this->info("would unlink chats={$total} (dry run — nothing changed)");
            if ($requests > 0) {
                $this->info("would expire pending bind requests={$requests}");
            }
        } else {
            $this->info("unlinked chats={$total}");
            if ($requests > 0) {
                $this->info("expired pending bind requests={$requests}");
            }
        }

        return self::SUCCESS;
    }
}
