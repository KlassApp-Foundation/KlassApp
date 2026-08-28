<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\School;
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

class ParentMagicLoginWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Mockery\MockInterface&WhatsAppBusinessService */
    private WhatsAppBusinessService $whatsApp;

    private User $parent;

    private WhatsAppUser $waUser;

    private string $phone = '+256700333444';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create(['name' => 'WA School', 'email' => 'wa@school.ug', 'status' => 1]);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'WA',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
        ]);

        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        $this->waUser = WhatsAppUser::create([
            'phone' => $this->phone,
            'user_id' => $this->parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $this->whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $this->app->instance(WhatsAppBusinessService::class, $this->whatsApp);
    }

    /** @test */
    public function web_login_keyword_sends_signed_magic_link_with_preview_url(): void
    {
        $captured = null;

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(function (string $phone, string $message, ?string $flowType, ?int $userId, bool $previewUrl) use (&$captured) {
                $captured = compact('phone', 'message', 'flowType', 'userId', 'previewUrl');

                return $phone === $this->phone
                    && $flowType === 'parent_magic_login'
                    && $userId === $this->parent->id
                    && $previewUrl === true
                    && str_contains($message, '/parent/magic-login/');
            })
            ->andReturn(['success' => true, 'message_id' => 'test']);

        $this->invokeRouteInbound('WEB_LOGIN');

        $this->assertNotNull($captured);
    }

    /** @test */
    public function dashboard_keyword_also_sends_magic_link(): void
    {
        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $message) => str_contains($message, '/parent/magic-login/'))
            ->andReturn(['success' => true, 'message_id' => 'test']);

        $this->invokeRouteInbound('DASHBOARD');
    }

    /** @test */
    public function web_login_without_linked_children_is_denied(): void
    {
        StudentParentLink::query()->delete();

        $this->whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(fn ($phone, $message, $flowType) => $flowType === 'parent_magic_login_denied')
            ->andReturn(['success' => true, 'message_id' => 'test']);

        $this->invokeRouteInbound('WEB_LOGIN');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function invokeRouteInbound(string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'routeInbound');
        $method->setAccessible(true);
        $method->invoke($controller, $this->waUser, $this->phone, $body, $this->whatsApp);
    }
}
