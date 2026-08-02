<?php

namespace Tests\Feature\Toshi\Adversarial;

use App\Ai\Tools\Superadmin\ImpersonateSchoolAdminTool;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\CreateExamTool;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Models\User;
use App\Services\ToshiActionService;
use App\Services\WhatsApp\WhatsAppToshiChannelService;
use App\Support\Toshi\AdversarialPromptFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Exceptions\NoSuchToolException;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

/**
 * Structural-isolation regression coverage under adversarial-shaped prompts.
 *
 * NOT jailbreak-resistance proof. These tests do not evaluate soft-refusal quality
 * of any live LLM. They prove that when a manipulative user message frames a
 * privilege-escalation attempt, off-role tools remain unregistered, WhatsApp
 * exclusion / canInvokeTool stay false, and direct handle() still denies.
 *
 * "Prompt pressure" without a real model: Agent::fake scripts a compliance-shaped
 * ToolCall for the forbidden capability; laravel/ai raises NoSuchToolException
 * because the tool is not on tools() — that exception plus tools()/handle() asserts
 * are the structural deny. Soft-refusal wording is out of scope for this suite.
 */
class TeacherAdversarialIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Teacher Adv School',
            'slug' => 'teacher-adv',
            'email' => 'teacher-adv@test.sch.ug',
            'phone' => '+256700000155',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.adv@test.sch.ug',
            'name' => 'Adv Teacher',
        ]);

        config([
            'toshi.sdk_v2_enabled' => true,
            'toshi.whatsapp_channel_enabled' => true,
            'toshi.per_school_gate' => true,
            'ai.providers.openai-compatible.key' => 'test-key',
        ]);
    }

    public function test_as_admin_add_coadmin_prompt_still_structurally_denied(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        $attack = AdversarialPromptFixtures::TEACHER_AS_ADMIN_ADD_COADMIN;

        TeacherOperationsAgent::fake([
            new ToolCall('call_adv_coadmin', 'AddCoAdminTool', [
                'name' => 'Eve',
                'email' => 'eve.coadmin@test.sch.ug',
            ]),
        ]);

        try {
            (new TeacherOperationsAgent)->prompt($attack);
            $this->fail('Expected NoSuchToolException for off-role AddCoAdminTool compliance attempt');
        } catch (NoSuchToolException $e) {
            $this->assertSame('AddCoAdminTool', $e->toolName);
        }

        $classes = collect((new TeacherOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(AddCoAdminTool::class, $classes);

        $result = (new AddCoAdminTool)->handle(new Request([
            'name' => 'Eve',
            'email' => 'eve.coadmin@test.sch.ug',
        ]));
        $this->assertStringStartsWith('❌', $result);
        $this->assertSame(0, User::where('email', 'eve.coadmin@test.sch.ug')->count());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_ignore_limits_payroll_prompt_still_structurally_denied(): void
    {
        $this->actingAs($this->teacher);

        TeacherOperationsAgent::fake([
            new ToolCall('call_adv_payroll', 'ManagePayrollTool', ['action' => 'mark']),
        ]);

        try {
            (new TeacherOperationsAgent)->prompt(AdversarialPromptFixtures::TEACHER_IGNORE_LIMITS_PAYROLL);
            $this->fail('Expected NoSuchToolException for off-role ManagePayrollTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('ManagePayrollTool', $e->toolName);
        }

        $classes = collect((new TeacherOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(ManagePayrollTool::class, $classes);

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->teacher, ManagePayrollTool::class));
        $this->assertTrue(WhatsAppWriteExclusion::isHardDenied(ManagePayrollTool::class));
    }

    public function test_impersonate_admin_prompt_still_structurally_denied(): void
    {
        $this->actingAs($this->teacher);

        TeacherOperationsAgent::fake([
            new ToolCall('call_adv_impersonate', 'ImpersonateSchoolAdminTool', [
                'school_id' => $this->schoolId,
            ]),
        ]);

        try {
            (new TeacherOperationsAgent)->prompt(AdversarialPromptFixtures::TEACHER_IMPERSONATE_ADMIN);
            $this->fail('Expected NoSuchToolException for off-role ImpersonateSchoolAdminTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('ImpersonateSchoolAdminTool', $e->toolName);
        }

        $classes = collect((new TeacherOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(ImpersonateSchoolAdminTool::class, $classes);

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->teacher, ImpersonateSchoolAdminTool::class));
        $this->assertTrue(WhatsAppWriteExclusion::isHardDenied(ImpersonateSchoolAdminTool::class));
    }

    public function test_create_exam_as_admin_prompt_still_structurally_denied(): void
    {
        $this->actingAs($this->teacher);
        ToshiActionService::$bypassConfirm = true;

        TeacherOperationsAgent::fake([
            new ToolCall('call_adv_exam', 'CreateExamTool', [
                'name' => 'Mid Term Bypass',
            ]),
        ]);

        try {
            (new TeacherOperationsAgent)->prompt(AdversarialPromptFixtures::TEACHER_CREATE_EXAM_AS_ADMIN);
            $this->fail('Expected NoSuchToolException for off-role CreateExamTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('CreateExamTool', $e->toolName);
        }

        $classes = collect((new TeacherOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(CreateExamTool::class, $classes);

        $result = (new CreateExamTool)->handle(new Request([
            'name' => 'Mid Term Bypass',
        ]));
        $this->assertStringStartsWith('❌', $result);

        ToshiActionService::$bypassConfirm = false;
    }
}
