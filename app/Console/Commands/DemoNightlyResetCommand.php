<?php

namespace App\Console\Commands;

use App\Models\Approval;
use App\Models\MarksRelease;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoBindApproval;
use App\Services\Demo\DemoFollowAlong;
use App\Support\DemoSeedManifest;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts every PROVISIONED demo school (is_demo = 1 and a demo:provision
 * manifest) back to its starting state. Scheduled at 03:00 Africa/Kampala
 * when DEMO_NIGHTLY_RESET_ENABLED=true; can also be run by hand.
 *
 *   1. every number bound to the school is unlinked (BIND, approved BIND,
 *      Try Demo), and attendee numbers that have no account are forgotten
 *      together with their delivery-log rows;
 *   2. demo BIND requests and their approvals are removed;
 *   3. marks releases of the school are removed (marks are unreleased);
 *   4. demo:reset restores the seeded baseline (removes users, marks,
 *      payments and attendance added during the day; demo:refresh);
 *   5. the live demo exam is put back to unreleased with its seeded marks;
 *   6. the demo admin's name and email are restored from the manifest and it
 *      stays marked is_public_demo.
 *
 * Every delete is scoped by school_id or by the numbers of that school's own
 * requests and bindings. A school that is not is_demo is never touched.
 * Sends nothing. A second run changes nothing.
 */
class DemoNightlyResetCommand extends Command
{
    protected $signature = 'demo:nightly-reset {--dry-run : List the schools and change nothing}';

    protected $description = 'Restore every provisioned demo school to its starting state (bindings, approvals, releases, data, admin)';

    public function handle(): int
    {
        $schools = School::query()->where('is_demo', 1)->orderBy('id')->get()
            ->filter(fn (School $s) => is_array(DemoSeedManifest::read((int) $s->id)['provision'] ?? null));

        if ($schools->isEmpty()) {
            $this->info('no provisioned demo school; nothing changed');

            return self::SUCCESS;
        }

        $exit = self::SUCCESS;
        foreach ($schools as $school) {
            if ($this->option('dry-run')) {
                $this->info("[{$school->id}] {$school->name}: would reset (dry run — nothing changed)");

                continue;
            }
            if (! $this->resetSchool($school)) {
                $exit = self::FAILURE;
            }
        }

        return $exit;
    }

    private function resetSchool(School $school): bool
    {
        if ((int) $school->is_demo !== 1) {
            return false;
        }
        $schoolId = (int) $school->id;
        $provision = DemoSeedManifest::read($schoolId)['provision'];

        $counts = DB::transaction(function () use ($schoolId) {
            $bound = WhatsAppUser::query()
                ->where('school_id', $schoolId)
                ->where(fn ($q) => $q->whereNotNull('demo_linked_at')->orWhereNotNull('demo_student_id'));
            $boundPhones = (clone $bound)->pluck('phone');
            $unlinked = $bound->update(DemoFollowAlong::unlinkAttributes());

            $requestIds = ParentLinkRequest::query()
                ->where('school_id', $schoolId)
                ->where('flow_token', DemoBindApproval::REQUEST_TOKEN)
                ->pluck('id');
            $phones = ParentLinkRequest::query()->whereIn('id', $requestIds)->pluck('phone')->merge($boundPhones)->unique()->values();
            Approval::query()
                ->where('approvable_type', ParentLinkRequest::class)
                ->whereIn('approvable_id', $requestIds)
                ->delete();
            $requests = ParentLinkRequest::query()->whereIn('id', $requestIds)->delete();
            $releases = MarksRelease::query()->where('school_id', $schoolId)->delete();

            // Attendee numbers of this school only, and only once they have no account.
            $forgotten = WhatsAppUser::query()->whereIn('phone', $phones)->whereNull('user_id')->whereNull('school_id')->delete();
            if (Schema::hasTable('message_delivery_log')) {
                DB::table('message_delivery_log')->whereIn('phone', $phones)
                    ->whereNotIn('phone', WhatsAppUser::query()->whereNotNull('user_id')->select('phone'))
                    ->delete();
            }

            return compact('unlinked', 'requests', 'releases', 'forgotten');
        });

        $this->call('demo:reset', ['school' => $schoolId]);
        (new DemoJuniorSchoolSeeder())->restoreFollowAlongExam($school->fresh());

        $restored = 0;
        $admin = User::query()->where('school_id', $schoolId)->where('usergroup_id', 3)
            ->where(fn ($q) => $q->where('is_public_demo', true)->orWhere('email', strtolower((string) ($provision['admin_email'] ?? ''))))
            ->orderByDesc('is_public_demo')->orderBy('id')->first();
        if ($admin) {
            $want = ['name' => (string) ($provision['admin_name'] ?? $admin->name), 'email' => strtolower((string) ($provision['admin_email'] ?? $admin->email)), 'is_public_demo' => true];
            $emailTaken = User::query()->where('email', $want['email'])->whereKeyNot($admin->id)->exists();
            if ($emailTaken) {
                unset($want['email']);
            }
            $changed = array_filter($want, fn ($v, $k) => $admin->getAttribute($k) != $v, ARRAY_FILTER_USE_BOTH);
            if ($changed !== []) {
                $admin->forceFill($changed)->save();
                $restored = 1;
            }
        }

        $this->info("[{$schoolId}] {$school->name}: unlinked numbers={$counts['unlinked']} bind requests removed={$counts['requests']}"
            . " releases removed={$counts['releases']} forgotten numbers={$counts['forgotten']} admin restored={$restored}");

        return true;
    }
}
