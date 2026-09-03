<?php

namespace Tests\Feature\WhatsApp;

use App\Helpers\SiteHelper;
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
 * Regression: rejection buttons send ids parent_link_flow / link_help / MENU.
 * Linked parents hit processMetaMessage → routeInbound; without a bridge those
 * ids were unknown_keyword ("Sorry, I didn't understand").
 */
class LinkedParentLinkButtonRoutingTest extends TestCase
{
    use RefreshDatabase;

    private string $phone = '+256700555777';

    private WhatsAppUser $waUser;

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
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Button Route School',
            'email' => 'button-route@test.sch.ug',
            'phone' => '+256700119900',
            'status' => 1,
        ]);

        $parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Linked Parent',
        ]);

        Userprofile::create([
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Linked',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $this->waUser = WhatsAppUser::create([
            'phone' => $this->phone,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'status' => 'active',
            'mobile_no' => '+256700119900',
            'name' => 'School Admin',
        ]);

        WhatsAppUser::create([
            'phone' => '+256700119900',
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);
    }

    public function test_linked_parent_tapping_request_link_opens_flow(): void
    {
        $flowSent = false;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendParentLinkRequestFlow')
            ->once()
            ->with($this->phone)
            ->andReturnUsing(function () use (&$flowSent) {
                $flowSent = true;

                return ['success' => true, 'message_id' => 'wamid.flow'];
            });
        $whatsApp->shouldNotReceive('sendText');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMetaMessage('parent_link_flow');

        $this->assertTrue($flowSent);
    }

    public function test_linked_parent_tapping_link_help_sends_help_buttons(): void
    {
        $captured = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendInteractiveButtons')
            ->once()
            ->withArgs(function (string $phone, string $message, array $buttons, ?string $flowType) use (&$captured) {
                $captured = compact('phone', 'message', 'buttons', 'flowType');

                return $phone === $this->phone
                    && $flowType === 'link_help'
                    && collect($buttons)->contains(fn ($b) => ($b['id'] ?? '') === 'parent_link_flow');
            })
            ->andReturn(['success' => true, 'message_id' => 'wamid.help']);
        $whatsApp->shouldNotReceive('sendParentLinkRequestFlow');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMetaMessage('link_help');

        $this->assertNotNull($captured);
        $this->assertSame('link_help', $captured['flowType']);
        $this->assertStringContainsString('2 ways', $captured['message']);
        $this->assertStringContainsString('KlassApp ID', $captured['message']);
        $this->assertStringContainsString('Request Form', $captured['message']);
        $this->assertStringNotContainsString('Full name', $captured['message']);
        $this->assertStringNotContainsString('3 ways', $captured['message']);
    }

    public function test_linked_parent_tapping_menu_still_routes_to_menu(): void
    {
        $menuSent = false;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn (string $phone, string $message, ?string $flowType) => $flowType === 'menu_greeting')
            ->andReturn(['success' => true, 'message_id' => 'greet']);
        $whatsApp->shouldReceive('sendList')
            ->once()
            ->andReturnUsing(function () use (&$menuSent) {
                $menuSent = true;

                return ['success' => true, 'message_id' => 'menu'];
            });
        $whatsApp->shouldNotReceive('sendParentLinkRequestFlow');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        // Meta button id MENU is lowercased in routeInbound to "menu"
        $this->invokeProcessMetaMessage('MENU');

        $this->assertTrue($menuSent);
    }

    public function test_school_admin_whatsapp_footer_includes_real_number(): void
    {
        $schoolId = (int) $this->waUser->school_id;
        $phone = SiteHelper::schoolAdminWhatsAppPhone($schoolId);
        $footer = SiteHelper::schoolOfficeWhatsAppFooter($schoolId);

        $this->assertSame('+256700119900', $phone);
        $this->assertStringContainsString('+256700119900', $footer);
        $this->assertStringNotContainsString('Contact the school office for details.', $footer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function invokeProcessMetaMessage(string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'processMetaMessage');
        $method->setAccessible(true);
        $method->invoke($controller, $this->phone, $body, 'wamid.button.tap', 'Linked Parent');
    }
}
