<?php

namespace Tests\Feature\Toshi\WhatsApp;

use App\AiAgents\Tools\Accountant\CreateTaskTool as AccountantCreateTaskTool;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\Accountant\RecordPaymentTool as AccountantRecordPaymentTool;
use App\AiAgents\Tools\Librarian\CreateTaskTool as LibrarianCreateTaskTool;
use App\AiAgents\Tools\Librarian\ManageLendingTool;
use App\AiAgents\Tools\Receptionist\CreateTaskTool as ReceptionistCreateTaskTool;
use App\AiAgents\Tools\Student\ManageTasksTool;
use App\AiAgents\Tools\Student\SubmitAssignmentTool;
use App\AiAgents\Tools\Student\SubmitHomeworkTool;
use App\AiAgents\Tools\Teacher\CreateTaskTool as TeacherCreateTaskTool;
use App\AiAgents\Tools\Teacher\EnterMarksTool;
use App\AiAgents\Tools\Teacher\MarkAttendanceTool;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsAppPendingConfirmation;
use App\Models\WhatsAppUser;
use App\Services\ToshiActionService;
use App\Services\WhatsApp\WhatsAppConfirmationBridge;
use App\Services\WhatsApp\WhatsAppToshiChannelService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Part B wave 1: allowlisted CreateTask / ManageTasks over WhatsApp via
 * existing WhatsAppConfirmationBridge (no new bridge logic).
 */
class WhatsAppWritesWave1Test extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

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
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'WA Writes Wave1 School',
            'slug' => 'wa-writes-w1',
            'email' => 'wa-writes-w1@test.sch.ug',
            'phone' => '+256700000099',
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

        config([
            'toshi.sdk_v2_enabled' => true,
            'toshi.whatsapp_channel_enabled' => true,
            'toshi.per_school_gate' => true,
            'ai.providers.openai-compatible.key' => 'test-key',
        ]);

        $this->whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $this->app->instance(WhatsAppBusinessService::class, $this->whatsApp);
    }

    /**
     * Fail-closed: non-allowlisted ConfirmsBeforeWrite tools remain excluded
     * after the wave-1 allowlist lands (denylist default unchanged).
     */
    public function test_fail_closed_non_allowlisted_confirms_before_write_still_excluded(): void
    {
        $this->assertFalse(
            WhatsAppWriteExclusion::allowsClass(MarkAttendanceTool::class),
            'Teacher MarkAttendance must stay excluded'
        );
        $this->assertFalse(
            WhatsAppWriteExclusion::allowsClass(AccountantRecordPaymentTool::class),
            'Accountant RecordPayment must stay excluded'
        );
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(EnterMarksTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(ManageLendingTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(SubmitAssignmentTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(SubmitHomeworkTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(ManagePayrollTool::class));

        $teacher = $this->makeUser(5, 'failclosed.teacher@test.sch.ug');
        $accountant = $this->makeUser(11, 'failclosed.acct@test.sch.ug');
        $channel = app(WhatsAppToshiChannelService::class);

        $this->assertNotContains(MarkAttendanceTool::class, $channel->exposedToolClasses($teacher));
        $this->assertNotContains(AccountantRecordPaymentTool::class, $channel->exposedToolClasses($accountant));
        $this->assertFalse($channel->canInvokeTool($teacher, MarkAttendanceTool::class));
        $this->assertFalse($channel->canInvokeTool($accountant, AccountantRecordPaymentTool::class));
    }

    public function test_write_allowlist_names_exactly_five_task_tools(): void
    {
        $this->assertSame([
            TeacherCreateTaskTool::class,
            AccountantCreateTaskTool::class,
            LibrarianCreateTaskTool::class,
            ReceptionistCreateTaskTool::class,
            ManageTasksTool::class,
        ], WhatsAppWriteExclusion::WRITE_ALLOWLIST);

        foreach (WhatsAppWriteExclusion::WRITE_ALLOWLIST as $class) {
            $this->assertTrue(WhatsAppWriteExclusion::allowsClass($class));
            $this->assertTrue(WhatsAppWriteExclusion::isAllowlisted($class));
        }

        // HARD_DENY still wins over any ConfirmsBeforeWrite path.
        $this->assertTrue(WhatsAppWriteExclusion::isHardDenied(ManagePayrollTool::class));
        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(ManagePayrollTool::class));
    }

    public function test_teacher_happy_path_button_creates_task_and_audits(): void
    {
        $this->assertRoleHappyPathButton(
            usergroupId: 5,
            email: 'w1.teacher@test.sch.ug',
            phone: '+256701000001',
            toolKey: 'toolTeacherCreateTask',
            allowlistedClass: TeacherCreateTaskTool::class,
            deniedClasses: [MarkAttendanceTool::class, EnterMarksTool::class],
            title: 'Teacher WA task',
        );
    }

    public function test_teacher_text_fallback_yes_creates_task(): void
    {
        $this->assertRoleTextApprove(
            usergroupId: 5,
            email: 'w1.teacher.txt@test.sch.ug',
            phone: '+256701000011',
            toolKey: 'toolTeacherCreateTask',
            title: 'Teacher text task',
        );
    }

    public function test_accountant_happy_path_button_creates_task_and_audits(): void
    {
        $this->assertRoleHappyPathButton(
            usergroupId: 11,
            email: 'w1.acct@test.sch.ug',
            phone: '+256701000002',
            toolKey: 'toolAccountantCreateTask',
            allowlistedClass: AccountantCreateTaskTool::class,
            deniedClasses: [AccountantRecordPaymentTool::class, ManagePayrollTool::class],
            title: 'Accountant WA task',
        );
    }

    public function test_librarian_happy_path_button_creates_task_and_audits(): void
    {
        $this->assertRoleHappyPathButton(
            usergroupId: 8,
            email: 'w1.lib@test.sch.ug',
            phone: '+256701000003',
            toolKey: 'toolLibrarianCreateTask',
            allowlistedClass: LibrarianCreateTaskTool::class,
            deniedClasses: [ManageLendingTool::class],
            title: 'Librarian WA task',
        );
    }

    public function test_receptionist_happy_path_button_creates_task_and_audits(): void
    {
        $this->assertRoleHappyPathButton(
            usergroupId: 10,
            email: 'w1.recv@test.sch.ug',
            phone: '+256701000004',
            toolKey: 'toolReceptionistCreateTask',
            allowlistedClass: ReceptionistCreateTaskTool::class,
            deniedClasses: [],
            title: 'Receptionist WA task',
        );
    }

    public function test_student_happy_path_button_creates_task_and_audits(): void
    {
        $this->assertRoleHappyPathButton(
            usergroupId: 6,
            email: 'w1.student@test.sch.ug',
            phone: '+256701000005',
            toolKey: 'toolStudentManageTasks',
            allowlistedClass: ManageTasksTool::class,
            deniedClasses: [SubmitAssignmentTool::class, SubmitHomeworkTool::class],
            title: 'Student WA task',
            args: [
                'action' => 'create',
                'title' => 'Student WA task',
                'task_date' => '2026-08-15',
            ],
        );
    }

    public function test_reject_creates_nothing(): void
    {
        [$user, $wa] = $this->makeLinkedUser(5, 'w1.reject@test.sch.ug', '+256701000020');
        $token = 'reject01';

        WhatsAppPendingConfirmation::query()->create([
            'token' => $token,
            'phone' => $wa->phone,
            'user_id' => $user->id,
            'mechanism' => WhatsAppPendingConfirmation::MECHANISM_TIER2,
            'payload' => [
                'tool' => 'toolTeacherCreateTask',
                'args' => ['title' => 'Should not exist', 'task_date' => '2026-08-15'],
                'preview' => 'Create task: Should not exist',
                'school_id' => $this->schoolId,
            ],
            'preview' => 'Create task: Should not exist',
            'status' => WhatsAppPendingConfirmation::STATUS_PENDING,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $phone === $wa->phone && str_contains($msg, 'Cancelled'))
            ->andReturn(['success' => true, 'message_id' => 'rej-1']);

        $handled = app(WhatsAppConfirmationBridge::class)
            ->handleInbound($wa, $wa->phone, "tn_{$token}");

        $this->assertTrue($handled);
        $this->assertFalse(Task::where('user_id', $user->id)->where('title', 'Should not exist')->exists());
    }

    public function test_expired_token_cannot_create_task(): void
    {
        [$user, $wa] = $this->makeLinkedUser(5, 'w1.expired@test.sch.ug', '+256701000021');
        $token = 'expired1';

        WhatsAppPendingConfirmation::query()->create([
            'token' => $token,
            'phone' => $wa->phone,
            'user_id' => $user->id,
            'mechanism' => WhatsAppPendingConfirmation::MECHANISM_TIER2,
            'payload' => [
                'tool' => 'toolTeacherCreateTask',
                'args' => ['title' => 'Expired task', 'task_date' => '2026-08-15'],
                'preview' => 'Create task: Expired task',
                'school_id' => $this->schoolId,
            ],
            'preview' => 'Create task: Expired task',
            'status' => WhatsAppPendingConfirmation::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
        ]);

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $msg) => $msg === WhatsAppConfirmationBridge::EXPIRED_MESSAGE)
            ->andReturn(['success' => true, 'message_id' => 'exp-1']);

        $handled = app(WhatsAppConfirmationBridge::class)
            ->handleInbound($wa, $wa->phone, "YES {$token}");

        $this->assertTrue($handled);
        $this->assertFalse(Task::where('user_id', $user->id)->where('title', 'Expired task')->exists());
    }

    public function test_channel_dispatches_tier2_confirm_via_existing_bridge(): void
    {
        [$user, $wa] = $this->makeLinkedUser(5, 'w1.dispatch@test.sch.ug', '+256701000030');

        $this->whatsApp->shouldReceive('sendInteractiveButtons')
            ->once()
            ->withArgs(function (string $phone, string $body, array $buttons) use ($wa) {
                $this->assertSame($wa->phone, $phone);
                $this->assertStringContainsString('Reply YES ', $body);
                $this->assertTrue(str_starts_with($buttons[0]['id'], 'ty_'));
                $this->assertTrue(str_starts_with($buttons[1]['id'], 'tn_'));

                return true;
            })
            ->andReturn(['success' => true, 'message_id' => 'wamid.dispatch']);

        ToshiActionService::$pendingConfirmPayload = [
            'tool' => 'toolTeacherCreateTask',
            'args' => ['title' => 'Dispatched task', 'task_date' => '2026-08-15'],
            'preview' => 'Create task: Dispatched task',
        ];

        $channel = app(WhatsAppToshiChannelService::class);
        $this->assertTrue($channel->dispatchTier2ConfirmationIfPending($wa, $user));

        $this->assertDatabaseHas('whatsapp_pending_confirmations', [
            'phone' => $wa->phone,
            'user_id' => $user->id,
            'status' => 'pending',
            'mechanism' => 'tier2',
        ]);
        $this->assertNull(ToshiActionService::$pendingConfirmPayload);
    }

    public function test_school_admin_and_parent_excluded_from_wave1_writes(): void
    {
        $admin = $this->makeUser(3, 'w1.admin@test.sch.ug');
        $parent = $this->makeUser(7, 'w1.parent@test.sch.ug');
        $channel = app(WhatsAppToshiChannelService::class);

        foreach ([
            TeacherCreateTaskTool::class,
            AccountantCreateTaskTool::class,
            LibrarianCreateTaskTool::class,
            ReceptionistCreateTaskTool::class,
            ManageTasksTool::class,
        ] as $toolClass) {
            $this->assertFalse($channel->canInvokeTool($admin, $toolClass));
            $this->assertFalse($channel->canInvokeTool($parent, $toolClass));
        }
    }

    /**
     * @param  list<class-string>  $deniedClasses
     * @param  array<string, mixed>|null  $args
     */
    private function assertRoleHappyPathButton(
        int $usergroupId,
        string $email,
        string $phone,
        string $toolKey,
        string $allowlistedClass,
        array $deniedClasses,
        string $title,
        ?array $args = null,
    ): void {
        [$user, $wa] = $this->makeLinkedUser($usergroupId, $email, $phone);
        $channel = app(WhatsAppToshiChannelService::class);

        $this->assertTrue(WhatsAppWriteExclusion::allowsClass($allowlistedClass));
        $this->assertContains($allowlistedClass, $channel->exposedToolClasses($user));
        $this->assertTrue($channel->canInvokeTool($user, $allowlistedClass));

        foreach ($deniedClasses as $denied) {
            $this->assertNotContains($denied, $channel->exposedToolClasses($user));
            $this->assertFalse($channel->canInvokeTool($user, $denied));
        }

        $args ??= [
            'title' => $title,
            'task_date' => '2026-08-15',
        ];

        $token = substr(md5($email), 0, 8);

        WhatsAppPendingConfirmation::query()->create([
            'token' => $token,
            'phone' => $wa->phone,
            'user_id' => $user->id,
            'mechanism' => WhatsAppPendingConfirmation::MECHANISM_TIER2,
            'payload' => [
                'tool' => $toolKey,
                'args' => $args,
                'preview' => "Create task: {$title}",
                'school_id' => $this->schoolId,
            ],
            'preview' => "Create task: {$title}",
            'status' => WhatsAppPendingConfirmation::STATUS_PENDING,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($p, $msg) => $p === $wa->phone && str_starts_with($msg, '✅'))
            ->andReturn(['success' => true, 'message_id' => 'ok-'.$token]);

        $handled = app(WhatsAppConfirmationBridge::class)
            ->handleInbound($wa, $wa->phone, "ty_{$token}");

        $this->assertTrue($handled);
        $this->assertTrue(
            Task::where('user_id', $user->id)->where('title', $title)->exists(),
            "Task '{$title}' should exist for ug{$usergroupId}"
        );

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('causer_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($toolKey, $log->properties['tool']);
        $this->assertSame($user->id, $log->properties['acting_user_id']);
        $this->assertSame($user->id, $log->properties['approver_id']);
    }

    private function assertRoleTextApprove(
        int $usergroupId,
        string $email,
        string $phone,
        string $toolKey,
        string $title,
    ): void {
        [$user, $wa] = $this->makeLinkedUser($usergroupId, $email, $phone);
        $token = 'txtyes01';

        WhatsAppPendingConfirmation::query()->create([
            'token' => $token,
            'phone' => $wa->phone,
            'user_id' => $user->id,
            'mechanism' => WhatsAppPendingConfirmation::MECHANISM_TIER2,
            'payload' => [
                'tool' => $toolKey,
                'args' => ['title' => $title, 'task_date' => '2026-08-16'],
                'preview' => "Create task: {$title}",
                'school_id' => $this->schoolId,
            ],
            'preview' => "Create task: {$title}",
            'status' => WhatsAppPendingConfirmation::STATUS_PENDING,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($p, $msg) => $p === $wa->phone && str_starts_with($msg, '✅'))
            ->andReturn(['success' => true, 'message_id' => 'txt-ok']);

        $handled = app(WhatsAppConfirmationBridge::class)
            ->handleInbound($wa, $wa->phone, "YES {$token}");

        $this->assertTrue($handled);
        $this->assertTrue(Task::where('user_id', $user->id)->where('title', $title)->exists());
    }

    private function makeUser(int $usergroupId, string $email): User
    {
        return User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => $usergroupId,
            'email' => $email,
            'name' => $email,
        ]);
    }

    /**
     * @return array{0: User, 1: WhatsAppUser}
     */
    private function makeLinkedUser(int $usergroupId, string $email, string $phone): array
    {
        $user = $this->makeUser($usergroupId, $email);
        $wa = WhatsAppUser::query()->create([
            'phone' => $phone,
            'user_id' => $user->id,
            'school_id' => $this->schoolId,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        return [$user, $wa];
    }
}
