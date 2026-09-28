<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\User;
use App\Models\Usergroup;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class FlagNeverLoggedIn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gego:flag-never-logged-in
        {--apply : Set is_reset=1 on the matching accounts (default: read-only report)}
        {--school= : Limit to a single school id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flag active accounts that have never logged in so they must change their password at next login (is_reset=1)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->matchingUsers();

        if ($this->option('school') !== null) {
            $query->where('school_id', (int) $this->option('school'));
        }

        $total = (clone $query)->count();

        $this->info("Never-logged-in active accounts with is_reset=0: {$total}");
        $this->reportBreakdown($query, $total);

        if (! $this->option('apply')) {
            $this->newLine();
            $this->line('Read-only report. Run again with --apply to set is_reset=1 on these accounts.');
            return self::SUCCESS;
        }

        if ($total === 0) {
            $this->line('Nothing to flag.');
            return self::SUCCESS;
        }

        // Pluck ids first: chunking a query whose rows stop matching as we
        // update them (is_reset 0 → 1) would silently skip rows.
        $ids = (clone $query)->pluck('id');
        $flagged = 0;

        // Save per model (not a bulk update) so UserObserver cache
        // invalidation runs for every changed account.
        User::whereIn('id', $ids)->orderBy('id')->chunkById(200, function ($users) use (&$flagged) {
            foreach ($users as $user) {
                $user->is_reset = 1;
                $user->save();
                $flagged++;
            }
        });

        $this->info("Flagged {$flagged} account(s): is_reset=1 — they will be prompted to change their password at next login.");

        return self::SUCCESS;
    }

    /**
     * Accounts eligible for forced password change on next login:
     * active status (positive equality — never a negated enum filter),
     * is_reset already 0, a usable password, and no recorded login ever.
     */
    private function matchingUsers(): Builder
    {
        return User::query()
            ->where('users.status', 'active')
            ->where('users.is_reset', 0)
            ->whereNotNull('users.password')
            ->where('users.password', '!=', '')
            ->whereNotExists(function ($join) {
                $join->selectRaw('1')
                    ->from('activity_log')
                    ->whereColumn('activity_log.causer_id', 'users.id')
                    ->where('activity_log.log_name', 'login');
            });
    }

    /**
     * Human-readable breakdown so the operator can sanity-check the scope
     * before running with --apply.
     */
    private function reportBreakdown(Builder $query, int $total): void
    {
        if ($total === 0) {
            return;
        }

        $this->newLine();
        $this->line('By usergroup:');

        $groupNames = Usergroup::pluck('name', 'id')->all();
        $byGroup = (clone $query)
            ->selectRaw('usergroup_id, COUNT(*) as c')
            ->groupBy('usergroup_id')
            ->orderByDesc('c')
            ->get();

        foreach ($byGroup as $row) {
            $name = $groupNames[$row->usergroup_id] ?? 'unknown';
            $this->line("  - {$name} (usergroup {$row->usergroup_id}): {$row->c}");
        }

        $distinctSchools = (clone $query)->distinct()->count('school_id');
        $this->newLine();
        $this->line("Schools affected (distinct school_id): {$distinctSchools}");

        $bySchool = (clone $query)
            ->join('schools', 'schools.id', '=', 'users.school_id')
            ->selectRaw('users.school_id, schools.name, COUNT(*) as c')
            ->groupBy('users.school_id', 'schools.name')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        foreach ($bySchool as $row) {
            $this->line("  - {$row->name} (school {$row->school_id}): {$row->c}");
        }

        $dateRange = (clone $query)
            ->selectRaw('MIN(created_at) as oldest, MAX(created_at) as newest')
            ->first();
        if ($dateRange && $dateRange->oldest) {
            $this->newLine();
            $this->line("Account created between {$dateRange->oldest} and {$dateRange->newest}");
        }
    }
}
