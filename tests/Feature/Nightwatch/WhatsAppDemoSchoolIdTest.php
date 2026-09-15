<?php

namespace Tests\Feature\Nightwatch;

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

class WhatsAppDemoSchoolIdTest extends TestCase
{
    use RefreshDatabase;

    private string $phone = '+256700111222';

    private User $demoParent;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        // Burn school id=1 so the demo school cannot accidentally match the old hardcode.
        School::create([
            'name' => 'Burn School One',
            'email' => 'burn1@test.sch.ug',
            'phone' => '+256700000001',
            'slug' => 'burn-1-'.uniqid(),
            'status' => 1,
        ]);

        $school = School::create([
            'name' => 'WA Demo School',
            'email' => 'wa-demo@test.sch.ug',
            'phone' => '+256700000104',
            'slug' => 'wa-demo-'.uniqid(),
            'status' => 1,
        ]);

        // Production has no school id=1; demo parent must live on a real school.
        $this->demoParent = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'email' => 'demo.parent@test.sch.ug',
        ]);

        config(['services.whatsapp.demo_parent_user_id' => $this->demoParent->id]);
    }

    public function test_demo_inbound_uses_demo_parent_school_id_not_hardcoded_one(): void
    {
        $this->assertGreaterThan(1, (int) $this->demoParent->school_id);

        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')->once()->andReturn(['success' => true]);
        $whatsApp->shouldReceive('sendList')->zeroOrMoreTimes()->andReturn(['success' => true]);
        $whatsApp->shouldReceive('sendInteractiveButtons')->zeroOrMoreTimes()->andReturn(['success' => true]);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeUnrecognized('demo');

        $wa = WhatsAppUser::where('phone', $this->phone)->first();
        $this->assertNotNull($wa);
        $this->assertSame((int) $this->demoParent->id, (int) $wa->user_id);
        $this->assertSame((int) $this->demoParent->school_id, (int) $wa->school_id);
        $this->assertNotSame(1, (int) $wa->school_id);
    }

    public function test_demo_inbound_graceful_when_demo_parent_missing(): void
    {
        config(['services.whatsapp.demo_parent_user_id' => 999999]);

        $messages = [];
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(function ($phone, $body) use (&$messages) {
                $messages[] = $body;

                return $phone === $this->phone;
            })
            ->andReturn(['success' => true]);
        $whatsApp->shouldReceive('sendList')->never();
        $whatsApp->shouldReceive('sendInteractiveButtons')->never();
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeUnrecognized('demo');

        $this->assertNull(WhatsAppUser::where('phone', $this->phone)->first());
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('Demo account not available', $messages[0]);
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
        $method->invoke($controller, $this->phone, $body, 'Nightwatch Tester');
    }
}
