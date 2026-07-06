<?php

use App\Models\Approval;
use App\Models\TeacherLeaveApplication;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill existing leave applications into the unified Approval table
        TeacherLeaveApplication::chunk(100, function ($leaves) {
            foreach ($leaves as $leave) {
                // Skip if already migrated (has an Approval record)
                if (Approval::where('approvable_type', TeacherLeaveApplication::class)
                    ->where('approvable_id', $leave->id)
                    ->exists()
                ) {
                    continue;
                }

                $state = match ($leave->status) {
                    'approved' => Approved::class,
                    'rejected' => Rejected::class,
                    default    => Pending::class,
                };

                $approval = Approval::create([
                    'approvable_type' => TeacherLeaveApplication::class,
                    'approvable_id'   => $leave->id,
                    'state'           => $state,
                    'requested_by'    => $leave->user_id,
                    'approved_by'     => $leave->approved_by,
                    'comments'        => $leave->comments,
                    'resolved_at'     => $leave->approved_on
                        ? \Carbon\Carbon::parse($leave->approved_on)
                        : null,
                ]);

                // If the leave was approved/rejected, the state needs a transition
                // record. Spatie ModelStates stores the current state; the
                // ->state attribute will reflect the value we set on create.
                $approval->save();
            }
        });
    }

    public function down(): void
    {
        Approval::where('approvable_type', TeacherLeaveApplication::class)->delete();
    }
};
