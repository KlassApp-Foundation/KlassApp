<?php

namespace Tests\Feature\Toshi;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use App\Services\ToshiAuditService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function createSchool(string $name = 'Audit Test School'): School
    {
        $slug = \Illuminate\Support\Str::slug($name . '-' . uniqid());
        return School::create([
            'name' => $name,
            'slug' => $slug,
            'email' => $slug . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
    }

    /** @test */
    public function log_execution_creates_activity_log_entry_with_correct_data()
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $school = $this->createSchool();

        $log = ToshiAuditService::logExecution(
            user: $user,
            school: $school,
            toolName: 'toolRecordAttendance',
            arguments: ['student' => 'Alice', 'date' => '2026-07-15'],
            result: 'Attendance recorded for Alice on 2026-07-15.',
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals('toshi', $log->log_name);
        $this->assertEquals('Executed toolRecordAttendance', $log->description);
        $this->assertEquals($user->id, $log->causer_id);
        $this->assertEquals($school->id, $log->school_id);
        $this->assertEquals('success', $log->properties['status']);
        $this->assertEquals('toolRecordAttendance', $log->properties['tool']);
        $this->assertEquals(['student' => 'Alice', 'date' => '2026-07-15'], $log->properties['arguments']);
        $this->assertEquals($user->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['approver_id']);
    }

    /** @test */
    public function log_execution_marks_failed_when_result_starts_with_cross_mark()
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $school = $this->createSchool();

        $log = ToshiAuditService::logExecution(
            user: $user,
            school: $school,
            toolName: 'toolRecordAttendance',
            arguments: [],
            result: "❌ Failed to record attendance.",
        );

        $this->assertEquals('failed', $log->properties['status']);
    }

    /** @test */
    public function log_cancellation_creates_activity_log_entry()
    {
        $user = User::factory()->create(['usergroup_id' => 3]);
        $school = $this->createSchool();

        $log = ToshiAuditService::logCancellation(
            user: $user,
            school: $school,
            toolName: 'toolCreateFee',
            arguments: ['name' => 'Tuition', 'amount' => 500000],
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals('toshi', $log->log_name);
        $this->assertEquals('Cancelled toolCreateFee', $log->description);
        $this->assertEquals($user->id, $log->causer_id);
        $this->assertEquals($school->id, $log->school_id);
        $this->assertEquals('cancelled', $log->properties['status']);
        $this->assertEquals('toolCreateFee', $log->properties['tool']);
    }

    /** @test */
    public function confirm_yes_creates_audit_log_entry()
    {
        $school = $this->createSchool('Confirm Yes Audit');
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
        ]);
        $this->actingAs($user);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('schoolId', $school->id);
        $component->set('pendingToolConfirm', [
            'tool' => 'toolRecordAttendance',
            'args' => ['student' => 'Alice', 'date' => '2026-07-15', 'status' => 'present'],
        ]);

        $component->call('confirmYes');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $school->id)
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after confirmYes');
        $this->assertEquals('toolRecordAttendance', $log->properties['tool']);
        $this->assertEquals($user->id, $log->properties['acting_user_id']);
        $this->assertEquals($user->id, $log->properties['approver_id']);
    }

    /** @test */
    public function confirm_no_creates_audit_log_entry_for_cancellation()
    {
        $school = $this->createSchool('Confirm No Audit');
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
        ]);
        $this->actingAs($user);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('schoolId', $school->id);
        $component->set('pendingToolConfirm', [
            'tool' => 'toolCreateFee',
            'args' => ['name' => 'Sports Fee', 'amount' => 200000],
        ]);

        $component->call('confirmNo');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $school->id)
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after confirmNo');
        $this->assertEquals('cancelled', $log->properties['status']);
        $this->assertEquals('toolCreateFee', $log->properties['tool']);
    }

    /** @test */
    public function audit_log_is_scoped_to_correct_school()
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $user = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolA->id]);
        $this->actingAs($user);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('schoolId', $schoolA->id);
        $component->set('pendingToolConfirm', [
            'tool' => 'toolAddStudent',
            'args' => ['name' => 'Alice'],
        ]);
        $component->call('confirmYes');

        $schoolALogs = ActivityLog::where('school_id', $schoolA->id)->count();
        $schoolBLogs = ActivityLog::where('school_id', $schoolB->id)->count();

        $this->assertEquals(1, $schoolALogs, 'School A should have 1 log entry');
        $this->assertEquals(0, $schoolBLogs, 'School B should have 0 log entries');
    }
}
