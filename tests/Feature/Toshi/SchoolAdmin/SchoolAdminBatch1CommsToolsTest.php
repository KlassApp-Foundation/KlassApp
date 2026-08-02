<?php

namespace Tests\Feature\Toshi\SchoolAdmin;

use App\AiAgents\AccountantOperationsAgent;
use App\AiAgents\DeputyAdminOperationsAgent;
use App\AiAgents\LibrarianOperationsAgent;
use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\ReceptionistOperationsAgent;
use App\AiAgents\Skills\SchoolCommsSkill;
use App\AiAgents\StudentOperationsAgent;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\CreateEventTool;
use App\AiAgents\Tools\CreateHolidayTool;
use App\AiAgents\Tools\CreateNoticeTool;
use App\AiAgents\Tools\ListEventsTool;
use App\AiAgents\Tools\ListHolidaysTool;
use App\AiAgents\Tools\ListNoticesTool;
use App\AiAgents\Tools\Receptionist\ViewNoticeboardTool;
use App\AiAgents\Tools\RouteToSchoolCommsSkillTool;
use App\AiAgents\Tools\UpdateEventTool;
use App\AiAgents\Tools\UpdateHolidayTool;
use App\AiAgents\Tools\UpdateNoticeTool;
use App\AiAgents\ToshiOrchestrator;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Listeners\Toshi\LogToolInvoked;
use App\Livewire\AgentToshi;
use App\Models\ActivityLog;
use App\Models\Events;
use App\Models\NoticeBoard;
use App\Models\User;
use App\Services\Toshi\ReceptionistActionService;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolAdminBatch1CommsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;

    private User $otherAdmin;

    private User $deputy;

    private User $receptionist;

    private int $schoolId;

    private int $otherSchoolId;

    private int $academicYearId;

    private int $otherAcademicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Batch1 Comms School',
            'slug' => 'batch1-comms',
            'email' => 'batch1-comms@test.sch.ug',
            'phone' => '+256700000301',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherSchoolId = DB::table('schools')->insertGetId([
            'name' => 'Other Comms School',
            'slug' => 'other-comms',
            'email' => 'other-comms@test.sch.ug',
            'phone' => '+256700000302',
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

        $this->otherAcademicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->otherSchoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolAdmin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'email' => 'admin.batch1@test.sch.ug',
            'name' => 'Batch1 Admin',
        ]);

        $this->otherAdmin = User::factory()->create([
            'school_id' => $this->otherSchoolId,
            'usergroup_id' => 3,
            'email' => 'admin.other.batch1@test.sch.ug',
            'name' => 'Other Admin',
        ]);

        $this->deputy = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 4,
            'email' => 'deputy.batch1@test.sch.ug',
            'name' => 'Batch1 Deputy',
        ]);

        $this->receptionist = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 10,
            'email' => 'reception.batch1@test.sch.ug',
            'name' => 'Batch1 Receptionist',
        ]);
    }

    public function test_notices_share_notice_board_with_receptionist_view(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;

        $create = (new CreateNoticeTool)->handle(new Request([
            'title' => 'Shared Notice Source',
            'description' => 'Posted by school admin via Toshi',
            'type' => 'school',
            'publish_date' => '2026-07-01',
            'expire_date' => '2026-12-31',
        ]));

        ToshiActionService::$bypassConfirm = false;

        $this->assertStringStartsWith('✅', $create);
        $this->assertDatabaseHas('notice_board', [
            'school_id' => $this->schoolId,
            'title' => 'Shared Notice Source',
        ]);

        $notice = NoticeBoard::where('school_id', $this->schoolId)
            ->where('title', 'Shared Notice Source')
            ->first();
        $this->assertNotNull($notice);

        $this->actingAs($this->receptionist);
        $viewed = ReceptionistActionService::viewNoticeboard($this->receptionist);
        $this->assertTrue($viewed['success']);
        $this->assertStringContainsString('Shared Notice Source', $viewed['message']);

        $toolView = (new ViewNoticeboardTool)->handle(new Request([]));
        $this->assertStringContainsString('Shared Notice Source', $toolView);
    }

    public function test_create_list_update_notice_happy_and_validation(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;

        $bad = (new CreateNoticeTool)->handle(new Request([
            'title' => '',
            'description' => 'x',
        ]));
        $this->assertStringStartsWith('❌', $bad);

        $ok = (new CreateNoticeTool)->handle(new Request([
            'title' => 'PTA Meeting',
            'description' => 'Please attend',
            'type' => 'school',
            'publish_date' => '2026-08-01',
            'expire_date' => '2026-08-31',
        ]));
        $this->assertStringStartsWith('✅', $ok);

        $list = (new ListNoticesTool)->handle(new Request([]));
        $this->assertStringContainsString('PTA Meeting', $list);

        $notice = NoticeBoard::where('title', 'PTA Meeting')->firstOrFail();
        $update = (new UpdateNoticeTool)->handle(new Request([
            'notice_id' => $notice->id,
            'title' => 'PTA Meeting Updated',
        ]));
        $this->assertStringStartsWith('✅', $update);
        $this->assertSame('PTA Meeting Updated', $notice->fresh()->title);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_create_list_update_event_and_holiday_happy_paths(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;

        $eventBad = (new CreateEventTool)->handle(new Request([
            'title' => 'Bad',
            'start_date' => '2026-09-01',
            'end_date' => '2026-08-01',
        ]));
        $this->assertStringStartsWith('❌', $eventBad);

        $eventOk = (new CreateEventTool)->handle(new Request([
            'title' => 'Sports Day',
            'description' => 'All day',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'category' => 'meeting',
            'select_type' => 'school',
        ]));
        $this->assertStringStartsWith('✅', $eventOk);

        $listEvents = (new ListEventsTool)->handle(new Request([]));
        $this->assertStringContainsString('Sports Day', $listEvents);

        $event = Events::where('title', 'Sports Day')->where('category', '!=', 'holidays')->firstOrFail();
        $this->assertTrue(Gate::forUser($this->schoolAdmin)->allows('event', $event));

        $updateEvent = (new UpdateEventTool)->handle(new Request([
            'event_id' => $event->id,
            'title' => 'Sports Day II',
        ]));
        $this->assertStringStartsWith('✅', $updateEvent);
        $this->assertSame('Sports Day II', $event->fresh()->title);

        $holidayOk = (new CreateHolidayTool)->handle(new Request([
            'title' => 'Independence Day',
            'date' => '2026-10-09',
        ]));
        $this->assertStringStartsWith('✅', $holidayOk);

        $listHolidays = (new ListHolidaysTool)->handle(new Request([]));
        $this->assertStringContainsString('Independence Day', $listHolidays);
        $this->assertStringNotContainsString('Sports Day', $listHolidays);

        $holiday = Events::where('title', 'Independence Day')->where('category', 'holidays')->firstOrFail();
        $updateHoliday = (new UpdateHolidayTool)->handle(new Request([
            'holiday_id' => $holiday->id,
            'title' => 'Uganda Independence',
        ]));
        $this->assertStringStartsWith('✅', $updateHoliday);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_ug3_cannot_update_another_schools_event_via_toshi_tool(): void
    {
        $foreign = new Events([
            'school_id' => $this->otherSchoolId,
            'academic_year_id' => $this->otherAcademicYearId,
            'select_type' => 'school',
            'title' => 'Foreign Event',
            'description' => 'Other school',
            'category' => 'meeting',
            'start_date' => '2026-09-01 08:00:00',
            'end_date' => '2026-09-01 17:00:00',
            'status' => 'active',
            'created_by' => $this->otherAdmin->id,
        ]);
        $foreign->batch = '';
        $foreign->color = 'green';
        $foreign->save();

        $this->actingAs($this->schoolAdmin);
        $this->assertTrue(Gate::denies('event', $foreign));

        ToshiActionService::$bypassConfirm = true;
        $result = (new UpdateEventTool)->handle(new Request([
            'event_id' => $foreign->id,
            'title' => 'Hijacked Title',
        ]));
        ToshiActionService::$bypassConfirm = false;

        $this->assertStringStartsWith('❌', $result);
        $this->assertStringContainsString('not authorized', strtolower($result));
        $this->assertSame('Foreign Event', $foreign->fresh()->title);
    }

    public function test_deputy_inherits_school_comms_tools_not_settings(): void
    {
        $classes = collect((new DeputyAdminOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();

        foreach ([
            ListNoticesTool::class,
            CreateNoticeTool::class,
            UpdateNoticeTool::class,
            ListEventsTool::class,
            CreateEventTool::class,
            UpdateEventTool::class,
            ListHolidaysTool::class,
            CreateHolidayTool::class,
            UpdateHolidayTool::class,
        ] as $tool) {
            $this->assertContains($tool, $classes);
        }

        $this->actingAs($this->deputy);
        $list = (new ListNoticesTool)->handle(new Request([]));
        $this->assertStringNotContainsString('not authorized', strtolower($list));
    }

    public function test_orchestrator_and_skill_register_school_comms(): void
    {
        $orch = collect((new ToshiOrchestrator)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertContains(RouteToSchoolCommsSkillTool::class, $orch);

        $skill = collect((new SchoolCommsSkill)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertCount(9, $skill);
        $this->assertContains(CreateNoticeTool::class, $skill);
        $this->assertContains(UpdateEventTool::class, $skill);
    }

    public function test_school_comms_tools_absent_from_non_admin_agents(): void
    {
        $targets = [
            ListNoticesTool::class,
            CreateNoticeTool::class,
            UpdateNoticeTool::class,
            ListEventsTool::class,
            CreateEventTool::class,
            UpdateEventTool::class,
            ListHolidaysTool::class,
            CreateHolidayTool::class,
            UpdateHolidayTool::class,
            RouteToSchoolCommsSkillTool::class,
        ];

        $agents = [
            new TeacherOperationsAgent,
            new AccountantOperationsAgent,
            new LibrarianOperationsAgent,
            new ReceptionistOperationsAgent,
            new StudentOperationsAgent,
            new ParentOperationsAgent,
        ];

        foreach ($agents as $agent) {
            $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();
            foreach ($targets as $tool) {
                $this->assertNotContains($tool, $classes, $agent::class.' must not expose '.$tool);
            }
        }
    }

    public function test_whatsapp_lists_allowed_and_writes_fail_closed(): void
    {
        $waClasses = collect((new SchoolAdminWhatsAppReadAgent)->tools())->map(fn ($t) => $t::class)->all();

        $this->assertContains(ListNoticesTool::class, $waClasses);
        $this->assertContains(ListEventsTool::class, $waClasses);
        $this->assertContains(ListHolidaysTool::class, $waClasses);
        $this->assertNotContains(CreateNoticeTool::class, $waClasses);
        $this->assertNotContains(UpdateEventTool::class, $waClasses);
        $this->assertNotContains(CreateHolidayTool::class, $waClasses);

        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(ListNoticesTool::class));
        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(ListEventsTool::class));
        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(ListHolidaysTool::class));

        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(CreateNoticeTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(UpdateNoticeTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(CreateEventTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(UpdateEventTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(CreateHolidayTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(UpdateHolidayTool::class));
    }

    public function test_role_capabilities_include_school_comms_for_admin_and_deputy(): void
    {
        $admin = ToshiActionService::getRoleCapabilities(3);
        $deputy = ToshiActionService::getRoleCapabilities(4);

        foreach (['list_notices', 'create_notice', 'update_notice', 'list_events', 'create_event', 'update_event', 'list_holidays', 'create_holiday', 'update_holiday'] as $action) {
            $this->assertContains($action, $admin['actions']);
            $this->assertContains($action, $deputy['actions']);
        }

        $this->assertNotContains('settings', $deputy['actions']);
        $this->assertNotContains('add_coadmin', $deputy['actions']);
    }

    public function test_school_comms_routing_is_custom_tool_not_sdk_sub_agent(): void
    {
        $this->assertFalse(
            (new SchoolCommsSkill) instanceof CanActAsTool,
            'SchoolCommsSkill must not implement CanActAsTool — Orchestrator uses RouteTo* wrappers'
        );
        $this->assertNotContains(CanActAsTool::class, class_implements(SchoolCommsSkill::class) ?: []);

        $academicSource = file_get_contents(app_path('AiAgents/Tools/RouteToAcademicSkillTool.php'));
        $commsSource = file_get_contents(app_path('AiAgents/Tools/RouteToSchoolCommsSkillTool.php'));
        $this->assertStringContainsString('new AcademicSkill', $academicSource);
        $this->assertStringContainsString('->prompt(', $academicSource);
        $this->assertStringContainsString('new SchoolCommsSkill', $commsSource);
        $this->assertStringContainsString('->prompt(', $commsSource);
        $this->assertStringContainsString('NOT Laravel AI Sub-Agents / CanActAsTool', $commsSource);
    }

    public function test_create_notice_leaf_sets_tier2_side_channel_not_generic_router(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        // Skill path leaf (no LLM): same CreateNoticeTool SchoolCommsSkill exposes.
        $raw = (new CreateNoticeTool)->handle(new Request([
            'title' => 'Audit Side-Channel Notice',
            'description' => 'Must identify toolCreateNotice',
            'type' => 'school',
            'publish_date' => '2026-08-01',
            'expire_date' => '2026-08-31',
        ]));

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['__tier2_confirm'] ?? false);
        $this->assertSame('toolCreateNotice', $decoded['tool']);
        $this->assertNotSame('RouteToSchoolCommsSkillTool', $decoded['tool']);
        $this->assertNotSame('SchoolCommsSkill', $decoded['tool']);

        $this->assertNotNull(ToshiActionService::$pendingConfirmPayload);
        $this->assertSame('toolCreateNotice', ToshiActionService::$pendingConfirmPayload['tool']);
        $this->assertSame('Audit Side-Channel Notice', ToshiActionService::$pendingConfirmPayload['args']['title']);
    }

    public function test_create_notice_confirm_yes_audits_leaf_tool_and_acting_user(): void
    {
        $this->actingAs($this->schoolAdmin);

        Livewire::test(AgentToshi::class)
            ->set('schoolId', $this->schoolId)
            ->set('pendingToolConfirm', [
                'tool' => 'toolCreateNotice',
                'args' => [
                    'title' => 'Audited PTA Notice',
                    'description' => 'Parents invited',
                    'type' => 'school',
                    'publish_date' => '2026-08-15',
                    'expire_date' => '2026-09-15',
                ],
            ])
            ->call('confirmYes');

        $this->assertDatabaseHas('notice_board', [
            'school_id' => $this->schoolId,
            'title' => 'Audited PTA Notice',
        ]);

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after Tier-2 confirmYes create-notice');
        $this->assertSame('toolCreateNotice', $log->properties['tool']);
        $this->assertSame($this->schoolAdmin->id, $log->properties['acting_user_id']);
        $this->assertSame($this->schoolAdmin->id, $log->properties['approver_id']);
        $this->assertSame($this->schoolAdmin->id, $log->causer_id);
        $this->assertSame('success', $log->properties['status']);
        $this->assertSame('Audited PTA Notice', $log->properties['arguments']['title'] ?? null);
        $this->assertStringNotContainsString('RouteToSchoolComms', (string) $log->properties['tool']);
        $this->assertStringNotContainsString('SchoolCommsSkill', (string) $log->description);
    }

    public function test_tool_invoked_under_school_comms_skill_audits_leaf_create_notice(): void
    {
        $this->actingAs($this->schoolAdmin);

        $tool = new CreateNoticeTool;
        $confirmJson = json_encode([
            '__tier2_confirm' => true,
            'tool' => 'toolCreateNotice',
            'args' => ['title' => 'Invoked Notice', 'description' => 'x'],
            'preview' => 'Create notice: Invoked Notice',
        ]);

        // Nested skill prompt path: ToolInvoked agent is SchoolCommsSkill, tool is leaf.
        (new LogToolInvoked)->handle(new ToolInvoked(
            'inv-school-comms-1',
            'tool-inv-create-notice-1',
            new SchoolCommsSkill,
            $tool,
            [
                'title' => 'Invoked Notice',
                'description' => 'x',
                'type' => 'school',
            ],
            $confirmJson,
        ));

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('CreateNoticeTool', $log->properties['tool']);
        $this->assertSame($this->schoolAdmin->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['approver_id']);
        $this->assertNotSame('RouteToSchoolCommsSkillTool', $log->properties['tool']);
    }

    public function test_bypass_create_notice_then_explicit_audit_matches_deputy_bar(): void
    {
        $this->actingAs($this->schoolAdmin);
        ToshiActionService::$bypassConfirm = true;

        $ok = (new CreateNoticeTool)->handle(new Request([
            'title' => 'Bypass then Audit',
            'description' => 'Direct leaf write',
            'type' => 'school',
            'publish_date' => '2026-07-01',
            'expire_date' => '2026-12-31',
        ]));
        ToshiActionService::$bypassConfirm = false;
        $this->assertStringStartsWith('✅', $ok);

        // Direct handle()+bypassConfirm does not auto-audit (same as other roles);
        // production write audit is executeConfirmedTool / LogToolInvoked / bridge.
        $this->assertFalse(
            ActivityLog::where('log_name', 'toshi')
                ->where('school_id', $this->schoolId)
                ->where('properties->tool', 'toolCreateNotice')
                ->exists()
        );

        $log = ToshiAuditService::logExecution(
            user: $this->schoolAdmin,
            school: $this->schoolAdmin->school,
            toolName: 'toolCreateNotice',
            arguments: ['title' => 'Bypass then Audit'],
            result: $ok,
            actingUser: $this->schoolAdmin,
            approver: null,
        );

        $this->assertSame('toolCreateNotice', $log->properties['tool']);
        $this->assertSame($this->schoolAdmin->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['approver_id']);
    }
}
