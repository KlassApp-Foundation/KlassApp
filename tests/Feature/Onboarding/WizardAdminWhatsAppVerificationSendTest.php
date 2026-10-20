<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\MessageDeliveryLog;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\DemoOnlyRecipientGuard;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A school admin's verification code is a system message. With
 * demo-only recipients on, it still goes to that admin's number.
 * Production never puts the code in the response. Other sends stay refused.
 */
class WizardAdminWhatsAppVerificationSendTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+256700111222';

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

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
            'name' => 'OTP Wizard School',
            'email' => 'otp@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'otp-wizard-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);
        $this->school->forceFill(['is_demo' => 0])->save();

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'OTP Admin',
            'email' => 'admin@otp.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'OTP',
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

        DemoOnlyRecipientGuard::flush();
        DemoSchoolCommsGuard::flushCache();
        RateLimiter::clear('admin-wa-verification:'.$this->admin->id);
    }

    public function test_verification_code_is_sent_for_a_non_demo_school_when_demo_only_recipients_is_on(): void
    {
        $this->assertSame(0, (int) $this->school->fresh()->is_demo);
        $this->configureWhatsApp();

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappSendFailed', false);

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(ltrim(self::PHONE, '+'), $sent[0]['to']);
        $this->assertMatchesRegularExpression('/Your KlassApp verification code is: \d{6}\./', $sent[0]['body']);

        $log = MessageDeliveryLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame(DemoOnlyRecipientGuard::ADMIN_VERIFICATION, $log->flow_type);
        $this->assertSame('authentication', $log->category);
        $this->assertSame('WhatsApp verification code', $log->content_preview);
        $this->assertStringNotContainsString($this->codeFromBody($sent[0]['body']), (string) $log->content_preview);

        $component->assertSet('errorMessage', '');

        // A number already stored on this real school used to be refused
        // before the "not a demo recipient" check. The verification code still goes.
        WhatsAppUser::create([
            'phone' => self::PHONE,
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'opted_in' => true,
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.LINKED']]], 200),
        ]);
        $linked = DemoOnlyRecipientGuard::duringAdminVerification(
            (int) $this->admin->id,
            self::PHONE,
            fn () => app(WhatsAppBusinessService::class)->sendText(
                self::PHONE,
                app(WhatsAppOnboardingOtpService::class)->messageBody('654321'),
                DemoOnlyRecipientGuard::ADMIN_VERIFICATION,
                $this->admin->id,
            ),
        );
        $this->assertTrue($linked['success'] ?? false);
        $bodies = $this->outbound();
        $last = $bodies[array_key_last($bodies)]['body'] ?? '';
        $this->assertStringContainsString('654321', $last);
    }

    public function test_verification_code_never_appears_in_the_production_response(): void
    {
        $this->asProduction();
        $this->configureWhatsApp();

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappOtpDisplay', '')
            ->assertSet('whatsappSendFailed', false)
            ->assertSee('We sent a verification code to your WhatsApp.')
            ->assertDontSee('WhatsApp API may be unavailable')
            ->assertDontSee('Your verification code');

        $code = $this->codeFromBody($this->outbound()[0]['body']);
        $this->assertResponseHidesCode($component, $code);
        $this->assertSame($code, (string) session('wizard_wa_otp.code'));
    }

    public function test_failed_send_shows_an_error_and_retry_and_not_the_code(): void
    {
        $this->asProduction();
        $this->configureWhatsApp(500);

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappSendFailed', true)
            ->assertSet('whatsappOtpDisplay', '')
            ->assertSee("We couldn't send the verification code. Please try again.")
            ->assertSee('Retry')
            ->assertDontSee('WhatsApp API may be unavailable')
            ->assertDontSee('Your verification code');

        $code = (string) session('wizard_wa_otp.code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertStringContainsString('data-testid="wizard-wa-retry-otp"', $component->html());
        $this->assertResponseHidesCode($component, $code);
        $this->assertCount(1, $this->outbound(), 'the failure is a real send attempt, not a skipped guard');
    }

    public function test_verification_sends_are_rate_limited_without_showing_the_code(): void
    {
        $this->asProduction();
        $this->configureWhatsApp();

        $key = 'admin-wa-verification:'.$this->admin->id;
        for ($i = 0; $i < WhatsAppOnboardingOtpService::SENDS_PER_WINDOW; $i++) {
            RateLimiter::hit($key, WhatsAppOnboardingOtpService::WINDOW_SECONDS);
        }

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappSendFailed', true)
            ->assertSet('whatsappOtpDisplay', '')
            ->assertSee('Please wait')
            ->assertSee('before requesting another code')
            ->assertSee('Retry')
            ->assertDontSee('Your verification code');

        $this->assertSame([], $this->outbound());
        $this->assertSame('', (string) session('wizard_wa_otp.code'));
        $this->assertStringNotContainsString('verification code is:', $component->html());
    }

    public function test_other_message_types_stay_blocked_by_the_demo_only_guard(): void
    {
        $this->configureWhatsApp();
        $phone = self::PHONE;
        $service = app(WhatsAppBusinessService::class);

        $marks = $service->sendText($phone, 'Marks are ready', 'marks_release', $this->admin->id);
        $this->assertTrue($marks['blocked'] ?? false);
        $this->assertSame('demo_only_recipients', $marks['reason']);

        $fee = $service->sendText($phone, 'Fee reminder', 'fee_reminder', $this->admin->id);
        $this->assertTrue($fee['blocked'] ?? false);
        $this->assertSame('demo_only_recipients', $fee['reason']);

        $wrongBody = DemoOnlyRecipientGuard::duringAdminVerification(
            (int) $this->admin->id,
            $phone,
            fn () => app(WhatsAppBusinessService::class)->sendText(
                $phone,
                'Your KlassApp verification code is: 123456. It expires in 5 minutes. Marks are ready.',
                DemoOnlyRecipientGuard::ADMIN_VERIFICATION,
                $this->admin->id,
            ),
        );
        $this->assertTrue($wrongBody['blocked'] ?? false);

        $during = DemoOnlyRecipientGuard::duringAdminVerification(
            (int) $this->admin->id,
            $phone,
            fn () => app(WhatsAppBusinessService::class)->sendText($phone, 'Marks are ready', 'marks_release', $this->admin->id),
        );
        $this->assertTrue($during['blocked'] ?? false);

        $unscoped = app(WhatsAppBusinessService::class)->sendText(
            $phone,
            'Your KlassApp verification code is: 123456. It expires in 5 minutes.',
            DemoOnlyRecipientGuard::ADMIN_VERIFICATION,
            $this->admin->id,
        );
        $this->assertTrue($unscoped['blocked'] ?? false);

        $otherNumber = DemoOnlyRecipientGuard::duringAdminVerification(
            (int) $this->admin->id,
            $phone,
            fn () => app(WhatsAppBusinessService::class)->sendText(
                '+256700000099',
                'Your KlassApp verification code is: 123456. It expires in 5 minutes.',
                DemoOnlyRecipientGuard::ADMIN_VERIFICATION,
                $this->admin->id,
            ),
        );
        $this->assertTrue($otherNumber['blocked'] ?? false);

        $teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'OTP Teacher',
            'email' => 'teacher@otp.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        $asTeacher = DemoOnlyRecipientGuard::duringAdminVerification(
            (int) $teacher->id,
            $phone,
            fn () => app(WhatsAppBusinessService::class)->sendText(
                $phone,
                'Your KlassApp verification code is: 123456. It expires in 5 minutes.',
                DemoOnlyRecipientGuard::ADMIN_VERIFICATION,
                $teacher->id,
            ),
        );
        $this->assertTrue($asTeacher['blocked'] ?? false);

        $this->assertSame([], $this->outbound());
    }

    private function asProduction(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);
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

    private function advanceToWhatsAppStep()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'OTP Academy')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-OTP')
            ->call('next')
            ->call('next') // UNEB centre
            ->call('next') // academic year
            ->call('next') // structure
            ->call('next') // subjects, lands on teachers
            ->call('skipOptionalStep')
            ->call('next') // terms
            ->call('next'); // fees

        $this->assertSame('whatsapp_verify', $component->get('steps')[$component->get('stepIndex')]['key'] ?? null);

        return $component;
    }

    private function assertResponseHidesCode($component, string $code): void
    {
        $this->assertStringNotContainsString($code, (string) $component->get('whatsappOtpDisplay'));
        $this->assertStringNotContainsString($code, (string) $component->get('whatsappOtpStatus'));
        $this->assertStringNotContainsString($code, (string) $component->get('errorMessage'));
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
