<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Approval;
use App\Models\School;
use App\Models\TeacherLeaveApplication;
use App\Models\User;
use App\Models\WhatsAppPendingNotification;
use App\Models\WhatsAppUser;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Approval status changes are queued for the teacher's next inbound message,
 * never sent cold.
 */
class ApprovalStatusQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_status_change_queues_the_message_instead_of_sending_it(): void
    {
        Http::fake();

        $school = School::create([
            'name' => 'Approval Queue School',
            'slug' => 'approval-queue-school',
            'email' => 'approval-queue@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        $teacher = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Queued Teacher',
            'email' => 'queued-teacher@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        WhatsAppUser::create([
            'phone' => '+256770333444',
            'user_id' => $teacher->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $leave = TeacherLeaveApplication::create([
            'school_id' => $school->id,
            'academic_year_id' => 1,
            'user_id' => $teacher->id,
            'from_date' => '2026-11-03 08:00:00',
            'to_date' => '2026-11-03 17:00:00',
            'remarks' => 'Personal',
            'status' => 'pending',
        ]);

        $approval = Approval::create([
            'approvable_type' => TeacherLeaveApplication::class,
            'approvable_id' => $leave->id,
            'state' => Pending::class,
            'requested_by' => $teacher->id,
        ]);

        $approval->state = new Approved($approval);
        $approval->save();

        $queued = WhatsAppPendingNotification::where('whatsapp_user_id', $teacher->id)->get();
        $this->assertCount(1, $queued);
        $this->assertSame('approval_status', $queued->first()->flow_type);

        Http::assertNothingSent();
    }
}
