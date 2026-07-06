<?php

namespace App\Console\Commands;

use App\Models\Approval;
use App\Models\TeacherLeaveApplication;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use Illuminate\Console\Command;

class MigrateLeaveApprovals extends Command
{
    protected $signature = 'approvals:migrate-leave';

    protected $description = 'Migrate existing TeacherLeaveApplication statuses into the unified approvals table';

    public function handle(): int
    {
        $mapped = 0;
        $skipped = 0;

        TeacherLeaveApplication::chunk(100, function ($leaves) use (&$mapped, &$skipped) {
            foreach ($leaves as $leave) {
                if ($leave->approvals()->exists()) {
                    $skipped++;
                    continue;
                }

                $state = match ($leave->status) {
                    'approved' => Approved::class,
                    'rejected', 'declined' => Rejected::class,
                    default => Pending::class,
                };

                $approval = $leave->approvals()->create([
                    'state'        => $state,
                    'requested_by' => $leave->user_id,
                    'approved_by'  => $leave->approved_by,
                    'comments'     => $leave->comments,
                    'resolved_at'  => $leave->approved_on,
                ]);

                $mapped++;
            }
        });

        $this->info("Mapped {$mapped} leave applications, skipped {$skipped} (already have approval records).");

        return Command::SUCCESS;
    }
}
