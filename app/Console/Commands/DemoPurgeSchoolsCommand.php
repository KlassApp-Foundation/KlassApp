<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes demo schools and every row that belongs to them.
 *
 * Dry-run by default: without --force nothing is deleted; the command prints
 * the full inventory (every school, every user, and the exact per-table row
 * counts with ids) of exactly what a real run would remove.
 *
 * Safety rails:
 *  - Every targeted school must be flagged is_demo (refuses otherwise).
 *  - Idempotent: schools that are already gone are reported and skipped.
 *  - One transaction per school; a failure rolls that school back cleanly.
 *
 * Run on purpose only (never automatic, never from a migration):
 *     php artisan demo:purge-schools --school=5 --school=6          (dry run)
 *     php artisan demo:purge-schools --school=5 --school=6 --force   (delete)
 */
class DemoPurgeSchoolsCommand extends Command
{
    protected $signature = 'demo:purge-schools
        {--school=* : School ids to purge (repeatable; each must be an is_demo school)}
        {--force : Actually delete rows. Without this flag the command is a dry run.}';

    protected $description = 'Remove demo schools and their dependent rows (dry run unless --force).';

    /**
     * Tables with a school_id column, in foreign-key-safe delete order:
     * rows that reference other rows are deleted before their parents
     * (MySQL enforces the constraints; the test suite turns them on for
     * sqlite too).
     */
    private const SCHOOL_SCOPED = [
        'activity_log',
        'current_plans',
        'subscriptions',
        'marks',
        'exams',
        'attendances',
        'fee_payments',
        'fees_categories',
        'student_academics',
        'admissions',
        'class_teacher_links',
        'teacher_invites',
        'whatsapp_users',
        'school_details',
        'teacherprofile',
        'standards_link',
        'sections',
        'subjects',
        'standards',
        'academic_terms',
        'academic_years',
    ];

    /** Tables with a user_id column. */
    private const USER_SCOPED = [
        'userprofiles',
    ];

    public function handle(): int
    {
        $ids = array_values(array_unique(array_map('intval', (array) $this->option('school'))));

        if (empty($ids)) {
            $this->error('Pass at least one --school=<id>.');

            return self::FAILURE;
        }

        $schools = [];
        foreach ($ids as $id) {
            $school = \App\Models\School::find($id);

            if (! $school) {
                $this->line("school {$id}: already removed (idempotent skip).");
                continue;
            }

            if (! $school->is_demo) {
                $this->error("school {$id} ({$school->name}) is not flagged is_demo — refusing to purge it.");

                return self::FAILURE;
            }

            $schools[] = $school;
        }

        if (empty($schools)) {
            $this->info('Nothing to purge.');

            return self::SUCCESS;
        }

        $plan = [];
        foreach ($schools as $school) {
            $plan[$school->id] = $this->inventoryFor($school);
        }

        $this->printPlan($plan, $schools);

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Dry run — nothing was deleted. Re-run with --force to execute.');

            return self::SUCCESS;
        }

        foreach ($schools as $school) {
            $this->purgeSchool($school, $plan[$school->id]);
        }

        $this->newLine();
        $this->info('Done. Demo schools purged.');

        return self::SUCCESS;
    }

    /**
     * Every dependent row for one school: user rows and school-scoped rows.
     *
     * @return array{users: array, tables: array<string, array{ids: array, count: int}>}
     */
    private function inventoryFor(\App\Models\School $school): array
    {
        $users = \App\Models\User::where('school_id', $school->id)
            ->orderBy('id')
            ->get(['id', 'email', 'name', 'usergroup_id', 'status']);

        $userIds = $users->pluck('id')->all();

        $tables = [];
        foreach (self::SCHOOL_SCOPED as $table) {
            if (! \Schema::hasTable($table) || ! \Schema::hasColumn($table, 'school_id')) {
                continue;
            }
            $ids = DB::table($table)->where('school_id', $school->id)->orderBy('id')->pluck('id')->all();
            if ($ids) {
                $tables[$table] = ['ids' => $ids, 'count' => count($ids)];
            }
        }
        foreach (self::USER_SCOPED as $table) {
            if (! \Schema::hasTable($table) || ! \Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            $ids = DB::table($table)->whereIn('user_id', $userIds)->orderBy('id')->pluck('id')->all();
            if ($ids) {
                $tables[$table] = ['ids' => $ids, 'count' => count($ids)];
            }
        }

        return ['users' => $users->all(), 'tables' => $tables];
    }

    private function printPlan(array $plan, array $schools): void
    {
        foreach ($schools as $school) {
            $this->line('');
            $this->info("school {$school->id} — {$school->name} (slug {$school->slug})" . ($this->option('force') ? ' — PURGING' : ' — would purge'));
            $this->line('  users:');
            foreach ($plan[$school->id]['users'] as $u) {
                $this->line("    user id={$u->id} group={$u->usergroup_id} status={$u->status} <{$u->email}>");
            }
            $this->line('  dependent tables:');
            foreach ($plan[$school->id]['tables'] as $table => $meta) {
                $ids = implode(',', $meta['ids']);
                $shown = strlen($ids) > 300 ? substr($ids, 0, 300) . '…' : $ids;
                $this->line("    {$table}: {$meta['count']} row(s) [ids {$shown}]");
            }
        }
    }

    private function purgeSchool(\App\Models\School $school, array $inventory): void
    {
        $userIds = array_map(fn ($u) => (int) $u->id, $inventory['users']);

        DB::transaction(function () use ($school, $inventory, $userIds) {
            foreach ($inventory['tables'] as $table => $meta) {
                if (in_array($table, self::USER_SCOPED, true)) {
                    DB::table($table)->whereIn('user_id', $userIds)->delete();
                    continue;
                }
                DB::table($table)->where('school_id', $school->id)->delete();
            }

            if ($userIds) {
                DB::table('users')->whereIn('id', $userIds)->delete();
            }

            DB::table('schools')->where('id', $school->id)->delete();
        });

        $this->info("school {$school->id} ({$school->name}) purged.");
    }
}
