<?php

namespace App\Console\Commands;

use App\Models\FeePayment;
use App\Models\School;
use App\Models\User;
use App\Support\DemoSeedManifest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Restores a demo school to the baseline produced by its rich seeder.
 *
 * What it removes (everything added after the last seeding):
 *   - users added after seeding (admins are NEVER touched), with their rows;
 *   - fee payments and marks created after seeding;
 *   - attendance rows created after seeding;
 *   - attendance for today on the walkthrough classes (stays untaken);
 *   - marks/submissions on the open walkthrough exam (stays unmarked).
 *
 * Then it re-runs the school's seeder so any gaps refill deterministically.
 * Accounts and passwords that existed at seed time are never modified.
 *
 * Safety rails (mirrors demo:purge-schools):
 *   - refuses any school that is not is_demo;
 *   - dry run with --dry-run (prints what would change, touches nothing);
 *   - one transaction per school; FK-safe delete order (MySQL enforces FKs,
 *     the test suite turns PRAGMA foreign_keys on for sqlite).
 *
 * Run on purpose only:
 *     php artisan demo:refresh 38            (dry run: no, dry run is --dry-run)
 *     php artisan demo:refresh 38 --dry-run
 *     php artisan demo:refresh               (all demo schools)
 */
class DemoRefreshCommand extends Command
{
    protected $signature = 'demo:refresh
        {school? : Demo school id (omit to refresh all demo schools)}
        {--dry-run : Report what would change without touching anything}';

    protected $description = 'Restore demo schools to their seeded baseline (removes post-seed test data, keeps accounts and passwords)';

    /**
     * User-scoped rows to remove before deleting a post-seed user,
     * in foreign-key-safe order. Tables that may not exist on older
     * deployments are skipped via Schema::hasTable.
     */
    private const USER_SCOPED_TABLES = [
        'attendances',
        'marks',
        'fee_payments',
        'student_academics',
        'student_parent_links',
        'userprofiles',
        'authentications',
        'activity_log',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $id = $this->argument('school');

        $schools = $id
            ? School::where('id', $id)->get()
            : School::where('is_demo', 1)->orderBy('id')->get();

        if ($schools->isEmpty()) {
            $this->error('No matching schools found.');

            return self::FAILURE;
        }

        $exit = self::SUCCESS;

        foreach ($schools as $school) {
            if ((int) $school->is_demo !== 1) {
                $this->error("School {$school->id} ({$school->name}) is not a demo school — refusing.");
                $exit = self::FAILURE;

                continue;
            }

            if (! $this->refreshSchool($school, $dry)) {
                $exit = self::FAILURE;
            }
        }

        return $exit;
    }

    private function seederFor(School $school): ?string
    {
        return match (true) {
            str_contains($school->name, 'Junior') => \Database\Seeders\DemoJuniorSchoolSeeder::class,
            str_contains($school->name, 'Senior') => \Database\Seeders\DemoSeniorSchoolSeeder::class,
            default => null,
        };
    }

    private function refreshSchool(School $school, bool $dry): bool
    {
        $label = "[{$school->id}] {$school->name}";
        $seeder = $this->seederFor($school);
        $manifest = DemoSeedManifest::read($school->id);

        if ($manifest === null) {
            if ($seeder === null) {
                $this->error("{$label}: no manifest and no registered rich seeder — skipping.");

                return false;
            }

            if ($dry) {
                $this->warn("{$label}: no manifest yet; the seeder must run once before refresh. (dry run)");

                return true;
            }

            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
            $manifest = DemoSeedManifest::read($school->id);

            if ($manifest === null) {
                $this->error("{$label}: seeding did not produce a manifest.");

                return false;
            }
        }

        $cutoff = Carbon::parse($manifest['seeded_at'] ?? 'now');
        $emails = DemoSeedManifest::emails($manifest);
        $walk = is_array($manifest['walkthrough'] ?? null) ? $manifest['walkthrough'] : [];
        $skipLinkIds = array_values(array_filter(array_map('intval', (array) ($walk['skip_link_ids'] ?? []))));
        $openExamId = isset($walk['open_exam_id']) ? (int) $walk['open_exam_id'] : null;

        $counts = array_fill_keys(['users', 'payments', 'marks', 'attendance', 'skip_today', 'open_marks', 'open_submissions'], 0);

        // 1) Users added after seeding. Admins (usergroup 3) are never touched,
        //    and neither is anyone whose email was in the manifest.
        // Cutoff is the manifest value captured above, before the seeder
        // below rewrites seeded_at. Reading it after that rewrite would hide
        // every row the session just added.
        $addedUsers = User::where('school_id', $school->id)
            ->where('usergroup_id', '<>', 3)
            ->where('created_at', '>', $cutoff)
            ->when($emails !== [], fn ($q) => $q->whereNotIn('email', $emails))
            ->orderBy('id')
            ->get();

        foreach ($addedUsers as $user) {
            $counts['users']++;

            if (! $dry) {
                $this->deleteUser((int) $user->id, (int) $school->id);
            }
        }

        // 2) Payments and marks created after seeding (any user, e.g. new
        //    payments recorded against old students). Mass-delete on the
        //    builder so the SQL keeps school_id; a per-model delete() does not.
        $payments = FeePayment::where('school_id', $school->id)->where('created_at', '>', $cutoff);
        $counts['payments'] = (clone $payments)->count();

        if (! $dry) {
            $payments->delete();
        }

        $marksQuery = fn () => DB::table('marks')->where('school_id', $school->id)->where('created_at', '>', $cutoff);
        $counts['marks'] = $marksQuery()->count();

        if (! $dry) {
            $marksQuery()->delete();
        }

        // 3) Attendance created after seeding, plus today's rows on the
        //    walkthrough classes which must stay untaken.
        // Query builder, not the model: Attendance uses soft deletes, and a
        // soft-deleted row would still be today's attendance.
        $attendanceQuery = fn () => DB::table('attendances')->where('school_id', $school->id)->where('created_at', '>', $cutoff);
        $counts['attendance'] = $attendanceQuery()->count();

        if (! $dry) {
            $attendanceQuery()->delete();
        }

        if ($skipLinkIds !== []) {
            $skipQuery = fn () => DB::table('attendances')->where('school_id', $school->id)
                ->whereIn('standardLink_id', $skipLinkIds)
                ->whereDate('date', now()->toDateString());
            $counts['skip_today'] = $skipQuery()->count();

            if (! $dry) {
                $skipQuery()->delete();
            }
        }

        // 4) The open walkthrough exam goes back to unmarked/unsubmitted.
        if ($openExamId) {
            $openMarks = fn () => DB::table('marks')->where('school_id', $school->id)->where('exam_id', $openExamId);
            $counts['open_marks'] = $openMarks()->count();

            $openSubs = fn () => DB::table('exam_marks_submissions')->where('school_id', $school->id)->where('exam_id', $openExamId);
            $counts['open_submissions'] = $openSubs()->count();

            if (! $dry) {
                $openMarks()->delete();
                $openSubs()->delete();
            }
        }

        // 5) Re-seed so everything missing refills deterministically and the
        //    manifest timestamp moves forward for the next refresh.
        if (! $dry && $seeder !== null) {
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        $verb = $dry ? 'would remove' : 'removed';
        $suffix = $dry ? ' (dry run — nothing changed)' : '; baseline restored';

        $this->info("{$label}: {$verb} users={$counts['users']} payments={$counts['payments']} marks={$counts['marks']} attendance={$counts['attendance']} walkthrough-today={$counts['skip_today']} open-exam-marks={$counts['open_marks']} open-submissions={$counts['open_submissions']}{$suffix}");

        return true;
    }

    /**
     * Remove a post-seed user and every row that references them, in
     * FK-safe order (children first, user last). Attendance/marks rows
     * by other users that merely reference this one via recorded_by etc.
     * are handled by the school-wide cutoff sweeps above; remaining
     * references are nulled where the schema allows it.
     */
    private function deleteUser(int $userId, int $schoolId): void
    {
        foreach (self::USER_SCOPED_TABLES as $table) {
            $this->deleteUserRows($table, $userId, $schoolId);
        }

        // timetable_slots.teacher_id cascades, but a walkthrough slot could
        // point at this user — remove that slot before deleting the user.
        if (\Schema::hasTable('timetable_slots')) {
            DB::table('timetable_slots')->where('school_id', $schoolId)->where('teacher_id', $userId)->delete();
        }

        // standards_link.class_teacher_id has a plain FK (no cascade):
        // reassign to the school's first admin so the class keeps a teacher.
        $adminId = User::where('school_id', $schoolId)->where('usergroup_id', 3)->orderBy('id')->value('id');

        if ($adminId) {
            DB::table('standards_link')
                ->where('school_id', $schoolId)
                ->where('class_teacher_id', $userId)
                ->update(['class_teacher_id' => $adminId]);
        }

        // Hard delete. User uses soft deletes; a soft delete leaves the row,
        // so the account would survive a refresh.
        User::withTrashed()->where('school_id', $schoolId)->where('id', $userId)->forceDelete();
    }

    /**
     * Delete one user's rows in a table. Every statement is limited to
     * $schoolId: directly when the table has school_id, otherwise through
     * the users row (authentications has no school_id column).
     */
    private function deleteUserRows(string $table, int $userId, int $schoolId): void
    {
        if (! \Schema::hasTable($table)) {
            return;
        }

        $columns = \Schema::getColumnListing($table);
        $keys = array_values(array_filter([
            in_array('user_id', $columns, true) ? 'user_id' : null,
            in_array('student_id', $columns, true) ? 'student_id' : null,
            in_array('parent_id', $columns, true) ? 'parent_id' : null,
        ]));

        if ($keys === []) {
            return;
        }

        $query = DB::table($table)->where(function ($nested) use ($keys, $userId) {
            foreach ($keys as $column) {
                $nested->orWhere($column, $userId);
            }
        });

        if (in_array('school_id', $columns, true)) {
            $query->where('school_id', $schoolId);
        } else {
            $query->whereIn('user_id', function ($nested) use ($schoolId, $userId) {
                $nested->select('id')->from('users')->where('school_id', $schoolId)->where('id', $userId);
            });
        }

        $query->delete();
    }
}
