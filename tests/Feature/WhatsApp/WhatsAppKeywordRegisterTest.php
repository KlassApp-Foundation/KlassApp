<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class WhatsAppKeywordRegisterTest extends TestCase
{
    use RefreshDatabase;

    private string $phone = '+256700555010';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
        ]);

        DB::table('usergroups')->insert([
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_K115_stop_is_honoured_when_not_opted_in_and_optin_sends_one_line(): void
    {
        $user = $this->linkedParent(false);
        $sent = [];
        $this->bindTextCapture($sent);

        $this->invoke('STOP');

        $this->assertFalse((bool) $user->fresh()->opted_in);
        $this->assertSame('opted_out', $sent[0]['flow']);
        $this->assertStringContainsString('Reply *OPTIN*', $sent[0]['message']);

        $sent = [];
        $this->bindTextCapture($sent);
        $this->invoke('OPTIN');

        $this->assertTrue((bool) $user->fresh()->opted_in);
        $this->assertSame('optin', $sent[0]['flow']);
        $this->assertSame('You are opted in to WhatsApp messages from your school.', $sent[0]['message']);

        $user->update(['opted_in' => false]);
        $sent = [];
        $this->bindTextCapture($sent);
        $this->invoke('JOIN');

        $this->assertTrue((bool) $user->fresh()->opted_in);
        $this->assertSame('You are opted in to WhatsApp messages from your school.', $sent[0]['message']);
    }

    public function test_K115_demo_keyword_still_reaches_a_demo_school(): void
    {
        $this->linkedParent(false, true);
        $pointer = null;
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')->andReturnUsing(function (string $phone, string $message, ?string $flow = null) use (&$pointer) {
            if ($flow === 'demo_pointer') {
                $pointer = $message;
            }

            return ['success' => true];
        });
        $whatsApp->shouldReceive('sendList')->andReturn(['success' => true]);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invoke('DEMO');

        $this->assertNotNull($pointer, 'DEMO must reach the demo pointer reply');
        $this->assertStringContainsString('Welcome to the KlassApp demo.', $pointer);
    }

    private function linkedParent(bool $optedIn, bool $demo = false): WhatsAppUser
    {
        $school = School::create([
            'name' => $demo ? 'Demo Junior School' : 'Keyword School',
            'slug' => $demo ? 'demo-junior-keyword' : 'keyword-school',
            'email' => uniqid().'@keyword.test',
            'phone' => '+256700000111',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $school->forceFill(['is_demo' => $demo ? 1 : 0])->save();
        $parent = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
        ]);

        return WhatsAppUser::create([
            'phone' => $this->phone,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => $optedIn,
        ]);
    }

    /**
     * @param  list<array{message: string, flow: ?string}>  $sent
     */
    private function bindTextCapture(array &$sent): void
    {
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')->andReturnUsing(function (string $phone, string $message, ?string $flow = null) use (&$sent) {
            $sent[] = ['message' => $message, 'flow' => $flow];

            return ['success' => true];
        });
        $whatsApp->shouldReceive('sendInteractiveButtons')->andReturn(['success' => true]);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);
    }

    private function invoke(string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'processMetaMessage');
        $method->setAccessible(true);
        $method->invoke($controller, $this->phone, $body, 'wamid.k115', 'Parent');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
