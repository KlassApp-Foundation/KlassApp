<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Soft-launch WhatsApp menu update:
 * - unknown numbers get the soft-launch intro (DEMO keyword + klassapp.xyz links);
 * - every known role gets a Help option (role_help text + Help/privacy links);
 * - the parent menu keeps REPORT/HELP/Dashboard rows (WEB_LOGIN last).
 */
class SoftLaunchHelpTest extends TestCase
{
    use RefreshDatabase;

    private string $strangerPhone = '+256700777111';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
            'services.whatsapp.demo_parent_user_id' => 0, // DEMO must degrade gracefully
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_unknown_number_intro_mentions_soft_launch_and_help_links(): void
    {
        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendInteractiveButtons')
            ->once()
            ->withArgs(function (string $phone, string $message, array $buttons, ?string $flowType) use (&$captured) {
                $captured = compact('message', 'buttons', 'flowType');

                return $phone === $this->strangerPhone && $flowType === 'unrecognized_prompt';
            })
            ->andReturn(['success' => true, 'message_id' => 'welcome']);
        $whatsApp->shouldNotReceive('sendText');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeUnrecognized('hello there');

        $this->assertSame('unrecognized_prompt', $captured['flowType']);
        $this->assertStringContainsString('soft launch', $captured['message']);
        $this->assertStringContainsString('https://klassapp.xyz', $captured['message']);
        $this->assertStringContainsString('https://klassapp.xyz/help', $captured['message']);
        $this->assertStringContainsString('https://klassapp.xyz/privacy', $captured['message']);
        $this->assertSame('demo', $captured['buttons'][0]['id']);
        $this->assertStringContainsString('Try Demo', $captured['buttons'][0]['title']);
        $this->assertStringContainsString('Request Link', $captured['buttons'][2]['title']);
    }

    public function test_unknown_number_typing_demo_without_config_degrades_to_text(): void
    {
        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$captured) {
                $captured = $message;

                return ['success' => true, 'message_id' => 'demo-unavailable'];
            });
        $whatsApp->shouldNotReceive('sendInteractiveButtons');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeUnrecognized('DEMO');

        $this->assertStringContainsString('Demo account not available', $captured);
    }

    public function test_parent_menu_keeps_report_and_dashboard_rows_and_adds_help(): void
    {
        [$parentWa] = $this->makeParent();

        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')->andReturn(['success' => true, 'message_id' => 'greet']);
        $whatsApp->shouldReceive('sendList')
            ->once()
            ->andReturnUsing(function (...$args) use (&$captured) {
                $sections = $args[2] ?? [];

                $captured = $sections;

                return ['success' => true, 'message_id' => 'menu'];
            });
        $whatsApp->shouldReceive('sendInteractiveButtons')->zeroOrMoreTimes();
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta($parentWa->phone, 'MENU');

        $rows = $captured[0]['rows'] ?? [];
        $ids = array_column($rows, 'id');
        $this->assertSame('WEB_LOGIN', $ids[array_key_last($ids)]);
        $this->assertContains('REPORT', $ids);
        $this->assertContains('HELP', $ids);
    }

    public function test_parent_help_returns_report_card_and_link_guide(): void
    {
        [$parentWa] = $this->makeParent();

        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$captured) {
                $captured = compact('message', 'flowType');

                return ['success' => true, 'message_id' => 'help'];
            });
        $whatsApp->shouldNotReceive('sendInteractiveButtons');
        $whatsApp->shouldNotReceive('sendList');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta($parentWa->phone, 'HELP');

        $this->assertSame('role_help', $captured['flowType']);
        $this->assertStringContainsString('report', $captured['message']);
        $this->assertStringContainsString('fees', $captured['message']);
        $this->assertStringContainsString('https://klassapp.xyz/help', $captured['message']);
        $this->assertStringContainsString('https://klassapp.xyz/privacy', $captured['message']);
    }

    public function test_teacher_help_mentions_marks_and_attendance(): void
    {
        [$wa] = $this->makeKnownUser(5, 'Tina Teacher');

        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$captured) {
                if ($flowType === 'role_help') {
                    $captured = $message;
                }

                return ['success' => true, 'message_id' => 'help'];
            });
        $whatsApp->shouldReceive('sendInteractiveButtons')->zeroOrMoreTimes();
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta($wa->phone, 'how to');

        $this->assertStringContainsString('marks', $captured);
        $this->assertStringContainsString('attendance', $captured);
        $this->assertStringContainsString('https://klassapp.xyz/help', $captured);
    }

    public function test_admin_and_bursar_and_student_and_receptionist_help_route(): void
    {
        foreach ([3 => 'exams', 11 => 'fees', 6 => 'grades', 10 => 'calls'] as $role => $needle) {
            [$wa] = $this->makeKnownUser($role, "Role{$role} User");

            $captured = null;
            $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
            $whatsApp->shouldReceive('sendText')
                ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$captured) {
                    if ($flowType === 'role_help') {
                        $captured = $message;
                    }

                    return ['success' => true, 'message_id' => 'help'];
                });
            $whatsApp->shouldReceive('sendInteractiveButtons')->zeroOrMoreTimes();
            $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

            $this->invokeProcessMeta($wa->phone, 'help');

            $this->assertStringContainsString($needle, $captured, "role {$role} help must mention {$needle}");
        }
    }

    public function test_menu_keyword_still_opens_menu_not_help(): void
    {
        [$parentWa] = $this->makeParent();

        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->withArgs(fn (string $phone, string $message, ?string $flowType) => $flowType === 'menu_greeting')
            ->andReturn(['success' => true, 'message_id' => 'greet']);
        $whatsApp->shouldReceive('sendList')
            ->andReturnUsing(function (string $phone, string $title, array $sections) use (&$captured) {
                $captured = true;

                return ['success' => true, 'message_id' => 'menu'];
            });
        $whatsApp->shouldReceive('sendInteractiveButtons')->zeroOrMoreTimes();
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta($parentWa->phone, 'menu');

        $this->assertTrue($captured);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return array{0: WhatsAppUser, 1: User} */
    private function makeParent(): array
    {
        $school = School::create([
            'name' => 'Soft Launch School',
            'email' => 'softlaunch@test.sch.ug',
            'status' => 1,
        ]);

        $parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Help Parent',
        ]);

        Userprofile::create([
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Help',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $wa = WhatsAppUser::create([
            'phone' => '+256700444999',
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        return [$wa, $parent];
    }

    /** @return array{0: WhatsAppUser, 1: User} */
    private function makeKnownUser(int $usergroupId, string $name): array
    {
        $school = School::create([
            'name' => 'Soft Launch School ' . $usergroupId,
            'email' => 'staff-' . uniqid() . '@test.sch.ug',
            'status' => 1,
        ]);

        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => $usergroupId,
            'status' => 'active',
            'name' => $name,
        ]);

        Userprofile::create([
            'user_id' => $user->id,
            'usergroup_id' => $usergroupId,
            'school_id' => $school->id,
            'firstname' => $name,
            'lastname' => '',
            'status' => 'active',
        ]);

        $wa = WhatsAppUser::create([
            'phone' => '+256700444'.str_pad((string) $usergroupId, 3, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        return [$wa, $user];
    }

    private function invokeUnrecognized(string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'handleUnrecognizedUserMeta');
        $method->setAccessible(true);
        $method->invoke($controller, $this->strangerPhone, $body, 'Stranger');
    }

    private function invokeProcessMeta(string $phone, string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'processMetaMessage');
        $method->setAccessible(true);
        $method->invoke($controller, $phone, $body, 'wamid.test', 'Parent');
    }
}
