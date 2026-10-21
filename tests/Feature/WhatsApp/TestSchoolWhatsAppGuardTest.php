<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppPendingNotification;
use App\Models\WhatsAppUser;
use App\Services\DemoOnlyRecipientGuard;
use App\Services\DemoSchoolCommsGuard;
use App\Services\OutboundWhatsAppService;
use App\Services\TestSchoolCommsGuard;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Outbound WhatsApp to an is_test school stays off even when demo-only
 * sending is off. A real school still sends. The admin verification code
 * to that admin's own number still sends.
 */
class TestSchoolWhatsAppGuardTest extends TestCase
{
    use RefreshDatabase;

    private const PARENT = '+256770111001';

    private const WA_ONLY = '+256770111002';

    private const REAL = '+256770111003';

    private const ADMIN = '+256770111004';

    private const DEMO = '+256770111005';

    private int $schoolPhone = 1000000;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '106540352242922',
            'services.whatsapp.business_api_version' => 'v21.0',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.graph_api_base_url' => 'https://graph.facebook.com',
        ]);
        TestSchoolCommsGuard::flushCache();
        DemoSchoolCommsGuard::flushCache();
        DemoOnlyRecipientGuard::flush();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]], 200),
        ]);
    }

    protected function tearDown(): void
    {
        TestSchoolCommsGuard::flushCache();
        DemoSchoolCommsGuard::flushCache();
        DemoOnlyRecipientGuard::flush();
        parent::tearDown();
    }

    public function test_test_school_parent_is_blocked_on_every_send_method_when_demo_only_recipients_is_off(): void
    {
        $school = $this->makeSchool('Test Parent School', isTest: true);
        $parent = $this->makeUser($school, 'parent@test-parent.sch.ug', 7, self::PARENT);
        WhatsAppUser::create([
            'phone' => self::PARENT,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
        ]);
        TestSchoolCommsGuard::flushCache();

        $service = app(WhatsAppBusinessService::class);
        $results = [
            $service->sendText(self::PARENT, 'hello', 'service', $parent->id),
            $service->sendTemplate(self::PARENT, 'fee_reminder', ['Ada'], 'en', 'utility'),
            $service->sendInteractiveButtons(self::PARENT, 'Choose', [['id' => 'yes', 'title' => 'Yes']], 'service', $parent->id),
            $service->sendList(self::PARENT, 'Menu', [['title' => 'One', 'rows' => [['id' => 'a', 'title' => 'A']]]], 'Pick', '', 'Open', 'service', $parent->id),
            $service->sendFlow(self::PARENT, 'flow-1', 'token-1', 'SCREEN', 'Open', 'Body', '', '', [], 'service', $parent->id),
            $service->sendDocument(self::PARENT, 'https://example.test/file.pdf', 'report', 'file.pdf', 'service', $parent->id),
        ];

        foreach ($results as $result) {
            $this->assertTrue($result['blocked'] ?? false);
            $this->assertFalse($result['success']);
            $this->assertSame('test_school', $result['reason']);
            $this->assertSame('Outbound messaging is disabled for test schools.', $result['message']);
        }
        Http::assertNothingSent();
    }

    public function test_fee_reminder_and_expired_queue_send_nothing_for_a_test_school(): void
    {
        $school = $this->makeSchool('Test Fee School', isTest: true);
        $student = $this->feeStudent($school, self::PARENT);
        TestSchoolCommsGuard::flushCache();

        $sent = app(OutboundWhatsAppService::class)->notifyFeeReminder($student->id);
        $this->assertSame(0, $sent);
        Http::assertNothingSent();

        $parent = User::query()->where('email', 'fee-parent@test-fee.sch.ug')->firstOrFail();
        $wa = WhatsAppUser::query()->where('user_id', $parent->id)->firstOrFail();
        WhatsAppPendingNotification::create([
            'whatsapp_user_id' => $wa->id,
            'flow_type' => 'service',
            'message' => 'Queued reminder',
            'send_after' => now()->subMinute(),
        ]);

        app(OutboundWhatsAppService::class)->sendExpiredQueue();
        Http::assertNothingSent();
    }

    public function test_number_linked_only_through_whatsapp_users_is_blocked(): void
    {
        $school = $this->makeSchool('Test Link School', isTest: true);
        WhatsAppUser::create([
            'phone' => self::WA_ONLY,
            'user_id' => null,
            'school_id' => $school->id,
            'opted_in' => true,
        ]);

        $member = $this->makeUser($school, 'member@test-link.sch.ug', 7, null);
        WhatsAppUser::create([
            'phone' => '+256770111006',
            'user_id' => $member->id,
            'school_id' => null,
            'opted_in' => true,
        ]);
        TestSchoolCommsGuard::flushCache();

        $service = app(WhatsAppBusinessService::class);
        $bySchool = $service->sendText(self::WA_ONLY, 'hello', 'service');
        $byUser = $service->sendText('+256770111006', 'hello', 'service');

        $this->assertSame('test_school', $bySchool['reason']);
        $this->assertSame($school->id, TestSchoolCommsGuard::blocksWhatsApp(self::WA_ONLY));
        $this->assertSame('test_school', $byUser['reason']);
        $this->assertSame($school->id, TestSchoolCommsGuard::blocksWhatsApp('+256770111006'));
        $this->assertNull(User::query()->where('mobile_no', self::WA_ONLY)->value('id'));
        $this->assertNull(User::query()->where('mobile_no', '+256770111006')->value('id'));
        Http::assertNothingSent();
    }

    public function test_test_school_stays_blocked_when_demo_only_recipients_is_on(): void
    {
        config(['services.whatsapp.demo_only_recipients' => true]);
        $school = $this->makeSchool('Test Flag School', isTest: true);
        $parent = $this->makeUser($school, 'parent@test-flag.sch.ug', 7, self::PARENT);
        TestSchoolCommsGuard::flushCache();

        $result = app(WhatsAppBusinessService::class)->sendText(self::PARENT, 'hello', 'service', $parent->id);

        $this->assertSame('test_school', $result['reason']);
        $this->assertTrue($result['blocked']);
        Http::assertNothingSent();
    }

    public function test_block_log_records_the_school_id_and_not_the_phone(): void
    {
        Log::spy();
        $school = $this->makeSchool('Test Log School', isTest: true);
        $parent = $this->makeUser($school, 'parent@test-log.sch.ug', 7, self::PARENT);
        TestSchoolCommsGuard::flushCache();

        app(WhatsAppBusinessService::class)->sendText(self::PARENT, 'hello', 'service', $parent->id);

        $digits = ltrim(self::PARENT, '+');
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context = []) use ($school, $digits) {
            if ($message !== '[test-school-guard] WhatsApp send blocked') {
                return false;
            }

            $this->assertSame(['school_id' => $school->id], $context);
            $blob = $message.' '.json_encode($context);
            $this->assertStringNotContainsString(self::PARENT, $blob);
            $this->assertStringNotContainsString($digits, $blob);
            $this->assertDoesNotMatchRegularExpression('/\d{6,}/', $blob);

            return true;
        });
        Http::assertNothingSent();
    }

    public function test_real_school_sends_one_request_when_demo_only_recipients_is_off(): void
    {
        $school = $this->makeSchool('Real School', isTest: false);
        $parent = $this->makeUser($school, 'parent@real.sch.ug', 7, self::REAL);
        WhatsAppUser::create([
            'phone' => self::REAL,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
        ]);
        TestSchoolCommsGuard::flushCache();

        $result = app(WhatsAppBusinessService::class)->sendText(self::REAL, 'hello', 'service', $parent->id);

        $this->assertTrue($result['success']);
        Http::assertSentCount(1);
    }

    public function test_admin_verification_code_to_the_admins_own_number_still_sends(): void
    {
        $school = $this->makeSchool('Test Admin School', isTest: true);
        $admin = $this->makeUser($school, 'admin@test-admin.sch.ug', 3, self::ADMIN);
        TestSchoolCommsGuard::flushCache();

        $body = app(WhatsAppOnboardingOtpService::class)->messageBody('654321');
        $result = DemoOnlyRecipientGuard::duringAdminVerification(
            (int) $admin->id,
            self::ADMIN,
            fn () => app(WhatsAppBusinessService::class)->sendText(
                self::ADMIN,
                $body,
                DemoOnlyRecipientGuard::ADMIN_VERIFICATION,
                $admin->id,
            ),
        );

        $this->assertTrue($result['success'] ?? false);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => ($request->data()['to'] ?? null) === ltrim(self::ADMIN, '+')
            && str_contains((string) ($request->data()['text']['body'] ?? ''), '654321'));
    }

    public function test_demo_school_block_log_does_not_include_the_full_phone(): void
    {
        Log::spy();
        $school = $this->makeSchool('Demo Log School', isTest: false, isDemo: true);
        $parent = $this->makeUser($school, 'parent@demo-log.sch.ug', 7, self::DEMO);
        DemoSchoolCommsGuard::flushCache();
        TestSchoolCommsGuard::flushCache();

        $result = app(WhatsAppBusinessService::class)->sendText(self::DEMO, 'hello', 'service', $parent->id);

        $this->assertSame('demo_school', $result['reason']);
        $digits = ltrim(self::DEMO, '+');
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context = []) use ($digits) {
            if (! is_string($message) || ! str_contains($message, '[demo-guard]')) {
                return false;
            }

            $blob = $message.' '.json_encode($context);
            $this->assertStringNotContainsString(self::DEMO, $blob);
            $this->assertStringNotContainsString($digits, $blob);
            $this->assertSame('+256*****005', $context['phone'] ?? null);

            return true;
        });
        Http::assertNothingSent();
    }

    public function test_local_trunk_form_of_the_same_number_is_blocked(): void
    {
        $school = $this->makeSchool('Test Trunk School', isTest: true);
        $this->makeUser($school, 'parent@test-trunk.sch.ug', 7, '0770111001');
        TestSchoolCommsGuard::flushCache();

        $result = app(WhatsAppBusinessService::class)->sendText('+256770111001', 'hello', 'service');

        $this->assertSame('test_school', $result['reason']);
        $this->assertSame($school->id, TestSchoolCommsGuard::blocksWhatsApp('+256770111001'));
        Http::assertNothingSent();
    }

    public function test_a_real_number_that_only_shares_the_last_nine_digits_still_sends(): void
    {
        $test = $this->makeSchool('Suffix Test School', isTest: true);
        $real = $this->makeSchool('Suffix Real School', isTest: false);
        $this->makeUser($test, 'parent@suffix-test.sch.ug', 7, '+256770111222');
        WhatsAppUser::create([
            'phone' => '+256770111222',
            'user_id' => null,
            'school_id' => $test->id,
            'opted_in' => true,
        ]);
        $realParent = $this->makeUser($real, 'parent@suffix-real.sch.ug', 7, '+254770111222');
        TestSchoolCommsGuard::flushCache();

        $result = app(WhatsAppBusinessService::class)->sendText('+254770111222', 'hello', 'service', $realParent->id);

        $this->assertTrue($result['success']);
        $this->assertNull(TestSchoolCommsGuard::blocksWhatsApp('+254770111222', $realParent->id));
        Http::assertSentCount(1);
    }

    public function test_inbound_reply_to_the_same_sender_is_allowed_and_a_queued_send_stays_blocked(): void
    {
        $school = $this->makeSchool('Test Reply School', isTest: true);
        $parent = $this->makeUser($school, 'parent@test-reply.sch.ug', 7, self::PARENT);
        TestSchoolCommsGuard::flushCache();

        DemoOnlyRecipientGuard::beginInbound(self::PARENT);
        try {
            $reply = app(WhatsAppBusinessService::class)->sendText(self::PARENT, 'reply', 'service', $parent->id);
            $this->assertTrue($reply['success'] ?? false);
            Http::assertSentCount(1);

            $queued = DemoSchoolCommsGuard::duringBusinessSend(
                fn () => app(WhatsAppBusinessService::class)->sendText(self::PARENT, 'queued', 'service', $parent->id),
            );
        } finally {
            DemoOnlyRecipientGuard::endInbound();
        }

        $this->assertSame('test_school', $queued['reason']);
        Http::assertSentCount(1);
    }

    public function test_other_messages_during_admin_verification_stay_blocked(): void
    {
        $school = $this->makeSchool('Test Scope School', isTest: true);
        $admin = $this->makeUser($school, 'admin@test-scope.sch.ug', 3, self::ADMIN);
        TestSchoolCommsGuard::flushCache();

        $marks = DemoOnlyRecipientGuard::duringAdminVerification(
            (int) $admin->id,
            self::ADMIN,
            fn () => app(WhatsAppBusinessService::class)->sendText(self::ADMIN, 'Marks are ready', 'marks_release', $admin->id),
        );

        $this->assertSame('test_school', $marks['reason']);
        Http::assertNothingSent();
    }

    private function makeSchool(string $name, bool $isTest, bool $isDemo = false): School
    {
        $school = School::create([
            'name' => $name,
            'slug' => str()->slug($name),
            'email' => str()->slug($name).'@schools.test',
            'phone' => '070'.str_pad((string) $this->schoolPhone++, 7, '0', STR_PAD_LEFT),
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $school->forceFill([
            'is_test' => $isTest ? 1 : 0,
            'is_demo' => $isDemo ? 1 : 0,
        ])->save();

        return $school->refresh();
    }

    private function makeUser(School $school, string $email, int $group, ?string $mobile): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => 'Guard Person',
            'email' => $email,
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        if ($mobile !== null) {
            $user->forceFill(['mobile_no' => $mobile])->save();
        }

        return $user->refresh();
    }

    private function feeStudent(School $school, string $phone): User
    {
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'name' => 'Tuition',
            'amount' => 450000,
        ]);
        $student = $this->makeUser($school, 'fee-student@test-fee.sch.ug', 6, null);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'klassapp_student_id' => sprintf('KLS%07d', $student->id),
        ]);
        $parent = $this->makeUser($school, 'fee-parent@test-fee.sch.ug', 7, $phone);
        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);
        WhatsAppUser::create([
            'phone' => $phone,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'last_inbound_at' => now(),
        ]);

        return $student;
    }
}
