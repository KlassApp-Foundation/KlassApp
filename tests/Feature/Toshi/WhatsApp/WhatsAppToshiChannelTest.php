<?php

namespace Tests\Feature\Toshi\WhatsApp;

use App\Ai\Tools\Superadmin\ImpersonateSchoolAdminTool;
use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\Accountant\RecordPaymentTool;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\Parent\AttendanceTool;
use App\AiAgents\Tools\Parent\FeeBalanceTool;
use App\AiAgents\Tools\Parent\GradesTool;
use App\AiAgents\Tools\Parent\HealthTool;
use App\AiAgents\Tools\Parent\ListChildrenTool;
use App\AiAgents\Tools\Student\SubmitAssignmentTool;
use App\AiAgents\Tools\Teacher\MarkAttendanceTool;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\Toshi\ParentActionService;
use App\Services\ToshiActionService;
use App\Services\WhatsApp\WhatsAppToshiChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

class WhatsAppToshiChannelTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private User $parent;

    private User $childA;

    private User $childB;

    private User $otherStudent;

    private User $teacher;

    private User $accountant;

    private User $deputy;

    private WhatsAppUser $parentWa;

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
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'WA Toshi School',
            'slug' => 'wa-toshi',
            'email' => 'wa-toshi@test.sch.ug',
            'phone' => '+256700000077',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->parent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 7,
            'email' => 'parent.wa@test.sch.ug',
            'name' => 'WA Parent',
        ]);

        $this->childA = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'child.a@test.sch.ug',
            'name' => 'Alice Child',
        ]);

        $this->childB = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'child.b@test.sch.ug',
            'name' => 'Bob Child',
        ]);

        $this->otherStudent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'other.student@test.sch.ug',
            'name' => 'Other Student',
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.wa@test.sch.ug',
            'name' => 'WA Teacher',
        ]);

        $this->accountant = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 11,
            'email' => 'accountant.wa@test.sch.ug',
            'name' => 'WA Accountant',
        ]);

        $this->deputy = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 4,
            'email' => 'deputy.wa@test.sch.ug',
            'name' => 'WA Deputy',
        ]);

        StudentParentLink::create([
            'school_id' => $this->schoolId,
            'parent_id' => $this->parent->id,
            'student_id' => $this->childA->id,
            'status' => 1,
        ]);

        StudentParentLink::create([
            'school_id' => $this->schoolId,
            'parent_id' => $this->parent->id,
            'student_id' => $this->childB->id,
            'status' => 1,
        ]);

        $this->parentWa = WhatsAppUser::create([
            'phone' => '+256701111177',
            'user_id' => $this->parent->id,
            'school_id' => $this->schoolId,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        config([
            'toshi.sdk_v2_enabled' => true,
            'toshi.whatsapp_channel_enabled' => true,
            'toshi.per_school_gate' => true,
            'ai.providers.openai-compatible.key' => 'test-key',
        ]);
    }

    public function test_parent_agent_tools_are_read_only_and_exclude_admin_writes(): void
    {
        $agent = new ParentOperationsAgent;
        $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();

        $this->assertContains(ListChildrenTool::class, $classes);
        $this->assertContains(FeeBalanceTool::class, $classes);
        $this->assertContains(GradesTool::class, $classes);
        $this->assertContains(AttendanceTool::class, $classes);
        $this->assertContains(HealthTool::class, $classes);
        $this->assertNotContains(AddCoAdminTool::class, $classes);
        $this->assertNotContains(ManagePayrollTool::class, $classes);
        $this->assertNotContains(ImpersonateSchoolAdminTool::class, $classes);
        $this->assertCount(6, $classes);
    }

    public function test_parent_capabilities_restored_to_children_scope(): void
    {
        $caps = ToshiActionService::getRoleCapabilities(7);

        $this->assertSame('children', $caps['scope']);
        $this->assertContains('view_fee_balance', $caps['actions']);
        $this->assertContains('list_children', $caps['actions']);
    }

    public function test_toshi_parent_gate_allows_parent_denies_teacher(): void
    {
        $this->actingAs($this->parent);
        $this->assertTrue(Gate::inspect('toshi-parent-action', $this->parent)->allowed());

        $this->actingAs($this->teacher);
        $this->assertTrue(Gate::inspect('toshi-parent-action', $this->teacher)->denied());
        $this->assertTrue(Gate::inspect('toshi-school-action', $this->parent)->denied());
    }

    public function test_parent_children_scope_lists_only_linked_students(): void
    {
        $this->actingAs($this->parent);

        $result = ParentActionService::listChildren($this->parent);
        $this->assertTrue($result['success']);
        $this->assertEqualsCanonicalizing(
            [$this->childA->id, $this->childB->id],
            $result['data']['ids'],
        );
        $this->assertNotContains($this->otherStudent->id, $result['data']['ids']);
    }

    public function test_parent_rejects_unlinked_student_id(): void
    {
        $this->actingAs($this->parent);

        $resolved = ParentActionService::resolveChild(
            $this->parent,
            null,
            $this->otherStudent->id,
        );

        $this->assertFalse($resolved['ok']);
        $this->assertStringContainsString('not linked', $resolved['message']);
    }

    public function test_parent_fee_tool_rejects_foreign_child_name(): void
    {
        $this->actingAs($this->parent);

        $result = (new FeeBalanceTool)->handle(new Request([
            'child_name' => 'Other Student',
        ]));

        $this->assertStringContainsString('No linked child matched', $result);
    }

    public function test_parent_fee_tool_resolves_linked_child_by_name(): void
    {
        $this->actingAs($this->parent);

        $result = (new FeeBalanceTool)->handle(new Request([
            'child_name' => 'Alice',
        ]));

        $this->assertStringContainsString('Alice Child', $result);
        $this->assertStringContainsString('Fee Balance', $result);
    }

    public function test_write_exclusion_denies_payroll_impersonation_and_non_allowlisted_confirms(): void
    {
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(ManagePayrollTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(ImpersonateSchoolAdminTool::class));
        // Fail-closed: non-allowlisted ConfirmsBeforeWrite still excluded after allowlist lands.
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(MarkAttendanceTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(RecordPaymentTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(SubmitAssignmentTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(AddCoAdminTool::class));
        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(ListChildrenTool::class));
        $this->assertTrue(WhatsAppWriteExclusion::allowsClass(FeeBalanceTool::class));
    }

    public function test_whatsapp_channel_does_not_expose_write_tools_for_teacher(): void
    {
        $channel = app(WhatsAppToshiChannelService::class);
        $exposed = $channel->exposedToolClasses($this->teacher);

        $this->assertNotContains(MarkAttendanceTool::class, $exposed);
        $this->assertNotContains(AddCoAdminTool::class, $exposed);
        $this->assertFalse($channel->canInvokeTool($this->teacher, MarkAttendanceTool::class));
        $this->assertFalse($channel->canInvokeTool($this->teacher, ManagePayrollTool::class));
    }

    public function test_whatsapp_channel_does_not_expose_payroll_for_accountant(): void
    {
        $channel = app(WhatsAppToshiChannelService::class);

        $this->assertFalse($channel->canInvokeTool($this->accountant, ManagePayrollTool::class));
        $this->assertFalse($channel->canInvokeTool($this->accountant, RecordPaymentTool::class));
        $this->assertNotContains(ManagePayrollTool::class, $channel->exposedToolClasses($this->accountant));
    }

    public function test_whatsapp_channel_maps_roles_including_parent(): void
    {
        $channel = app(WhatsAppToshiChannelService::class);

        $this->assertSame(ParentOperationsAgent::class, $channel->resolveAgentClass(7));
        $this->assertSame(SchoolAdminWhatsAppReadAgent::class, $channel->resolveAgentClass(3));
        $this->assertNull($channel->resolveAgentClass(4));
        $this->assertFalse($channel->isAvailableFor($this->deputy));
        $this->assertTrue($channel->isAvailableFor($this->parent));
    }

    public function test_pending_approval_hook_delegates_to_confirmation_bridge(): void
    {
        $channel = app(WhatsAppToshiChannelService::class);

        // Non-coded free-form must not be consumed (bridge returns false).
        $this->assertFalse($channel->tryHandlePendingApproval(
            $this->parentWa,
            $this->parentWa->phone,
            'what are my fees?',
        ));

        // Coded YES for a missing token is consumed (fail-closed expiry path).
        $wa = Mockery::mock(\App\Services\WhatsAppBusinessService::class);
        $wa->shouldReceive('sendText')->once()->andReturn(['success' => true, 'message_id' => 'x']);
        $this->app->instance(\App\Services\WhatsAppBusinessService::class, $wa);

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertTrue($channel->tryHandlePendingApproval(
            $this->parentWa,
            $this->parentWa->phone,
            'YES a7f3k2xy',
        ));
    }

    public function test_ask_binds_acting_user_for_parent_tools(): void
    {
        $this->actingAs($this->parent);

        $toolResult = (new ListChildrenTool)->handle(new Request([]));
        $this->assertStringContainsString('Alice Child', $toolResult);
        $this->assertStringContainsString('Bob Child', $toolResult);

        // Teacher must not pass parent authorize
        $this->actingAs($this->teacher);
        $denied = (new ListChildrenTool)->handle(new Request([]));
        $this->assertStringStartsWith('❌', $denied);
    }

    public function test_ug4_has_no_whatsapp_toshi_agent(): void
    {
        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertNull($channel->makeAgent($this->deputy));
        $this->assertSame([], $channel->exposedToolClasses($this->deputy));
    }
}
