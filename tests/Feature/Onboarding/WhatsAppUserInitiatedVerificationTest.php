<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Models\WhatsAppVerificationChallenge;
use App\Services\OnboardingStepsService;
use App\Services\WhatsApp\WhatsAppUserInitiatedVerification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Support\PostsMetaWhatsApp;
use Tests\TestCase;

/**
 * The admin sends the code to KlassApp. KlassApp does not send the code out.
 */
class WhatsAppUserInitiatedVerificationTest extends TestCase
{
    use PostsMetaWhatsApp;
    use RefreshDatabase;

    private const PHONE = '+256700111222';

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'app.timezone' => 'Africa/Kampala',
            'services.whatsapp.user_initiated_verification.enforce_limits_in_tests' => false,
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '000000',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.business_number' => '+256793844906',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00', 'Africa/Kampala'));
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Verify Academy',
            'email' => 'verify@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'verify-academy',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);
        $this->school->forceFill(['is_demo' => 0, 'is_test' => 0])->save();

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Verify Admin',
            'email' => 'admin@verify.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Verify',
            'lastname' => 'Admin',
        ]);

        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_issued_code_is_stored_as_a_hash_and_expires(): void
    {
        $issued = $this->service()->issue($this->admin->id, self::PHONE, '203.0.113.5');

        $this->assertTrue($issued['ok']);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', (string) $issued['code']);
        $this->assertDoesNotMatchRegularExpression('/[01ILO]/', (string) $issued['code']);

        $row = WhatsAppVerificationChallenge::query()->first();
        $this->assertNotNull($row);
        $this->assertSame($this->school->id, (int) $row->school_id);
        $this->assertNotSame($issued['code'], $row->code_hash);
        $this->assertSame(hash_hmac('sha256', (string) $issued['code'], (string) config('app.key')), $row->code_hash);
        $this->assertStringNotContainsString((string) $issued['code'], $row->code_hash);
        $this->assertSame(
            Carbon::parse('2026-10-21 12:10:00', 'Africa/Kampala')->utc()->getTimestamp(),
            $row->expires_at->utc()->getTimestamp()
        );

        Carbon::setTestNow(Carbon::parse('2026-10-21 12:11:00', 'Africa/Kampala'));
        $this->service()->handleInbound(self::PHONE, 'Verify KlassApp: '.$issued['code']);

        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));
    }

    public function test_inbound_from_the_matching_number_verifies_without_an_outbound_code(): void
    {
        $issued = $this->service()->issue($this->admin->id, self::PHONE, '203.0.113.5');
        Http::assertNothingSent();

        $this->postMetaWhatsAppText(self::PHONE, 'Verify KlassApp: '.$issued['code'])->assertOk();

        $row = WhatsAppUser::query()->where('user_id', $this->admin->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(self::PHONE, $row->phone);
        $this->assertSame($this->school->id, (int) $row->school_id);
        $this->assertNotNull($row->verified_at);
        $this->assertNotNull(WhatsAppVerificationChallenge::query()->value('used_at'));

        $sent = $this->outboundBodies();
        $this->assertCount(1, $sent);
        $this->assertSame('256700111222', $sent[0]['to']);
        $this->assertStringNotContainsString((string) $issued['code'], $sent[0]['body']);
        $this->assertStringContainsString('verified this number', $sent[0]['body']);
    }

    public function test_wrong_number_wrong_code_expired_and_reused_code_do_not_verify(): void
    {
        $issued = $this->service()->issue($this->admin->id, self::PHONE, '203.0.113.8');
        $code = (string) $issued['code'];

        $this->assertFalse($this->service()->handleInbound('+256700999888', 'Verify KlassApp: '.$code));
        $this->assertFalse($this->service()->handleInbound('700111222', 'Verify KlassApp: '.$code));
        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));

        $this->service()->handleInbound(self::PHONE, 'Verify KlassApp: ZZZZZZ');
        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));

        Carbon::setTestNow(Carbon::parse('2026-10-21 12:11:00', 'Africa/Kampala'));
        $this->service()->handleInbound(self::PHONE, 'Verify KlassApp: '.$code);
        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));

        Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00', 'Africa/Kampala'));
        $fresh = $this->service()->issue($this->admin->id, self::PHONE, '203.0.113.9');
        $this->service()->handleInbound(self::PHONE, 'Verify KlassApp: '.$fresh['code']);
        $verifiedAt = WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at');
        $this->assertNotNull($verifiedAt);

        $this->service()->handleInbound(self::PHONE, 'Verify KlassApp: '.$fresh['code']);
        $this->assertEquals($verifiedAt, WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));
        $this->assertSame(1, WhatsAppUser::query()->where('user_id', $this->admin->id)->count());
    }

    public function test_too_many_wrong_attempts_burn_the_code(): void
    {
        $issued = $this->service()->issue($this->admin->id, self::PHONE, '203.0.113.10');

        for ($i = 0; $i < 5; $i++) {
            $this->service()->handleInbound(self::PHONE, 'Verify KlassApp: ZZZZZZ');
        }

        $this->service()->handleInbound(self::PHONE, 'Verify KlassApp: '.$issued['code']);
        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));
        $this->assertNotNull(WhatsAppVerificationChallenge::query()->value('used_at'));
    }

    public function test_phone_and_ip_rate_limits(): void
    {
        config(['services.whatsapp.user_initiated_verification.enforce_limits_in_tests' => true]);
        $phone = self::PHONE;
        $ip = '203.0.113.20';
        RateLimiter::clear('wa-user-verify:phone:'.hash('sha256', $phone));
        RateLimiter::clear('wa-user-verify:ip:'.hash('sha256', $ip));

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->service()->issue($this->admin->id, $phone, $ip)['ok']);
        }
        $blocked = $this->service()->issue($this->admin->id, $phone, $ip);
        $this->assertFalse($blocked['ok']);
        $this->assertNull($blocked['code']);
        $this->assertSame('Too many codes for this number. Wait and try again.', $blocked['message']);
        $this->assertSame(5, WhatsAppVerificationChallenge::query()->count());

        $otherIp = '203.0.113.21';
        RateLimiter::clear('wa-user-verify:ip:'.hash('sha256', $otherIp));
        for ($i = 1; $i <= 10; $i++) {
            $number = '+256701000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            RateLimiter::clear('wa-user-verify:phone:'.hash('sha256', $number));
            $this->assertTrue($this->service()->issue($this->admin->id, $number, $otherIp)['ok'], $number);
        }
        $overIp = $this->service()->issue($this->admin->id, '+256701000011', $otherIp);
        $this->assertFalse($overIp['ok']);
        $this->assertNull($overIp['code']);
        $this->assertSame('Too many codes from this network. Wait and try again.', $overIp['message']);
    }

    public function test_logs_omit_the_code_and_the_full_phone(): void
    {
        $lines = [];
        Log::listen(function ($message) use (&$lines) {
            $lines[] = $message->message.' '.json_encode($message->context);
        });

        $issued = $this->service()->issue($this->admin->id, self::PHONE, '203.0.113.30');
        $code = (string) $issued['code'];
        $this->postMetaWhatsAppText(self::PHONE, 'Verify KlassApp: '.$code)->assertOk();

        $blob = implode("\n", $lines);
        $this->assertStringNotContainsString($code, $blob);
        $this->assertStringNotContainsString(self::PHONE, $blob);
        $this->assertStringNotContainsString('256700111222', $blob);

        $preview = (string) DB::table('message_delivery_log')->where('direction', 'inbound')->value('content_preview');
        $this->assertStringNotContainsString($code, $preview);
        $this->assertStringContainsString('******', $preview);
    }

    public function test_whatsapp_link_is_url_encoded(): void
    {
        $url = $this->service()->whatsAppLink('ABC234');

        $this->assertSame(
            'https://wa.me/256793844906?text='.rawurlencode('Verify KlassApp: ABC234'),
            $url
        );
        $this->assertStringContainsString('Verify%20KlassApp%3A%20ABC234', $url);
    }

    public function test_wizard_shows_the_code_polls_and_moves_on(): void
    {
        $this->actingAs($this->admin);

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('issueWhatsAppVerificationCode')
            ->assertSet('errorMessage', '')
            ->assertSeeHtml('data-testid="wizard-wa-poll"')
            ->assertSeeHtml('wire:poll.5s="pollWhatsAppVerification"');

        $code = (string) $component->get('whatsappOtpDisplay');
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $code);
        $component->assertSee($code, false);
        $component->assertSee('https://wa.me/256793844906?text='.rawurlencode('Verify KlassApp: '.$code), false);
        Http::assertNothingSent();

        $this->postMetaWhatsAppText(self::PHONE, 'Verify KlassApp: '.$code)->assertOk();

        $component->call('pollWhatsAppVerification')
            ->assertSet('whatsappVerified', true)
            ->assertSet('whatsappOtpDisplay', '');

        $this->assertSame('plan_selection', $component->get('steps')[$component->get('stepIndex')]['key'] ?? null);
        $this->assertNotNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));
    }

    public function test_wizard_shows_expired_code_message(): void
    {
        $this->actingAs($this->admin);

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('issueWhatsAppVerificationCode');

        Carbon::setTestNow(Carbon::parse('2026-10-21 12:11:00', 'Africa/Kampala'));

        $component->call('pollWhatsAppVerification')
            ->assertSee('This code expired. Get a new code.')
            ->assertSet('whatsappVerified', false);

        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->value('verified_at'));
    }

    public function test_setup_can_finish_while_whatsapp_stays_unverified(): void
    {
        $this->actingAs($this->admin);

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('next')
            ->assertSet('whatsappVerified', false)
            ->call('next')
            ->call('confirmReview')
            ->assertSet('finished', true);

        $this->assertNull(WhatsAppUser::query()->where('user_id', $this->admin->id)->first());
        $this->assertFalse(OnboardingStepsService::isStepComplete('whatsapp_verify', $this->school->fresh(), $this->admin->id));
        $this->assertNotContains(
            'whatsapp_verify',
            collect(OnboardingStepsService::blockingIncompleteSteps($this->school->fresh(), $this->admin->id))->pluck('key')->all()
        );
        $this->assertContains(
            'whatsapp_verify',
            OnboardingStepsService::progress($this->school->fresh(), $this->admin->id)['incomplete']
        );
    }

    private function service(): WhatsAppUserInitiatedVerification
    {
        return app(WhatsAppUserInitiatedVerification::class);
    }

    /** @return array<int, array{to: string, body: string}> */
    private function outboundBodies(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'graph.facebook.com'))
            ->map(function ($pair) {
                $data = $pair[0]->data();

                return [
                    'to' => (string) ($data['to'] ?? ''),
                    'body' => (string) ($data['text']['body'] ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function advanceToWhatsAppStep()
    {
        $component = Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'Verify Academy')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-VERIFY')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('skipOptionalStep');

        $this->goToStepKey($component, 'students');
        $component
            ->call('skipOptionalStep')
            ->call('next')
            ->call('next');

        $keys = array_column($component->get('steps'), 'key');
        $this->assertSame('whatsapp_verify', $keys[$component->get('stepIndex')] ?? null);

        return $component;
    }

    private function goToStepKey(object $component, string $key): void
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search($key, $keys, true);
        $this->assertNotFalse($index, "step {$key} missing");
        $component->call('goToStep', $index);
        $component->call('goToStep', $index);
    }
}
