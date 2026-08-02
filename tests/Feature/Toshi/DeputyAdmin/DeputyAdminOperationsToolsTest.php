<?php

namespace Tests\Feature\Toshi\DeputyAdmin;

use App\AiAgents\DeputyAdminOperationsAgent;
use App\AiAgents\Skills\TeacherSkill;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\AddStudentTool;
use App\AiAgents\Tools\ListTeachersTool;
use App\AiAgents\Tools\SetCurriculumTool;
use App\Livewire\AgentToshi;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\TestCase;

class DeputyAdminOperationsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $deputy;

    private User $schoolAdmin;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Deputy Toshi School',
            'slug' => 'deputy-toshi',
            'email' => 'deputy-toshi@test.sch.ug',
            'phone' => '+256700000044',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
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
            'email' => 'admin.deputy@test.sch.ug',
            'name' => 'School Admin',
        ]);

        $this->deputy = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 4,
            'email' => 'deputy.toshi@test.sch.ug',
            'name' => 'Deputy Admin',
        ]);

        User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.deputy@test.sch.ug',
            'name' => 'Listed Teacher',
        ]);
    }

    public function test_deputy_agent_tools_exclude_owner_level_tools(): void
    {
        $agent = new DeputyAdminOperationsAgent;
        $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();

        $this->assertNotContains(AddCoAdminTool::class, $classes);
        $this->assertNotContains(SetCurriculumTool::class, $classes);
        $this->assertContains(AddStudentTool::class, $classes);
        $this->assertContains(ListTeachersTool::class, $classes);
        $this->assertCount(22, $classes);
    }

    public function test_ug3_teacher_skill_still_includes_add_coadmin(): void
    {
        $classes = collect((new TeacherSkill)->tools())->map(fn ($t) => $t::class)->all();

        $this->assertContains(AddCoAdminTool::class, $classes);
    }

    public function test_ug3_agent_toshi_map_still_registers_set_curriculum(): void
    {
        $toolMap = (new \ReflectionClass(AgentToshi::class))->getConstant('TOOL_CLASS_MAP');

        $this->assertSame(SetCurriculumTool::class, $toolMap['toolSetCurriculum'] ?? null);
        $this->assertSame(AddCoAdminTool::class, $toolMap['toolAddCoAdmin'] ?? null);
    }

    public function test_gates_isolate_deputy_from_other_role_gates(): void
    {
        $this->actingAs($this->deputy);

        $this->assertTrue(Gate::inspect('toshi-deputy-action', $this->deputy)->allowed());
        $this->assertTrue(Gate::inspect('toshi-school-action', $this->deputy)->denied());
        $this->assertTrue(Gate::inspect('toshi-teacher-action', $this->deputy)->denied());
        $this->assertTrue(Gate::inspect('toshi-accountant-action', $this->deputy)->denied());
        $this->assertTrue(Gate::inspect('toshi-librarian-action', $this->deputy)->denied());
        $this->assertTrue(Gate::inspect('toshi-receptionist-action', $this->deputy)->denied());
        $this->assertTrue(Gate::inspect('toshi-student-action', $this->deputy)->denied());
    }

    public function test_school_admin_still_passes_toshi_school_action_only(): void
    {
        $this->actingAs($this->schoolAdmin);

        $this->assertTrue(Gate::inspect('toshi-school-action', $this->schoolAdmin)->allowed());
        $this->assertTrue(Gate::inspect('toshi-deputy-action', $this->schoolAdmin)->denied());
    }

    public function test_owner_tools_deny_deputy_even_when_invoked_directly(): void
    {
        $this->actingAs($this->deputy);
        ToshiActionService::$bypassConfirm = true;

        $coAdmin = (new AddCoAdminTool)->handle(new Request([
            'name' => 'Should Fail',
            'email' => 'coadmin.deputy.fail@test.sch.ug',
        ]));
        $this->assertStringStartsWith('❌', $coAdmin);

        $curriculum = (new SetCurriculumTool)->handle(new Request([
            'curriculum' => 'uneb',
        ]));
        $this->assertStringStartsWith('❌', $curriculum);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_shared_school_tool_allows_deputy_via_dual_allow(): void
    {
        $this->actingAs($this->deputy);

        $result = (new ListTeachersTool)->handle(new Request([]));

        $this->assertStringNotContainsString('not authorized', strtolower($result));
        $this->assertStringContainsString('Teacher', $result);
    }

    public function test_get_role_capabilities_omits_add_coadmin_and_settings(): void
    {
        $caps = ToshiActionService::getRoleCapabilities(4);

        $this->assertSame('school', $caps['scope']);
        $this->assertSame('deputy school administrator', $caps['label']);
        $this->assertContains('add_student', $caps['actions']);
        $this->assertContains('generate_report', $caps['actions']);
        $this->assertNotContains('add_coadmin', $caps['actions']);
        $this->assertNotContains('settings', $caps['actions']);
        $this->assertNotContains('set_curriculum', $caps['actions']);
    }

    public function test_scope_router_selects_deputy_agent_for_ug4(): void
    {
        $service = app(\App\AiAgents\ToshiSdkV2Service::class);
        $reflection = new \ReflectionClass($service);

        // ask() is hard to drive without AI; assert match arm via source contract on router.
        $source = file_get_contents(base_path('app/AiAgents/ToshiSdkV2Service.php'));
        $this->assertStringContainsString('4 => new DeputyAdminOperationsAgent', $source);

        unset($reflection);
    }

    public function test_unconfirmed_or_read_only_audit_leaves_approver_id_null(): void
    {
        $this->actingAs($this->deputy);

        $log = ToshiAuditService::logExecution(
            user: $this->deputy,
            school: $this->deputy->school,
            toolName: 'ListTeachersTool',
            arguments: [],
            result: '✅ ok',
            actingUser: $this->deputy,
            approver: null,
        );

        $this->assertSame($this->deputy->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['approver_id']);
        $this->assertSame($this->deputy->id, $log->causer_id);
        $this->assertTrue(ActivityLog::where('log_name', 'toshi')->where('causer_id', $this->deputy->id)->exists());
    }

    public function test_tier2_confirm_yes_sets_approver_id_to_confirming_deputy(): void
    {
        $this->actingAs($this->deputy);

        Livewire::test(AgentToshi::class)
            ->set('schoolId', $this->schoolId)
            ->set('pendingToolConfirm', [
                'tool' => 'toolAddTeacher',
                'args' => [
                    'name' => 'Audit Teacher',
                    'email' => 'audit.teacher.deputy@test.sch.ug',
                ],
            ])
            ->call('confirmYes');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after Tier-2 confirmYes');
        $this->assertSame('toolAddTeacher', $log->properties['tool']);
        $this->assertSame($this->deputy->id, $log->properties['acting_user_id']);
        $this->assertSame($this->deputy->id, $log->properties['approver_id']);
    }
}
