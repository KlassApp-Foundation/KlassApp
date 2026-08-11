<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixStudentDisplayNames extends Command
{
    protected $signature = 'students:fix-names {school_id : School to fix}';
    protected $description = 'Backfill User.name from Userprofile.firstname + lastname for a school';

    public function handle(): int
    {
        $schoolId = (int) $this->argument('school_id');

        $users = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', '!=', 'exit')
            ->with('userprofile')
            ->get();

        if ($users->isEmpty()) {
            $this->warn("No active students found for school {$schoolId}.");

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($users as $user) {
            $first = trim((string) ($user->userprofile->firstname ?? ''));
            $last  = trim((string) ($user->userprofile->lastname ?? ''));

            if ($first === '' && $last === '') {
                continue;
            }

            $display = trim($first.' '.$last);
            if ($display === '') {
                continue;
            }

            $user->forceFill(['name' => $display])->save();
            $count++;
        }

        $this->info("Fixed {$count} student display names for school {$schoolId}.");

        return self::SUCCESS;
    }
}
