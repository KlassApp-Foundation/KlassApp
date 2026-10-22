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

    public function test_wizard_shows_a_code_and_does_not_send_one(): void
    {
        $this->assertSame(0, (int) $this->school->fresh()->is_demo);
        $this->configureWhatsApp();

        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', self::PHONE)
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappSendFailed', false)
            ->assertSet('errorMessage', '');

        Http::assertNothingSent();
        $code = (string) $component->get('whatsappOtpDisplay');
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $code);
        $component->assertSee('https://wa.me/256793844906?text='.rawurlencode('Verify KlassApp: '.$code), false);
        $this->assertSame('', (string) session('wizard_wa_otp.code'));

        // Toshi still sends a code. The demo-only exception stays limited to that body.
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
        $this->assertCount(1, $bodies);
        $this->assertStringContainsString('654321', $bodies[0]['body']);
        $log = MessageDeliveryLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame('WhatsApp verification code', $log->content_preview);
        $this->assertStringNotContainsString('654321', (string) $log->content_preview);
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
