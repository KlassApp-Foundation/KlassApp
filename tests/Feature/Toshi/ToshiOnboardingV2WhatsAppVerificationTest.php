<?php

namespace Tests\Feature\Toshi;

use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Onboarding\Steps\StepRegistry;
use App\Services\DemoOnlyRecipientGuard;
use App\Services\DemoSchoolCommsGuard;
use App\Services\OnboardingStepsService;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Toshi onboarding v2 must send a verification code and mark the number
 * verified only after that code is entered. The code is never rendered.
 */
class ToshiOnboardingV2WhatsAppVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '0772000999';

    private const NORMALISED = '+256772000999';

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        config(['toshi.onboarding_v2' => true]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Verification Academy',
            'email' => 'v2-wa@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'v2-wa-verify',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'registration_country' => 'Uganda',
            'student_size' => 'Up to 500',
            'ministry_code' => 'EMIS-V2-WA',
            'uneb_center_number' => '',
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);
        $this->school->forceFill(['is_demo' => 0])->save();

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'V2 WhatsApp Admin',
            'email' => 'admin@v2-wa.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $this->admin->id,
            'status' => 1,
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'name' => 'Term 1',
            'academic_year_id' => $year->id,
            'status' => 'current',
        ]);

        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'name' => 'Tuition',
            'amount' => 100000,
        ]);

        $school = $this->school->fresh();
        OnboardingStepsService::markStepSkipped($school, 'teachers');
        OnboardingStepsService::markStepSkipped($school, 'students');
        $this->school = $school->fresh();

        $next = app(StepRegistry::class)->nextUnfinished($this->school, $this->admin->id);
        $this->assertSame('whatsapp_verify', $next?->key());

        DemoOnlyRecipientGuard::flush();
        DemoSchoolCommsGuard::flushCache();
        RateLimiter::clear('admin-wa-verification:'.$this->admin->id);
        session()->forget(['wizard_wa_otp.code', 'wizard_wa_otp.phone', 'wizard_wa_otp.user_id', 'wizard_wa_otp.expires_at']);
    }

    public function test_v2_whatsapp_step_does_not_mark_verified_before_the_code(): void
    {
        $this->configureWhatsApp();
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('input', self::PHONE)
            ->call('send')
            ->assertSet('whatsappSentOtp', '');

        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        $this->assertSame('onboarding_v2', $component->get('actionStep'));
        $this->assertSame(
            'whatsapp_verify',
            app(StepRegistry::class)->nextUnfinished($this->school->fresh(), $this->admin->id)?->key()
        );

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $code = $this->codeFromBody($sent[0]['body']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertSame($code, app(WhatsAppOnboardingOtpService::class)->pendingCode());

        $messages = $this->botText($component);
        $this->assertStringContainsString('Enter it below', $messages);
        $this->assertStringContainsString('resend', $messages);
        $this->assertResponseHidesCode($component, $code);
    }

    public function test_v2_whatsapp_correct_code_verifies(): void
    {
        $this->configureWhatsApp();
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('input', self::PHONE)
            ->call('send');

        $code = app(WhatsAppOnboardingOtpService::class)->pendingCode();
        $this->assertNotNull($code);

        $component->set('input', $code)->call('send');

        $row = WhatsAppUser::where('user_id', $this->admin->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(self::NORMALISED, $row->phone);
        $this->assertSame($this->school->id, $row->school_id);
        $this->assertNotNull($row->verified_at);
        $this->assertTrue((bool) $row->opted_in);
        $this->assertNull(app(WhatsAppOnboardingOtpService::class)->pendingCode());

        $bot = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");
        $this->assertStringNotContainsString($code, $bot);
    }

    public function test_v2_whatsapp_wrong_code_does_not_verify(): void
    {
        $this->configureWhatsApp();
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('input', self::PHONE)
            ->call('send')
            ->set('input', '000000')
            ->call('send');

        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        $this->assertNotNull(app(WhatsAppOnboardingOtpService::class)->pendingCode());

        $messages = $this->botText($component);
        $this->assertStringContainsString("doesn't match", $messages);
        $this->assertStringContainsString('resend', $messages);
        $this->assertSame('whatsapp_verify', app(StepRegistry::class)->nextUnfinished($this->school->fresh(), $this->admin->id)?->key());

        $code = app(WhatsAppOnboardingOtpService::class)->pendingCode();
        $this->assertResponseHidesCode($component, (string) $code);
    }

    public function test_v2_whatsapp_code_is_not_in_the_rendered_response(): void
    {
        $this->configureWhatsApp();
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('input', self::PHONE)
            ->call('send')
            ->assertSet('whatsappSentOtp', '')
            ->assertSee('We sent a verification code to your WhatsApp.')
            ->assertDontSee('Your verification code')
            ->assertDontSee('WhatsApp API may be unavailable');

        $code = (string) app(WhatsAppOnboardingOtpService::class)->pendingCode();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertResponseHidesCode($component, $code);
        $this->assertStringContainsString($code, $this->outbound()[0]['body']);
    }

    public function test_v2_whatsapp_failed_send_offers_retry_and_hides_the_code(): void
    {
        $this->configureWhatsApp(500);
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('input', self::PHONE)
            ->call('send')
            ->assertSet('whatsappSentOtp', '')
            ->assertSee("We couldn't send the verification code.")
            ->assertSee('retry')
            ->assertDontSee('Your verification code');

        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        $code = (string) app(WhatsAppOnboardingOtpService::class)->pendingCode();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertResponseHidesCode($component, $code);
        $this->assertCount(1, $this->outbound());

        $component->set('input', 'retry')->call('send');
        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        $this->assertResponseHidesCode($component, (string) app(WhatsAppOnboardingOtpService::class)->pendingCode());
        $this->assertGreaterThanOrEqual(1, count($this->outbound()));
    }

    public function test_v2_whatsapp_sixth_guess_after_five_wrong_attempts_is_rejected(): void
    {
        $this->configureWhatsApp();
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('input', self::PHONE)
            ->call('send');

        $code = (string) app(WhatsAppOnboardingOtpService::class)->pendingCode();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        if ($code === '000000') {
            $component->set('input', 'resend')->call('send');
            $code = (string) app(WhatsAppOnboardingOtpService::class)->pendingCode();
        }

        for ($attempt = 0; $attempt < WhatsAppOnboardingOtpService::MAX_WRONG_GUESSES; $attempt++) {
            $component->set('input', '000000')->call('send');
            $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        }

        $this->assertNull(app(WhatsAppOnboardingOtpService::class)->pendingCode());

        $component->set('input', $code)->call('send');
        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        $this->assertNull(app(WhatsAppOnboardingOtpService::class)->grantPhone());
        $this->assertStringContainsString('Request a new one', $this->botText($component));

        $component->set('input', 'resend')->call('send');
        $fresh = (string) app(WhatsAppOnboardingOtpService::class)->pendingCode();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $fresh);

        $component->set('input', $fresh)->call('send');
        $row = WhatsAppUser::where('user_id', $this->admin->id)->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->verified_at);
        $this->assertSame(self::NORMALISED, $row->phone);
    }

    public function test_v2_whatsapp_step_does_not_skip(): void
    {
        $this->configureWhatsApp();
        $this->actingAs($this->admin);

        Livewire::test(AgentToshi::class)
            ->set('input', 'skip')
            ->call('send');

        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        $this->assertNotContains('whatsapp_verify', OnboardingStepsService::skippedSteps($this->school->fresh()));
        $this->assertSame(
            'whatsapp_verify',
            app(StepRegistry::class)->nextUnfinished($this->school->fresh(), $this->admin->id)?->key()
        );
        $this->assertSame([], $this->outbound());
    }

    private function configureWhatsApp(int $graphStatus = 200): void
    {
        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '106540352242922',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => true,
            'services.whatsapp.graph_api_base_url' => 'https://graph.facebook.com',
        ]);
        DemoOnlyRecipientGuard::flush();
        DemoSchoolCommsGuard::flushCache();
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(
                $graphStatus === 200
                    ? ['messages' => [['id' => 'wamid.TEST']]]
                    : ['error' => ['message' => 'Temporary failure']],
                $graphStatus,
            ),
        ]);
    }

    private function botText($component): string
    {
        return collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");
    }

    private function assertResponseHidesCode($component, string $code): void
    {
        $this->assertNotSame('', $code);
        $this->assertStringNotContainsString($code, $this->botText($component));
        $this->assertStringNotContainsString($code, (string) $component->get('whatsappSentOtp'));
        $this->assertStringNotContainsString($code, $component->html());

        $payload = json_encode($component->effects);
        $this->assertIsString($payload);
        $this->assertStringNotContainsString($code, $payload);
        $snapshot = json_encode($component->snapshot);
        $this->assertIsString($snapshot);
        $this->assertStringNotContainsString($code, $snapshot);
    }

    private function codeFromBody(string $body): string
    {
        preg_match('/\d{6}/', $body, $match);

        return $match[0] ?? '';
    }

    /** @return array<int, array{to: string, body: string}> */
    private function outbound(): array
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
}
