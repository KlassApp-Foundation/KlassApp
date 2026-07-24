<?php

namespace App\Console\Commands;

use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\StudentIdGeneratorService;
use Illuminate\Console\Command;

class BackfillRegistrationNumbers extends Command
{
    protected $signature = 'students:backfill-registration-numbers
        {--dry-run : Report counts without writing}';

    protected $description = 'Backfill registration_number on users from existing klassapp_student_id or fresh generation';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // ── Students with klassapp_student_id but no registration_number ──
        $fromKlassId = User::where('users.usergroup_id', 6)
            ->whereNull('users.registration_number')
            ->whereExists(function ($q) {
                $q->selectRaw(1)
                    ->from('student_academics')
                    ->whereColumn('student_academics.user_id', 'users.id')
                    ->whereNotNull('klassapp_student_id');
            })
            ->count();

        if ($fromKlassId > 0) {
            $this->line("Students with klassapp_student_id, no regno: {$fromKlassId}");
            if (! $dryRun) {
                User::where('users.usergroup_id', 6)
                    ->whereNull('users.registration_number')
                    ->whereExists(function ($q) {
                        $q->selectRaw(1)
                            ->from('student_academics')
                            ->whereColumn('student_academics.user_id', 'users.id')
                            ->whereNotNull('klassapp_student_id');
                    })
                    ->chunkById(100, function ($users) {
                        foreach ($users as $user) {
                            $sa = StudentAcademic::where('user_id', $user->id)
                                ->whereNotNull('klassapp_student_id')
                                ->first();
                            if ($sa) {
                                $user->registration_number = $sa->klassapp_student_id;
                                $user->save();
                            }
                        }
                    });
                $this->info("Backfilled {$fromKlassId} registration_number(s) from existing klassapp_student_id.");
            }
        }

        // ── Students with neither klassapp_student_id nor registration_number ──
        $neitherIds = User::where('users.usergroup_id', 6)
            ->whereNull('users.registration_number')
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                    ->from('student_academics')
                    ->whereColumn('student_academics.user_id', 'users.id')
                    ->whereNotNull('klassapp_student_id');
            })
            ->pluck('users.id');

        $neitherCount = $neitherIds->count();

        if ($neitherCount > 0) {
            $this->line("Students with neither klassapp_student_id nor regno: {$neitherCount}");
            if (! $dryRun) {
                foreach ($neitherIds as $userId) {
                    $user = User::find($userId);
                    if (! $user || ! $user->school_id) continue;

                    $newId = StudentIdGeneratorService::next($user->school_id);

                    // Set on users.registration_number
                    $user->registration_number = $newId;
                    $user->save();

                    // Also set on the student's StudentAcademic row (if one exists)
                    StudentAcademic::where('user_id', $userId)
                        ->whereNull('klassapp_student_id')
                        ->update(['klassapp_student_id' => $newId]);
                }
                $this->info("Generated fresh IDs for {$neitherCount} student(s).");
            }
        }

        // ── Students already having registration_number ──
        $already = User::where('users.usergroup_id', 6)
            ->whereNotNull('users.registration_number')
            ->where('users.registration_number', '!=', '')
            ->count();

        $this->line("Students already with registration_number (skipped): {$already}");

        $this->info('Done.');
    }
}