<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppBusinessService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Hard outbound-comms guard: every is_demo school must never send
 * business-initiated WhatsApp, SMS, email or push. A reply to the
 * person's own inbound message still goes out.
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

    public function test_a_reply_to_the_users_own_message_still_goes_out_for_a_demo_school(): void
    {
        Http::fake();

        $demoSchool = $this->makeSchool('Guard Reply Demo', ['is_demo' => 1]);
        $demoUser = $this->makeUser($demoSchool, 'guard-reply@demo.klassapp.test');
        WhatsAppUser::create([
            'phone' => '+256770000333',
            'user_id' => $demoUser->id,
            'school_id' => $demoSchool->id,
        ]);

        DemoSchoolCommsGuard::beginInboundReply();
        try {
            $response = app(WhatsAppBusinessService::class)->sendText('+256770000333', 'Here is your balance.', 'service', $demoUser->id);
        } finally {
            DemoSchoolCommsGuard::endInboundReply();
        }

        $this->assertFalse($response['blocked'] ?? false);
        Http::assertSentCount(1);

        Http::fake();
        $blocked = app(WhatsAppBusinessService::class)->sendText('+256770000333', 'Fee reminder', 'utility', $demoUser->id);
        $this->assertTrue($blocked['blocked'] ?? false);
        Http::assertNothingSent();
    }

    public function test_queued_business_sends_stay_blocked_during_a_demo_reply(): void
    {
        Http::fake();

        $demoSchool = $this->makeSchool('Guard Queue Demo', ['is_demo' => 1]);
        $demoUser = $this->makeUser($demoSchool, 'guard-queue@demo.klassapp.test');
        WhatsAppUser::create([
            'phone' => '+256770000777',
            'user_id' => $demoUser->id,
            'school_id' => $demoSchool->id,
        ]);

        DemoSchoolCommsGuard::beginInboundReply();
        try {
            $response = DemoSchoolCommsGuard::duringBusinessSend(
                fn () => app(WhatsAppBusinessService::class)->sendText('+256770000777', 'queued reminder', 'utility', $demoUser->id)
            );
        } finally {
            DemoSchoolCommsGuard::endInboundReply();
        }

        $this->assertTrue($response['blocked'] ?? false);
        Http::assertNothingSent();
    }

    public function test_demo_session_notice_names_the_thirty_minute_removal_and_the_privacy_url(): void
    {
        config(['services.whatsapp.privacy_notice_url' => 'https://example.com/privacy']);

        $this->assertSame(
            "Your number is removed after 30 minutes.\nPrivacy: https://example.com/privacy",
            DemoSchoolCommsGuard::demoSessionNotice(),
        );
        $this->assertStringContainsString(
            'demoSessionNotice()',
            file_get_contents(app_path('Http/Controllers/Api/WhatsAppController.php')),
        );
    }

    public function test_demo_chat_links_expire_after_thirty_minutes_without_touching_another_school(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
        config(['app.timezone' => 'Pacific/Auckland']);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'Pacific/Auckland'));

        $demo = $this->makeSchool('Expiry Demo', ['is_demo' => 1]);
        $other = $this->makeSchool('Expiry Other');
        $demoParent = $this->makeUser($demo, 'expiry-demo@demo.klassapp.test');
        $otherParent = $this->makeUser($other, 'expiry-other@schools.test');

        $expired = WhatsAppUser::create([
            'phone' => '+256770000444',
            'user_id' => $demoParent->id,
            'school_id' => $demo->id,
            'demo_linked_at' => now()->subMinutes(31),
        ]);
        $fresh = WhatsAppUser::create([
            'phone' => '+256770000445',
            'user_id' => $demoParent->id,
            'school_id' => $demo->id,
            'demo_linked_at' => now()->subMinutes(10),
        ]);
        $untouched = WhatsAppUser::create([
            'phone' => '+256770000446',
            'user_id' => $otherParent->id,
            'school_id' => $other->id,
            'demo_linked_at' => now()->subMinutes(90),
        ]);

        Artisan::call('demo:expire-chats', ['--dry-run' => true]);
        $this->assertStringContainsString('dry run — nothing changed', Artisan::output());
        $this->assertNotNull($expired->fresh()->user_id);

        Artisan::call('demo:expire-chats');
        $expired->refresh();
        $fresh->refresh();
        $untouched->refresh();

        $this->assertNull($expired->user_id);
        $this->assertNull($expired->school_id);
        $this->assertSame($demoParent->id, $fresh->user_id);
        $this->assertSame($demo->id, $fresh->school_id);
        $this->assertSame($otherParent->id, $untouched->user_id);
        $this->assertSame($other->id, $untouched->school_id);
        $this->assertNotNull(User::find($demoParent->id));
        $this->assertNotNull(User::find($otherParent->id));

        Artisan::call('demo:expire-chats');
        $this->assertStringContainsString('unlinked chats=0', Artisan::output());
        $this->assertNull($expired->fresh()->user_id);
        $this->assertStringContainsString(
            "command('demo:expire-chats')->everyFiveMinutes()",
            file_get_contents(app_path('Console/Kernel.php')),
        );
    }
}
