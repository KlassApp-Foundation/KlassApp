<?php

namespace Tests\Feature\Toshi\WhatsApp;

use App\AiAgents\ParentOperationsAgent;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\WhatsApp\WhatsAppHumanEscalationService;
use App\Services\WhatsApp\WhatsAppToshiChannelService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * WhatsApp human-escalation MVP (explicit phrase set; ≠ confirmation bridge).
 */
class WhatsAppHumanEscalationTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private User $parent;

    private User $student;

    private User $teacher;

    private User $receptionist;

    private User $admin;

    private WhatsAppUser $parentWa;

    private WhatsAppUser $teacherWa;

    private WhatsAppUser $adminWa;

    private WhatsAppUser $receptionistWa;

    /** @var \Mockery\MockInterface&WhatsAppBusinessService */
    private WhatsAppBusinessService $whatsApp;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'WA Escalation School',
            'slug' => 'wa-escalation',
            'email' => 'wa-esc@test.sch.ug',
            'phone' => '+256700000199',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('academic_years')->insert([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'email' => 'admin.esc@test.sch.ug',
            'name' => 'Esc Admin',
        ]);

        $this->receptionist = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 10,
            'email' => 'reception.esc@test.sch.ug',
            'name' => 'Esc Receptionist',
        ]);

        $this->parent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 7,
            'email' => 'parent.esc@test.sch.ug',
            'name' => 'Esc Parent',
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.esc@test.sch.ug',
            'name' => 'Esc Student',
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.esc@test.sch.ug',
            'name' => 'Esc Teacher',
        ]);

        $this->parentWa = WhatsAppUser::create([
            'phone' => '+256701111199',
            'user_id' => $this->parent->id,
            'school_id' => $this->schoolId,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $this->teacherWa = WhatsAppUser::create([
            'phone' => '+256701111198',
            'user_id' => $this->teacher->id,
            'school_id' => $this->schoolId,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $this->adminWa = WhatsAppUser::create([
            'phone' => '+256701111197',
            'user_id' => $this->admin->id,
            'school_id' => $this->schoolId,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $this->receptionistWa = WhatsAppUser::create([
            'phone' => '+256701111196',
            'user_id' => $this->receptionist->id,
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

        $this->whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $this->app->instance(WhatsAppBusinessService::class, $this->whatsApp);
    }

    public function test_escalation_fires_on_decided_phrase_set(): void
    {
        $service = app(WhatsAppHumanEscalationService::class);

        foreach (WhatsAppHumanEscalationService::PHRASES as $phrase) {
            $this->assertTrue($service->matches("Please {$phrase} now"), $phrase);
        }

        $this->assertFalse($service->matches('What is my fee balance?'));
        $this->assertFalse($service->matches('hello'));
    }

    public function test_parent_escalation_routes_to_receptionist_with_task_and_notify(): void
    {
        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(function (string $phone, string $message, ?string $flowType = null) {
                return $phone === $this->receptionistWa->phone
                    && str_contains($message, 'Esc Parent')
                    && $flowType === 'toshi_human_escalation';
            })
            ->andReturn(['success' => true]);

        $channel = app(WhatsAppToshiChannelService::class);
        ParentOperationsAgent::fake(['should not run']);

        $reply = $channel->ask($this->parentWa, 'I need to talk to a human about fees');

        $this->assertSame(WhatsAppHumanEscalationService::ACK_MESSAGE, $reply);
        ParentOperationsAgent::assertNeverPrompted();

        $this->assertTrue(
            Task::where('user_id', $this->receptionist->id)
                ->where('title', 'like', 'WA escalation:%')
                ->exists()
        );
    }

    public function test_teacher_escalation_routes_to_school_admin(): void
    {
        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn (string $phone) => $phone === $this->adminWa->phone)
            ->andReturn(['success' => true]);

        $channel = app(WhatsAppToshiChannelService::class);
        $reply = $channel->ask($this->teacherWa, 'Please speak to someone at the office');

        $this->assertSame(WhatsAppHumanEscalationService::ACK_MESSAGE, $reply);
        $this->assertTrue(
            Task::where('user_id', $this->admin->id)
                ->where('title', 'like', 'WA escalation:%')
                ->exists()
        );
        $this->assertFalse(
            Task::where('user_id', $this->receptionist->id)
                ->where('title', 'like', 'WA escalation:%')
                ->exists()
        );
    }

    public function test_school_admin_escalation_logs_only_no_self_notify_loop(): void
    {
        $this->whatsApp->shouldNotReceive('sendText');

        $channel = app(WhatsAppToshiChannelService::class);
        $reply = $channel->ask($this->adminWa, 'I want a real person to help');

        $this->assertSame(WhatsAppHumanEscalationService::ACK_MESSAGE, $reply);
        $this->assertSame(0, Task::where('title', 'like', 'WA escalation:%')->count());

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('properties->tool', 'WhatsAppHumanEscalation')
            ->where('properties->status', 'escalated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['arguments']['receiver_user_id']);
    }

    public function test_tool_calling_halts_for_remainder_of_turn(): void
    {
        $this->whatsApp->shouldReceive('sendText')->once()->andReturn(['success' => true]);

        ParentOperationsAgent::fake(['I would have called FeeBalanceTool'])->preventStrayPrompts();

        $channel = app(WhatsAppToshiChannelService::class);
        $reply = $channel->ask($this->parentWa, 'talk to a person please');

        $this->assertSame(WhatsAppHumanEscalationService::ACK_MESSAGE, $reply);
        ParentOperationsAgent::assertNeverPrompted();
    }

    public function test_audit_record_includes_acting_user_id(): void
    {
        $this->whatsApp->shouldReceive('sendText')->once()->andReturn(['success' => true]);

        app(WhatsAppToshiChannelService::class)
            ->ask($this->parentWa, 'human agent please');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('properties->tool', 'WhatsAppHumanEscalation')
            ->where('properties->status', 'escalated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->parent->id, $log->properties['acting_user_id']);
        $this->assertSame($this->parent->id, $log->causer_id);
        $this->assertArrayHasKey('approver_id', $log->properties);
        $this->assertNull($log->properties['approver_id']);
        $this->assertSame($this->receptionist->id, $log->properties['arguments']['receiver_user_id']);
    }

    public function test_parent_falls_back_to_school_admin_when_no_receptionist(): void
    {
        $this->receptionist->delete();
        $this->receptionistWa->delete();

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn (string $phone) => $phone === $this->adminWa->phone)
            ->andReturn(['success' => true]);

        $channel = app(WhatsAppToshiChannelService::class);
        $reply = $channel->ask($this->parentWa, 'I need to talk to a human');

        $this->assertSame(WhatsAppHumanEscalationService::ACK_MESSAGE, $reply);
        $this->assertTrue(
            Task::where('user_id', $this->admin->id)
                ->where('title', 'like', 'WA escalation:%')
                ->exists()
        );
    }
}
