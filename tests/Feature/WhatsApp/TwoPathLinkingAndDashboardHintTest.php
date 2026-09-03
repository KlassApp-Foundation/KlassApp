<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class TwoPathLinkingAndDashboardHintTest extends TestCase
{
    use RefreshDatabase;

    private string $strangerPhone = '+256700666111';

    private string $parentPhone = '+256700666222';

    private WhatsAppUser $parentWa;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
            'services.whatsapp.parent_link_flow_id' => '999888777',
            'services.whatsapp.parent_link_flow_screen' => 'LINK_REQUEST',
        ]);

        DB::table('usergroups')->insert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Two Path School',
            'email' => 'twopath@test.sch.ug',
            'status' => 1,
        ]);

        $parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Hint Parent',
        ]);

        Userprofile::create([
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Hint',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'name' => 'hint child999',
        ]);

        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        $this->parentWa = WhatsAppUser::create([
            'phone' => $this->parentPhone,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);
    }

    public function test_free_text_name_no_longer_searches_students(): void
    {
        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendInteractiveButtons')
            ->once()
            ->withArgs(function (string $phone, string $message, array $buttons, ?string $flowType) use (&$captured) {
                $captured = compact('phone', 'message', 'buttons', 'flowType');

                return $phone === $this->strangerPhone
                    && $flowType === 'unrecognized_prompt'
                    && str_contains($message, 'Request Link')
                    && ! str_contains($message, 'matching');
            })
            ->andReturn(['success' => true, 'message_id' => 'welcome']);
        $whatsApp->shouldNotReceive('sendText');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeUnrecognized('Amope Nandawula');

        $this->assertNotNull($captured);
        $this->assertSame('unrecognized_prompt', $captured['flowType']);
    }

    public function test_legacy_linktype_name_redirects_to_two_path_help(): void
    {
        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendInteractiveButtons')
            ->once()
            ->withArgs(function (string $phone, string $message, array $buttons, ?string $flowType) use (&$captured) {
                $captured = compact('message', 'flowType', 'buttons');

                return $flowType === 'link_help'
                    && str_contains($message, '2 ways')
                    && str_contains($message, 'KlassApp ID')
                    && ! str_contains($message, 'Full name');
            })
            ->andReturn(['success' => true, 'message_id' => 'help']);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeUnrecognized('linktype_name_99');

        $this->assertSame('link_help', $captured['flowType']);
    }

    public function test_parent_menu_lists_dashboard_last(): void
    {
        $greeting = null;
        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(function (string $phone, string $message, ?string $flowType) use (&$greeting) {
                $greeting = $message;

                return $flowType === 'menu_greeting';
            })
            ->andReturn(['success' => true, 'message_id' => 'greet']);
        $whatsApp->shouldReceive('sendList')
            ->once()
            ->withArgs(function (string $phone, string $title, array $sections, string $description, string $footerText, string $buttonText, ?string $flowType) use (&$captured) {
                $captured = compact('sections', 'flowType');
                $rows = $sections[0]['rows'] ?? [];
                $last = $rows === [] ? null : $rows[array_key_last($rows)];

                $ids = array_column($rows, 'id');

                return $flowType === 'menu'
                    && ($last['id'] ?? null) === 'WEB_LOGIN'
                    && in_array('REPORT', $ids, true)
                    && ! str_contains($description, 'WEB_LOGIN');
            })
            ->andReturn(['success' => true, 'message_id' => 'menu']);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta($this->parentPhone, 'MENU');

        $this->assertNotNull($captured);
        $this->assertStringContainsString('HINT PARENT', $greeting);
        $this->assertStringNotContainsString('WEB_LOGIN', $greeting);
    }

    public function test_fees_response_does_not_append_typed_web_login_hint(): void
    {
        $feeBodies = [];
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->atLeast()->once()
            ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$feeBodies) {
                if ($flowType === 'fees' || $flowType === 'fees_none_all') {
                    $feeBodies[] = $message;
                }

                return ['success' => true, 'message_id' => 'fees'];
            });
        $whatsApp->shouldReceive('sendList')
            ->zeroOrMoreTimes()
            ->andReturn(['success' => true, 'message_id' => 'btns']);
        $whatsApp->shouldReceive('sendInteractiveButtons')
            ->zeroOrMoreTimes()
            ->andReturn(['success' => true, 'message_id' => 'btns']);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta($this->parentPhone, 'FEES');

        $this->assertNotEmpty($feeBodies);
        $withHint = collect($feeBodies)->filter(fn ($m) => str_contains($m, 'WEB_LOGIN'));
        $this->assertSame(0, $withHint->count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
