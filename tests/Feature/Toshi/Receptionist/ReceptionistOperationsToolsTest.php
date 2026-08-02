<?php

namespace Tests\Feature\Toshi\Receptionist;

use App\AiAgents\ReceptionistOperationsAgent;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\Receptionist\CreateTaskTool;
use App\AiAgents\Tools\Receptionist\ManageCallLogTool;
use App\AiAgents\Tools\Receptionist\ManagePostalRecordTool;
use App\AiAgents\Tools\Receptionist\ManageVisitorLogTool;
use App\AiAgents\Tools\Receptionist\ViewDashboardTool;
use App\AiAgents\Tools\Receptionist\ViewEventsTool;
use App\AiAgents\Tools\Receptionist\ViewNoticeboardTool;
use App\Models\ActivityLog;
use App\Models\CallLog;
use App\Models\PostalRecord;
use App\Models\Task;
use App\Models\User;
use App\Models\VisitorLog;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\TestCase;

class ReceptionistOperationsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private int $schoolId;

    private int $academicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Receptionist Toshi School',
            'slug' => 'receptionist-toshi',
            'email' => 'receptionist-toshi@test.sch.ug',
            'phone' => '+256700000010',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->receptionist = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 10,
            'email' => 'receptionist.toshi@test.sch.ug',
            'name' => 'Toshi Receptionist',
        ]);
    }

    public function test_receptionist_agent_tools_exclude_admin_and_other_role_tools(): void
    {
        $agent = new ReceptionistOperationsAgent;
        $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();

        $this->assertNotContains(AddCoAdminTool::class, $classes);
        $this->assertContains(ManageVisitorLogTool::class, $classes);
        $this->assertContains(ManageCallLogTool::class, $classes);
        $this->assertContains(ManagePostalRecordTool::class, $classes);
        $this->assertContains(ViewDashboardTool::class, $classes);
        $this->assertContains(ViewEventsTool::class, $classes);
        $this->assertContains(ViewNoticeboardTool::class, $classes);
        $this->assertContains(CreateTaskTool::class, $classes);
        $this->assertCount(8, $classes);
    }

    public function test_gates_isolate_receptionist_from_school_teacher_accountant_librarian(): void
    {
        $this->actingAs($this->receptionist);

        $this->assertTrue(Gate::inspect('toshi-school-action', $this->receptionist)->denied());
        $this->assertTrue(Gate::inspect('toshi-teacher-action', $this->receptionist)->denied());
        $this->assertTrue(Gate::inspect('toshi-accountant-action', $this->receptionist)->denied());
        $this->assertTrue(Gate::inspect('toshi-librarian-action', $this->receptionist)->denied());
        $this->assertTrue(Gate::inspect('toshi-receptionist-action', $this->receptionist)->allowed());
    }

    public function test_role_capabilities_drop_email_and_rename_noticeboard(): void
    {
        $caps = ToshiActionService::getRoleCapabilities(10);

        $this->assertNotContains('manage_email_record', $caps['actions']);
        $this->assertNotContains('manage_noticeboard', $caps['actions']);
        $this->assertContains('view_noticeboard', $caps['actions']);
        $this->assertCount(7, $caps['actions']);
    }

    public function test_school_admin_tool_denies_receptionist_via_authorize(): void
    {
        $this->actingAs($this->receptionist);
        ToshiActionService::$bypassConfirm = true;

        $result = (new AddCoAdminTool)->handle(new Request([
            'name' => 'Should Fail',
            'email' => 'coadmin.fail@test.sch.ug',
        ]));

        $this->assertStringStartsWith('❌', $result);
        ToshiActionService::$bypassConfirm = false;
    }

    public function test_manage_visitor_log_happy_path_and_requires_confirmation(): void
    {
        $this->actingAs($this->receptionist);
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        $pending = (new ManageVisitorLogTool)->handle(new Request([
            'name' => 'Guest Visitor',
            'contact_number' => '+256700111222',
            'visiting_purpose' => 'Enquiry',
            'date_of_visit' => '2026-08-01',
        ]));
        $decoded = json_decode($pending, true);
        $this->assertTrue($decoded['__tier2_confirm'] ?? false);
        $this->assertSame('toolReceptionistManageVisitorLog', $decoded['tool']);
        $this->assertFalse(VisitorLog::where('name', 'Guest Visitor')->exists());

        ToshiActionService::$bypassConfirm = true;
        $ok = (new ManageVisitorLogTool)->handle(new Request([
            'name' => 'Guest Visitor',
            'contact_number' => '+256700111222',
            'visiting_purpose' => 'Enquiry',
            'date_of_visit' => '2026-08-01',
        ]));
        $this->assertStringStartsWith('✅', $ok);
        $this->assertTrue(VisitorLog::where('name', 'Guest Visitor')->where('school_id', $this->schoolId)->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_manage_call_and_postal_logs_happy_path(): void
    {
        $this->actingAs($this->receptionist);
        ToshiActionService::$bypassConfirm = true;

        $call = (new ManageCallLogTool)->handle(new Request([
            'name' => 'Parent Caller',
            'call_type' => 'incoming',
            'call_date' => '2026-07-15',
            'incoming_number' => '0700111222',
            'description' => 'Fee enquiry',
        ]));
        $this->assertStringStartsWith('✅', $call);
        $this->assertTrue(CallLog::where('name', 'Parent Caller')->where('call_type', 'incoming')->exists());

        $postal = (new ManagePostalRecordTool)->handle(new Request([
            'type' => 'incoming',
            'confidential' => '0',
            'description' => 'Ministry circular',
            'postal_date' => '2026-07-20',
            'reference_number' => 'REF-1',
        ]));
        $this->assertStringStartsWith('✅', $postal);
        $this->assertTrue(PostalRecord::where('reference_number', 'REF-1')->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_create_task_happy_path(): void
    {
        $this->actingAs($this->receptionist);
        ToshiActionService::$bypassConfirm = true;

        $result = (new CreateTaskTool)->handle(new Request([
            'title' => 'Follow up visitor',
            'task_date' => '2026-08-10',
        ]));

        $this->assertStringStartsWith('✅', $result);
        $this->assertTrue(Task::where('user_id', $this->receptionist->id)->where('title', 'Follow up visitor')->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_view_tools_authorized_for_receptionist(): void
    {
        $this->actingAs($this->receptionist);

        foreach ([
            new ViewDashboardTool,
            new ViewEventsTool,
            new ViewNoticeboardTool,
        ] as $tool) {
            $result = $tool->handle(new Request([]));
            $this->assertStringStartsWith('✅', $result, $tool::class);
        }
    }

    public function test_unconfirmed_or_read_only_audit_leaves_approver_id_null(): void
    {
        $this->actingAs($this->receptionist);

        foreach (['ViewDashboardTool', 'ViewEventsTool', 'ViewNoticeboardTool'] as $toolName) {
            $log = ToshiAuditService::logExecution(
                user: $this->receptionist,
                school: $this->receptionist->school,
                toolName: $toolName,
                arguments: [],
                result: '✅ ok',
                actingUser: $this->receptionist,
                approver: null,
            );

            $this->assertSame($this->receptionist->id, $log->properties['acting_user_id'], $toolName);
            $this->assertArrayHasKey('approver_id', $log->properties, $toolName);
            $this->assertNull($log->properties['approver_id'], $toolName);
            $this->assertSame($this->receptionist->id, $log->causer_id, $toolName);
        }

        $this->assertTrue(ActivityLog::where('log_name', 'toshi')->where('causer_id', $this->receptionist->id)->exists());
    }

    public function test_tier2_confirm_yes_sets_approver_id_to_confirming_receptionist(): void
    {
        $this->actingAs($this->receptionist);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('schoolId', $this->schoolId)
            ->set('pendingToolConfirm', [
                'tool' => 'toolReceptionistManageVisitorLog',
                'args' => [
                    'name' => 'Confirmed Guest',
                    'contact_number' => '+256700333444',
                    'visiting_purpose' => 'Meeting',
                    'date_of_visit' => '2026-08-01',
                ],
            ])
            ->call('confirmYes');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after Tier-2 confirmYes');
        $this->assertSame('toolReceptionistManageVisitorLog', $log->properties['tool']);
        $this->assertSame($this->receptionist->id, $log->properties['acting_user_id']);
        $this->assertSame($this->receptionist->id, $log->properties['approver_id']);
        $this->assertTrue(VisitorLog::where('name', 'Confirmed Guest')->exists());
    }
}
