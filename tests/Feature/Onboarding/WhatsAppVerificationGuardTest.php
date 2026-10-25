<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Models\OnboardingSession;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A WhatsApp number is verified only after the server checks the code it issued.
 * Browser-sent flags and a sixth guess of a burned code must not verify it.
 */
class WhatsAppVerificationGuardTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+256700555111';

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Guard Academy',
            'email' => 'guard@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'guard-academy',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);
        // This test stays on the Toshi path so the phone form and commit still run.
        $this->school->forceFill(['is_test' => 1])->save();

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Guard Admin',
            'email' => 'admin@guard.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_tampered_state_cannot_mark_whatsapp_verified(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/whatsapp/phone', [
            'phone' => self::PHONE,
            'verified' => '1',
            'codeWasVerified' => '1',
            'verified_at' => now()->toDateTimeString(),
        ]);

        $this->assertNull(
            WhatsAppUser::where('user_id', $this->admin->id)->where('phone', self::PHONE)->first(),
            'A posted phone with forged flags must not link anything — linking only happens by sending the code from WhatsApp.'
        );

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolName', $this->school->name)
            ->set('whatsappPhone', self::PHONE);

        try {
            $component->set('whatsappVerified', true);
            $component->set('whatsappSentOtp', '123456');
        } catch (\Throwable) {
            // Locked properties reject the browser write. Commit still must
            // ignore any flag that arrived another way.
        }

        $component->call('confirmOnboarding');
        $this->assertNumberStaysUnverified(self::PHONE);

        OnboardingSession::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'step' => 14,
            'substep' => 0,
            'status' => 'draft',
            'data' => [
                'mode' => 'complete',
                'schoolName' => $this->school->name,
                'whatsappPhone' => self::PHONE,
                'whatsappVerified' => true,
                'whatsappSentOtp' => '123456',
            ],
        ]);

        Livewire::test(AgentToshi::class)
            ->call('resumeDraft')
            ->call('confirmOnboarding');
        $this->assertNumberStaysUnverified(self::PHONE);

        try {
            app(OnboardingEngine::class)->saveWhatsApp(
                $this->school,
                $this->admin->id,
                self::PHONE,
                codeWasVerified: true,
            );
            $this->fail('saveWhatsApp must not treat a boolean as a successful code check.');
        } catch (ValidationException) {
            $this->assertNumberStaysUnverified(self::PHONE);
        }
    }

    public function test_sixth_guess_after_five_wrong_attempts_is_rejected(): void
    {
        $otp = app(WhatsAppOnboardingOtpService::class);
        $phone = self::PHONE;

        $first = $otp->sendAdminVerificationCode($this->admin->id, $phone);
        $code = (string) $first['code'];
        if ($code === '000000') {
            $first = $otp->sendAdminVerificationCode($this->admin->id, $phone);
            $code = (string) $first['code'];
        }
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $wrong = $otp->verifyAdminCode($this->admin->id, '000000', $phone);
            $this->assertFalse($wrong['verified']);
        }
        $this->assertSame($code, $otp->pendingCode());
        $this->assertTrue($otp->verifyAdminCode($this->admin->id, $code, $phone)['verified']);

        $second = $otp->sendAdminVerificationCode($this->admin->id, $phone);
        $burned = (string) $second['code'];
        if ($burned === '000000') {
            $second = $otp->sendAdminVerificationCode($this->admin->id, $phone);
            $burned = (string) $second['code'];
        }

        for ($attempt = 0; $attempt < WhatsAppOnboardingOtpService::MAX_WRONG_GUESSES; $attempt++) {
            $wrong = $otp->verifyAdminCode($this->admin->id, '000000', $phone);
            $this->assertFalse($wrong['verified']);
        }

        $this->assertNull($otp->pendingCode());
        $this->assertNull($otp->grantPhone());

        $sixth = $otp->verifyAdminCode($this->admin->id, $burned, $phone);
        $this->assertFalse($sixth['verified']);
        $this->assertStringContainsString('Request a new one', $sixth['message']);
        $this->assertNull($otp->grantPhone());

        $third = $otp->sendAdminVerificationCode($this->admin->id, $phone);
        $fresh = (string) $third['code'];
        $this->assertTrue($otp->verifyAdminCode($this->admin->id, $fresh, $phone)['verified']);
        $this->assertSame($phone, $otp->grantPhone());
        $this->assertFalse($otp->verifyAdminCode($this->admin->id, $burned, $phone)['verified']);
    }

    public function test_expired_code_is_rejected_and_then_deleted(): void
    {
        $this->actingAs($this->admin);
        $otp = app(WhatsAppOnboardingOtpService::class);
        $sent = $otp->sendAdminVerificationCode($this->admin->id, self::PHONE);
        $code = (string) $sent['code'];
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->travel(6)->minutes();

        $expired = $otp->verifyAdminCode($this->admin->id, $code, self::PHONE);
        $this->assertFalse($expired['verified']);
        $this->assertStringContainsString('expired', $expired['message']);
        $this->assertNull($otp->pendingCode());
        $this->assertNull($otp->grantPhone());
        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());

        $this->travel(-10)->minutes();

        $again = $otp->verifyAdminCode($this->admin->id, $code, self::PHONE);
        $this->assertFalse($again['verified']);
        $this->assertStringContainsString('expired', $again['message']);
        $this->assertNull($otp->pendingCode());
        $this->assertNull($otp->grantPhone());
        $this->assertFalse(
            OnboardingStepsService::isStepComplete('whatsapp_verify', $this->school->fresh(), $this->admin->id)
        );
    }

    public function test_a_correct_code_cannot_be_reused(): void
    {
        $this->actingAs($this->admin);
        $otp = app(WhatsAppOnboardingOtpService::class);
        $sent = $otp->sendAdminVerificationCode($this->admin->id, self::PHONE);
        $code = (string) $sent['code'];

        $this->assertTrue($otp->verifyAdminCode($this->admin->id, $code, self::PHONE)['verified']);
        app(OnboardingEngine::class)->saveWhatsApp(
            $this->school,
            $this->admin->id,
            self::PHONE,
            codeWasVerified: true,
        );
        $this->assertNull($otp->grantPhone());
        $this->assertNull($otp->pendingCode());
        $verifiedAt = WhatsAppUser::where('user_id', $this->admin->id)->value('verified_at');
        $this->assertNotNull($verifiedAt);

        $again = $otp->verifyAdminCode($this->admin->id, $code, self::PHONE);
        $this->assertFalse($again['verified']);
        $this->assertNull($otp->grantPhone());
        $this->assertNull($otp->pendingCode());
        $this->assertSame(1, WhatsAppUser::where('user_id', $this->admin->id)->count());
        $this->assertEquals($verifiedAt, WhatsAppUser::where('user_id', $this->admin->id)->value('verified_at'));
    }

    public function test_legacy_send_without_an_authenticated_admin_is_refused(): void
    {
        config([
            'toshi.onboarding_v2' => false,
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '106540352242922',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.graph_api_base_url' => 'https://graph.facebook.com',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]], 200),
        ]);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'create')
            ->set('step', 15)
            ->set('substep', 2)
            ->set('schoolCountry', 'Uganda')
            ->set('whatsappPhone', self::PHONE)
            ->set('input', self::PHONE)
            ->call('send');

        $component->assertSee('Sign in before we send a verification code.');
        $this->assertCount(0, Http::recorded());
        $this->assertNull(app(WhatsAppOnboardingOtpService::class)->pendingCode());
        $this->assertNull(app(WhatsAppOnboardingOtpService::class)->grantPhone());
        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());

        $direct = app(WhatsAppOnboardingOtpService::class)->sendAdminVerificationCode(0, self::PHONE);
        $this->assertFalse($direct['sent']);
        $this->assertSame('unauthenticated', $direct['reason']);
        $this->assertNull($direct['code']);
        $this->assertCount(0, Http::recorded());
    }

    private function assertNumberStaysUnverified(string $phone): void
    {
        $row = WhatsAppUser::where('phone', $phone)->orWhere('user_id', $this->admin->id)->first();
        if ($row !== null) {
            $this->assertNull($row->verified_at);
        } else {
            $this->assertNull($row);
        }
    }
}
