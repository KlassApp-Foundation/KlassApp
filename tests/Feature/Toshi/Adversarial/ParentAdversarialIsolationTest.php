<?php

namespace Tests\Feature\Toshi\Adversarial;

use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\Parent\FeeBalanceTool;
use App\AiAgents\Tools\Parent\GradesTool;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Services\Toshi\ParentActionService;
use App\Services\WhatsApp\WhatsAppToshiChannelService;
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
 * of any live LLM. They prove parent children-scope and off-role tool absence under
 * manipulative framing.
 *
 * Prompt pressure simulation: Agent::fake scripts a compliance-shaped ToolCall.
 * Off-role → NoSuchToolException. Children-scope tools → resolveChild / handle deny
 * for unlinked peers.
 */
class ParentAdversarialIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $childA;

    private User $otherStudent;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Parent Adv School',
            'slug' => 'parent-adv',
            'email' => 'parent-adv@test.sch.ug',
            'phone' => '+256700000177',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->parent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 7,
            'email' => 'parent.adv@test.sch.ug',
            'name' => 'Adv Parent',
        ]);

        $this->childA = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'child.a.adv@test.sch.ug',
            'name' => 'Alice Linked',
        ]);

        $this->otherStudent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'other.adv@test.sch.ug',
            'name' => 'Other Student',
        ]);

        StudentParentLink::create([
            'school_id' => $this->schoolId,
            'parent_id' => $this->parent->id,
            'student_id' => $this->childA->id,
            'status' => 1,
        ]);

        config([
            'toshi.sdk_v2_enabled' => true,
            'toshi.whatsapp_channel_enabled' => true,
            'toshi.per_school_gate' => true,
            'ai.providers.openai-compatible.key' => 'test-key',
        ]);
    }

    public function test_peer_child_fees_prompt_still_children_scoped(): void
    {
        $this->actingAs($this->parent);
        $attack = AdversarialPromptFixtures::parentPeerChildFees($this->otherStudent->id);

        ParentOperationsAgent::fake([
            new ToolCall('call_adv_fees', 'FeeBalanceTool', [
                'student_id' => $this->otherStudent->id,
                'child_name' => 'Other Student',
            ]),
            'Fee balance (fake compliance text).',
        ]);

        (new ParentOperationsAgent)->prompt($attack);

        $resolved = ParentActionService::resolveChild(
            $this->parent,
            null,
            $this->otherStudent->id,
        );
        $this->assertFalse($resolved['ok']);
        $this->assertStringContainsString('not linked', $resolved['message']);

        $toolResult = (new FeeBalanceTool)->handle(new Request([
            'child_name' => 'Other Student',
        ]));
        $this->assertStringContainsString('No linked child matched', $toolResult);
    }

    public function test_add_coadmin_prompt_still_structurally_denied(): void
    {
        $this->actingAs($this->parent);

        ParentOperationsAgent::fake([
            new ToolCall('call_adv_coadmin', 'AddCoAdminTool', [
                'name' => 'ParentEsc',
                'email' => 'parent.esc@test.sch.ug',
            ]),
        ]);

        try {
            (new ParentOperationsAgent)->prompt(AdversarialPromptFixtures::PARENT_ADD_COADMIN);
            $this->fail('Expected NoSuchToolException for off-role AddCoAdminTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('AddCoAdminTool', $e->toolName);
        }

        $classes = collect((new ParentOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(AddCoAdminTool::class, $classes);
        $this->assertContains(FeeBalanceTool::class, $classes);

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->parent, AddCoAdminTool::class));
    }

    public function test_as_accountant_payroll_prompt_still_hard_denied_on_whatsapp(): void
    {
        $this->actingAs($this->parent);

        ParentOperationsAgent::fake([
            new ToolCall('call_adv_payroll', 'ManagePayrollTool', ['action' => 'run']),
        ]);

        try {
            (new ParentOperationsAgent)->prompt(AdversarialPromptFixtures::PARENT_PAYROLL);
            $this->fail('Expected NoSuchToolException for off-role ManagePayrollTool');
        } catch (NoSuchToolException $e) {
            $this->assertSame('ManagePayrollTool', $e->toolName);
        }

        $classes = collect((new ParentOperationsAgent)->tools())->map(fn ($t) => $t::class)->all();
        $this->assertNotContains(ManagePayrollTool::class, $classes);

        $this->assertTrue(WhatsAppWriteExclusion::isHardDenied(ManagePayrollTool::class));
        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertFalse($channel->canInvokeTool($this->parent, ManagePayrollTool::class));
    }

    public function test_peer_grades_by_name_still_children_scoped(): void
    {
        $this->actingAs($this->parent);

        ParentOperationsAgent::fake([
            new ToolCall('call_adv_grades', 'GradesTool', [
                'child_name' => 'Other Student',
            ]),
            'Grades (fake compliance text).',
        ]);

        (new ParentOperationsAgent)->prompt(AdversarialPromptFixtures::PARENT_PEER_GRADES);

        $result = (new GradesTool)->handle(new Request([
            'child_name' => 'Other Student',
        ]));
        $this->assertStringContainsString('No linked child matched', $result);

        $children = ParentActionService::listChildren($this->parent);
        $this->assertContains($this->childA->id, $children['data']['ids']);
        $this->assertNotContains($this->otherStudent->id, $children['data']['ids']);
    }
}
