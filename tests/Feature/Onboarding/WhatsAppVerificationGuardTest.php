<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Models\OnboardingSession;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\OnboardingEngine;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $posted = WhatsAppUser::where('user_id', $this->admin->id)->where('phone', self::PHONE)->first();
        $this->assertNotNull($posted);
        $this->assertNull($posted->verified_at);
        WhatsAppUser::where('user_id', $this->admin->id)->delete();

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
