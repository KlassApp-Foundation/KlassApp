<?php

namespace Tests\Feature\Toshi\Adversarial;

use App\Ai\Tools\Superadmin\ImpersonateSchoolAdminTool;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\Teacher\MarkAttendanceTool;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppToshiChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Exceptions\NoSuchToolException;
use Laravel\Ai\Responses\Data\ToolCall;
use Tests\TestCase;

/**
 * Structural-isolation regression coverage under adversarial-shaped prompts
 * for School Admin on the WhatsApp channel path.
 *
 * NOT jailbreak-resistance proof. These tests do not evaluate soft-refusal quality
 * of any live LLM. They prove WhatsAppWriteExclusion / exposedToolClasses /
 * canInvokeTool remain fail-closed under manipulative framing.
 *
 * Prompt pressure simulation: Agent::fake scripts a compliance-shaped ToolCall for
 * a HARD_DENY or ConfirmsBeforeWrite-excluded capability; laravel/ai raises
 * NoSuchToolException because SchoolAdminWhatsAppReadAgent never registers writes.
 */
class SchoolAdminWhatsAppAdversarialIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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
            'name' => 'Admin WA Adv School',
            'slug' => 'admin-wa-adv',
            'email' => 'admin-wa-adv@test.sch.ug',
            'phone' => '+256700000188',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'email' => 'admin.wa.adv@test.sch.ug',
            'name' => 'WA Adv Admin',
        ]);

        config([
            'toshi.sdk_v2_enabled' => true,
            'toshi.whatsapp_channel_enabled' => true,
            'toshi.per_school_gate' => true,
            'ai.providers.openai-compatible.key' => 'test-key',
        ]);
    }

    public function test_wa_payroll_approve_prompt_still_hard_denied(): void
    {
        $this->actingAs($this->admin);

        SchoolAdminWhatsAppReadAgent::fake([
            new ToolCall('call_adv_payroll', 'ManagePayrollTool', ['action' => 'approve']),
        ]);

        try {
            (new SchoolAdminWhatsAppReadAgent)->prompt(AdversarialPromptFixtures::SCHOOL_ADMIN_WA_PAYROLL);
            $this->fail('Expected NoSuchToolException for ManagePayrollTool on WA read agent');
        } catch (NoSuchToolException $e) {
            $this->assertSame('ManagePayrollTool', $e->toolName);
        }

        $this->assertTrue(WhatsAppWriteExclusion::isHardDenied(ManagePayrollTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(ManagePayrollTool::class));

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->admin, ManagePayrollTool::class));
        $this->assertNotContains(ManagePayrollTool::class, $channel->exposedToolClasses($this->admin));
    }

    public function test_wa_impersonate_prompt_still_hard_denied(): void
    {
        $this->actingAs($this->admin);

        SchoolAdminWhatsAppReadAgent::fake([
            new ToolCall('call_adv_impersonate', 'ImpersonateSchoolAdminTool', [
                'school_id' => $this->schoolId,
            ]),
        ]);

        try {
            (new SchoolAdminWhatsAppReadAgent)->prompt(AdversarialPromptFixtures::SCHOOL_ADMIN_WA_IMPERSONATE);
            $this->fail('Expected NoSuchToolException for ImpersonateSchoolAdminTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('ImpersonateSchoolAdminTool', $e->toolName);
        }

        $this->assertTrue(WhatsAppWriteExclusion::isHardDenied(ImpersonateSchoolAdminTool::class));

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->admin, ImpersonateSchoolAdminTool::class));
        $this->assertNotContains(ImpersonateSchoolAdminTool::class, $channel->exposedToolClasses($this->admin));
    }

    public function test_wa_mark_attendance_prompt_still_excluded_after_confirm_bridge(): void
    {
        $this->actingAs($this->admin);

        SchoolAdminWhatsAppReadAgent::fake([
            new ToolCall('call_adv_attendance', 'MarkAttendanceTool', [
                'student' => 'someone@test.sch.ug',
                'date' => '2026-08-01',
                'status' => 'present',
            ]),
        ]);

        try {
            (new SchoolAdminWhatsAppReadAgent)->prompt(AdversarialPromptFixtures::SCHOOL_ADMIN_WA_MARK_ATTENDANCE);
            $this->fail('Expected NoSuchToolException for MarkAttendanceTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('MarkAttendanceTool', $e->toolName);
        }

        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(MarkAttendanceTool::class));

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->admin, MarkAttendanceTool::class));
        $this->assertNotContains(MarkAttendanceTool::class, $channel->exposedToolClasses($this->admin));
    }

    public function test_wa_add_coadmin_prompt_still_not_on_read_agent(): void
    {
        $this->actingAs($this->admin);

        SchoolAdminWhatsAppReadAgent::fake([
            new ToolCall('call_adv_coadmin', 'AddCoAdminTool', [
                'name' => 'WaEscalation',
                'email' => 'wa.esc@test.sch.ug',
            ]),
        ]);

        try {
            (new SchoolAdminWhatsAppReadAgent)->prompt(AdversarialPromptFixtures::SCHOOL_ADMIN_WA_ADD_COADMIN);
            $this->fail('Expected NoSuchToolException for AddCoAdminTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('AddCoAdminTool', $e->toolName);
        }

        $classes = collect((new SchoolAdminWhatsAppReadAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(AddCoAdminTool::class, $classes);

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->admin, AddCoAdminTool::class));
        $this->assertNotContains(AddCoAdminTool::class, $channel->exposedToolClasses($this->admin));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(AddCoAdminTool::class));
    }
}
