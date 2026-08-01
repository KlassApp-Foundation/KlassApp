<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Tools\Superadmin\CreateCoAdminTool;
use App\Ai\Tools\Superadmin\DeleteCoAdminTool;
use App\Ai\Tools\Superadmin\ResetCoAdminPasswordTool;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use ReflectionProperty;
use Tests\TestCase;

class PlatformCoAdminToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'sitesubadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-coadmin@test.sch.ug',
        ]);
    }

    public function test_create_coadmin_happy_path_db_and_audit(): void
    {
        $this->actingAs($this->siteadmin);

        $args = [
            'name' => 'Co Admin One',
            'email' => 'coadmin.one@test.sch.ug',
            'password' => 'secret12',
        ];

        PlatformOperationsAgent::fake([
            new ToolCall('call_coadmin_create', 'CreateCoAdminTool', $args),
            'Co-admin created.',
        ]);

        $response = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Create a co-admin.');

        $this->assertFalse($response->hasPendingApprovals());
        $this->assertDatabaseHas('users', [
            'email' => 'coadmin.one@test.sch.ug',
            'usergroup_id' => 2,
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'CreateCoAdminTool')
                ->where('properties->status', 'success')
                ->where('properties->acting_user_id', $this->siteadmin->id)
                ->exists()
        );
    }

    public function test_create_coadmin_validation_rejection(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(CreateCoAdminTool::class)->handle(new Request([
            'name' => 'X',
            'email' => 'not-an-email',
            'password' => 'short',
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertSame(0, User::where('usergroup_id', 2)->count());
    }

    public function test_delete_coadmin_pending_before_approve_no_mutation(): void
    {
        $this->actingAs($this->siteadmin);

        $coAdmin = User::factory()->create([
            'usergroup_id' => 2,
            'school_id' => null,
            'email' => 'coadmin.delete@test.sch.ug',
        ]);

        PlatformOperationsAgent::fake([
            new ToolCall('call_coadmin_delete_pending', 'DeleteCoAdminTool', [
                'id' => $coAdmin->id,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Delete the co-admin.');

        $this->assertTrue($paused->hasPendingApprovals());
        $pending = $paused->pendingApprovals->first();
        $this->assertSame('DeleteCoAdminTool', $pending->tool);
        $this->assertStringContainsString((string) $coAdmin->id, (string) $pending->reason);
        $this->assertNull($coAdmin->fresh()->deleted_at);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'DeleteCoAdminTool')
                ->where('properties->status', 'pending_approval')
                ->where('properties->acting_user_id', $this->siteadmin->id)
                ->whereNull('properties->approver_id')
                ->exists()
        );
    }

    public function test_delete_coadmin_http_approve_soft_deletes(): void
    {
        $this->actingAs($this->siteadmin);

        $coAdmin = User::factory()->create([
            'usergroup_id' => 2,
            'school_id' => null,
            'email' => 'coadmin.http-delete@test.sch.ug',
        ]);

        $paused = $this->pauseDelete($coAdmin->id);
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Co-admin removed.');

        $response = $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => ['action' => 'approve'],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'complete');
        $this->assertSoftDeleted('users', ['id' => $coAdmin->id]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'DeleteCoAdminTool')
                ->where('properties->status', 'approval_resolved')
                ->where('properties->acting_user_id', $this->siteadmin->id)
                ->where('properties->approver_id', $this->siteadmin->id)
                ->exists()
        );
    }

    public function test_delete_coadmin_reject_leaves_user(): void
    {
        $this->actingAs($this->siteadmin);

        $coAdmin = User::factory()->create([
            'usergroup_id' => 2,
            'school_id' => null,
            'email' => 'coadmin.reject-delete@test.sch.ug',
        ]);

        $paused = $this->pauseDelete($coAdmin->id);
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Kept the co-admin.');

        $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => [
                    'action' => 'reject',
                    'result' => 'Keep this co-admin.',
                ],
            ],
        ])->assertOk();

        $this->assertNull($coAdmin->fresh()->deleted_at);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'DeleteCoAdminTool')
                ->where('properties->status', 'approval_rejected')
                ->exists()
        );
    }

    public function test_reset_password_http_approve_and_reject_audit(): void
    {
        $this->actingAs($this->siteadmin);

        $coAdmin = User::factory()->create([
            'usergroup_id' => 2,
            'school_id' => null,
            'email' => 'coadmin.reset@test.sch.ug',
            'password' => Hash::make('old-password'),
        ]);
        $oldHash = $coAdmin->password;

        PlatformOperationsAgent::fake([
            new ToolCall('call_coadmin_reset', 'ResetCoAdminPasswordTool', [
                'id' => $coAdmin->id,
                'password' => 'new-secret-99',
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Reset co-admin password.');

        $this->assertTrue($paused->hasPendingApprovals());
        $this->assertStringContainsString($coAdmin->email, (string) $paused->pendingApprovals->first()->reason);
        $this->assertSame($oldHash, $coAdmin->fresh()->password);

        $approvalId = $paused->pendingApprovals->first()->id;
        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Password reset.');

        $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => ['action' => 'approve'],
            ],
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secret-99', $coAdmin->fresh()->password));
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ResetCoAdminPasswordTool')
                ->where('properties->status', 'approval_resolved')
                ->where('properties->acting_user_id', $this->siteadmin->id)
                ->where('properties->approver_id', $this->siteadmin->id)
                ->exists()
        );
    }

    public function test_reset_password_reject_leaves_hash_unchanged(): void
    {
        $this->actingAs($this->siteadmin);

        $coAdmin = User::factory()->create([
            'usergroup_id' => 2,
            'school_id' => null,
            'email' => 'coadmin.reset-reject@test.sch.ug',
            'password' => Hash::make('keep-me'),
        ]);
        $oldHash = $coAdmin->password;

        PlatformOperationsAgent::fake([
            new ToolCall('call_coadmin_reset_reject', 'ResetCoAdminPasswordTool', [
                'id' => $coAdmin->id,
                'password' => 'should-not-apply',
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Reset password.');

        $approvalId = $paused->pendingApprovals->first()->id;
        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Not resetting.');

        $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => [
                    'action' => 'reject',
                    'result' => 'Do not reset.',
                ],
            ],
        ])->assertOk();

        $this->assertSame($oldHash, $coAdmin->fresh()->password);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ResetCoAdminPasswordTool')
                ->where('properties->status', 'approval_rejected')
                ->exists()
        );
    }

    public function test_delete_non_coadmin_validation_via_handle(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(DeleteCoAdminTool::class)->handle(new Request([
            'id' => $this->siteadmin->id,
        ]));

        $this->assertStringStartsWith('❌', $result);
    }

    public function test_reset_non_coadmin_validation_via_handle(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(ResetCoAdminPasswordTool::class)->handle(new Request([
            'id' => $this->siteadmin->id,
            'password' => 'whatever1',
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertStringContainsString('not a co-admin', $result);
    }

    private function pauseDelete(int $id)
    {
        PlatformOperationsAgent::fake([
            new ToolCall('call_coadmin_delete_'.md5((string) $id), 'DeleteCoAdminTool', [
                'id' => $id,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt("Delete co-admin {$id}.");

        $this->assertTrue($paused->hasPendingApprovals());

        return $paused;
    }

    private function clearAgentFake(string $agentClass): void
    {
        $ai = Ai::getFacadeRoot();
        $prop = new ReflectionProperty($ai, 'fakeAgentGateways');
        $gateways = $prop->getValue($ai);
        unset($gateways[$agentClass]);
        $prop->setValue($ai, $gateways);
    }

    private function fakeOpenAiCompatibleCompletion(string $content): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'choices' => [[
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $content,
                    ],
                    'finish_reason' => 'stop',
                ]],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15,
                ],
            ], 200),
        ]);
    }
}
