<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Hard outbound-comms guard: every is_demo school (including the two on
 * production) must never send real WhatsApp, SMS, email or push messages.
 */
class DemoSchoolCommsGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DemoSchoolCommsGuard::flushCache();

        config([
            'mail.default' => 'array',
            'mail.from.address' => 'guard@schools.test',
            'mail.from.name' => 'Guard Tests',
        ]);
    }

    private function makeSchool(string $name, array $overrides = []): School
    {
        $school = School::create(array_merge([
            'name' => $name,
            'slug' => str()->slug($name),
            'email' => str()->slug($name) . '@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ], $overrides));

        // is_demo / is_test are deliberately not mass-assignable; set explicitly.
        $flags = collect($overrides)->only(['is_demo', 'is_test']);
        if ($flags->isNotEmpty()) {
            $school->forceFill($flags->all())->save();
        }

        return $school->refresh();
    }

    private function makeUser(School $school, string $email, array $overrides = []): User
    {
        $user = User::create(array_merge([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Guard Tester',
            'email' => $email,
            'password' => bcrypt('password'),
            'status' => 'active',
        ], $overrides));

        // mobile_no / device_id and friends are partially fillable; pin the
        // overrides explicitly so channel lookups find them.
        $extras = collect($overrides)->only(['mobile_no', 'device_id']);
        if ($extras->isNotEmpty()) {
            $user->forceFill($extras->all())->save();
        }

        return $user->refresh();
    }

    public function test_email_to_a_demo_school_recipient_is_cancelled_before_the_transport(): void
    {
        config(['mail.default' => 'array']);

        $demoSchool = $this->makeSchool('Guard Demo', ['is_demo' => 1]);
        $normalSchool = $this->makeSchool('Guard Normal');
        $demoUser = $this->makeUser($demoSchool, 'guard-demo@demo.klassapp.test');
        $normalUser = $this->makeUser($normalSchool, 'guard-normal@schools.test');

        Mail::raw('hello demo', fn ($m) => $m->to($demoUser->email)->subject('guard'));
        Mail::raw('hello normal', fn ($m) => $m->to($normalUser->email)->subject('guard'));

        $transport = app('mailer')->getSymfonyTransport();
        $this->assertCount(1, $transport->messages(), 'Only the non-demo email may reach the transport');
        $this->assertSame('guard-normal@schools.test', $transport->messages()[0]->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_email_to_the_reserved_demo_domain_is_blocked_even_without_a_user_row(): void
    {
        config(['mail.default' => 'array']);
        $this->makeSchool('Guard Demo 2', ['is_demo' => 1]);

        Mail::raw('ghost mail', fn ($m) => $m->to('ghost@demo.klassapp.test')->subject('guard'));

        $this->assertCount(0, app('mailer')->getSymfonyTransport()->messages());
    }

    public function test_sms_to_a_demo_school_number_is_blocked_without_touching_the_gateway(): void
    {
        $demoSchool = $this->makeSchool('Guard SMS Demo', ['is_demo' => 1]);
        $normalSchool = $this->makeSchool('Guard SMS Normal');
        $this->makeUser($demoSchool, 'guard-sms-demo@demo.klassapp.test', ['mobile_no' => '+256770000111']);
        $this->makeUser($normalSchool, 'guard-sms-normal@schools.test', ['mobile_no' => '+256770000999']);

        $this->assertTrue(DemoSchoolCommsGuard::blocksPhone('+256770000111'));
        $this->assertFalse(DemoSchoolCommsGuard::blocksPhone('+256770000999'));

        $sms = new class {
            use \App\Traits\MSG91;
        };

        $this->assertSame('blocked: demo school (no SMS sent)', $sms->sendSMS('hello', '+256770000111'));
    }

    public function test_whatsapp_sends_are_blocked_for_demo_school_recipients(): void
    {
        Http::fake();

        $demoSchool = $this->makeSchool('Guard WA Demo', ['is_demo' => 1]);
        $demoUser = $this->makeUser($demoSchool, 'guard-wa-demo@demo.klassapp.test');
        WhatsAppUser::create([
            'phone' => '+256770000222',
            'user_id' => $demoUser->id,
            'school_id' => $demoSchool->id,
        ]);

        $service = app(WhatsAppBusinessService::class);
        $response = $service->sendText('+256770000222', 'demo campus update');

        $this->assertTrue($response['blocked'] ?? false, 'Demo WhatsApp send must be blocked');
        Http::assertNothingSent();
    }

    public function test_push_to_a_demo_school_device_token_is_blocked(): void
    {
        $demoSchool = $this->makeSchool('Guard Push Demo', ['is_demo' => 1]);
        $this->makeUser($demoSchool, 'guard-push-demo@demo.klassapp.test', ['device_id' => 'demo-device-token']);

        $this->assertTrue(DemoSchoolCommsGuard::blocksDeviceToken('demo-device-token'));
        $this->assertFalse(DemoSchoolCommsGuard::blocksDeviceToken('some-other-token'));

        $pusher = new class {
            use \App\Traits\SendPushNotification;
        };

        $this->assertNull($pusher->sendNotification(['type' => 'guard', 'message' => 'hi'], 'demo-device-token'));
    }

    public function test_normal_schools_are_not_blocked_by_any_channel_check(): void
    {
        $school = $this->makeSchool('Guard Plain');
        $user = $this->makeUser($school, 'guard-plain@schools.test', ['mobile_no' => '+256771111222', 'device_id' => 'plain-token']);

        $this->assertFalse(DemoSchoolCommsGuard::blocksUser($user->id));
        $this->assertFalse(DemoSchoolCommsGuard::blocksEmail('guard-plain@schools.test'));
        $this->assertFalse(DemoSchoolCommsGuard::blocksPhone('+256771111222'));
        $this->assertFalse(DemoSchoolCommsGuard::blocksDeviceToken('plain-token'));
    }
}
