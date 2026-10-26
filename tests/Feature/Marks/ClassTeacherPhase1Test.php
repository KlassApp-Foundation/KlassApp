<?php

namespace Tests\Feature\Marks;

use App\Logging\ScrubInviteTokensProcessor;
use App\Mail\TeacherInviteMail;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Services\ClassTeacherAssignmentService;
use App\Services\ClassTeacherInviteService;
use App\Services\TeacherInviteLinkService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\TestCase;

class ClassTeacherPhase1Test extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Section $section;

    private StandardLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['invites.staff_whatsapp_enabled' => false]);

        $this->school = School::create([
            'name' => 'Phase One School',
            'slug' => 'phase-one-school',
            'email' => 'school@phase-one.test',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Amina Admin',
            'email' => 'admin@phase-one.test',
            'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);
        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'status' => 1,
            'stream' => 'A',
        ]);
    }

    public function test_assignment_requires_confirmation_and_writes_one_log(): void
    {
        $teacher = $this->teacher('grace@phase-one.test');

        $refused = ClassTeacherInviteService::invite($this->admin, $this->link, [
            'email' => 'grace@phase-one.test',
            'existing_teacher_id' => $teacher->id,
        ]);
        $this->assertFalse($refused['success']);
        $this->assertSame('Confirm this assignment before it is saved.', $refused['message']);
        $this->assertNull($this->link->fresh()->class_teacher_id);

        $result = app(ClassTeacherAssignmentService::class)->assign($this->admin, $this->link, [
            'confirmed' => true,
            'email' => 'grace@phase-one.test',
            'existing_teacher_id' => $teacher->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame($teacher->id, $this->link->fresh()->class_teacher_id);
        $this->assertSame($teacher->id, $this->section->fresh()->class_teacher_id);
        $this->assertSame(1, ActivityLog::query()->where('log_name', 'class_teacher')->count());
        $this->assertSame($this->school->id, (int) ActivityLog::query()->where('log_name', 'class_teacher')->value('school_id'));
    }

    public function test_invite_stores_only_a_hash_and_expires_in_72_hours(): void
    {
        $result = $this->inviteNew('new.teacher@phase-one.test', 'Nora New');
        $this->assertTrue($result['success']);

        $invite = TeacherInvite::query()->where('email', 'new.teacher@phase-one.test')->first();
        $this->assertNotNull($invite);
        $this->assertSame(64, strlen((string) $invite->token_hash));
        $this->assertFalse($invite->expires_at->isPast());
        $this->assertTrue($invite->expires_at->between(now()->addHours(71), now()->addHours(73)));

        $raw = TeacherInvite::query()->where('token_hash', 'like', '%new.teacher%')->count();
        $this->assertSame(0, $raw);
        $this->assertDatabaseMissing('users', ['email' => 'new.teacher@phase-one.test']);
    }

    public function test_token_is_single_use_and_an_expired_token_is_refused(): void
    {
        ['token' => $token] = TeacherInviteLinkService::issue(
            $this->school,
            'once@phase-one.test',
            'Once Teacher',
            $this->link,
        );

        $claim = TeacherInviteLinkService::claim(
            TeacherInvite::query()->where('email', 'once@phase-one.test')->first(),
            'Secret1a',
        );
        $this->assertTrue($claim['success']);

        $again = TeacherInviteLinkService::validateToken($token);
        $this->assertSame('claimed', $again['error']);

        $expired = TeacherInviteLinkService::issue(
            $this->school,
            'old@phase-one.test',
            'Old Teacher',
            $this->link,
        );
        $expired['invite']->expires_at = now()->subHour();
        $expired['invite']->save();

        $checked = TeacherInviteLinkService::validateToken($expired['token']);
        $this->assertSame('expired', $checked['error']);
        $this->assertFalse(TeacherInviteLinkService::claim($expired['invite']->fresh(), 'Secret1a')['success']);
    }

    public function test_replacing_a_class_teacher_revokes_the_pending_invite(): void
    {
        ['token' => $token] = TeacherInviteLinkService::issue(
            $this->school,
            'pending@phase-one.test',
            'Pending Teacher',
            $this->link,
        );
        $invite = TeacherInvite::query()->where('email', 'pending@phase-one.test')->first();

        $replacement = $this->teacher('replace@phase-one.test');
        $result = app(ClassTeacherAssignmentService::class)->assign($this->admin, $this->link, [
            'confirmed' => true,
            'email' => 'replace@phase-one.test',
            'existing_teacher_id' => $replacement->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($invite->fresh()->revoked_at);
        $this->assertSame('revoked', TeacherInviteLinkService::validateToken($token)['error']);

        $this->actingAs($this->admin)
            ->get(route('teacher.invite.form', $token))
            ->assertOk()
            ->assertSee('This invite was replaced')
            ->assertDontSee($token);
    }

    public function test_invite_token_is_scrubbed_from_logs_and_exception_messages(): void
    {
        $token = 'abcDEF1234567890abcDEF1234567890abcDEF1234567890abcDEF1234567890';
        $logger = new Logger('phase1');
        $handler = new TestHandler();
        $logger->pushHandler($handler);
        $logger->pushProcessor(new ScrubInviteTokensProcessor());

        $logger->error('Opened /invite/teacher/'.$token, [
            'token' => $token,
            'invite_url' => 'https://klassapp.test/invite/teacher/'.$token,
            'exception' => new \RuntimeException('claim failed /invite/teacher/'.$token),
        ]);

        $record = $handler->getRecords()[0];
        $encoded = json_encode([
            $record->message,
            $record->context,
            $record->context['exception']->getMessage(),
        ]);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($token, $encoded);
        $this->assertStringContainsString('[redacted]', $encoded);
    }

    public function test_invite_page_sends_no_referrer(): void
    {
        ['token' => $token] = TeacherInviteLinkService::issue(
            $this->school,
            'page@phase-one.test',
            'Page Teacher',
            $this->link,
        );

        $this->get(route('teacher.invite.form', $token))
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('name="referrer" content="no-referrer"', false)
            ->assertSee('Set your password');
    }

    public function test_invite_creation_and_acceptance_are_rate_limited(): void
    {
        config([
            'invites.force_rate_limits' => true,
            'invites.create_attempts' => 2,
            'invites.accept_attempts' => 2,
        ]);
        RateLimiter::clear('teacher-invite-create:user:'.$this->admin->id);

        $this->assertTrue($this->inviteNew('one@phase-one.test', 'One Teacher')['success']);
        $this->assertTrue($this->inviteNew('two@phase-one.test', 'Two Teacher')['success']);
        $blocked = $this->inviteNew('three@phase-one.test', 'Three Teacher');
        $this->assertFalse($blocked['success']);
        $this->assertStringContainsString('Too many', $blocked['message']);

        ['token' => $token] = TeacherInviteLinkService::issue(
            $this->school,
            'accept@phase-one.test',
            'Accept Teacher',
            $this->link,
        );
        $ip = '203.0.113.50';
        RateLimiter::clear('teacher-invite-accept:'.hash('sha256', $ip));

        $this->withServerVariables(['REMOTE_ADDR' => $ip]);
        $this->get(route('teacher.invite.form', $token))->assertOk();
        $this->get(route('teacher.invite.form', $token))->assertOk();
        $this->get(route('teacher.invite.form', $token))
            ->assertStatus(429)
            ->assertSee('Too many attempts')
            ->assertDontSee($token);
    }

    public function test_existing_teacher_notice_names_who_assigned_them_and_the_class(): void
    {
        $teacher = $this->teacher('notice@phase-one.test');
        app(ClassTeacherAssignmentService::class)->assign($this->admin, $this->link, [
            'confirmed' => true,
            'email' => 'notice@phase-one.test',
            'existing_teacher_id' => $teacher->id,
        ]);

        Mail::assertQueued(TeacherInviteMail::class, function (TeacherInviteMail $mail) {
            return $mail->existingAccount === true
                && $mail->assignedBy === 'Amina Admin'
                && $mail->className === 'P.1';
        });

        $html = Mail::queued(TeacherInviteMail::class)->first()->render();
        $this->assertStringContainsString('Amina Admin', $html);
        $this->assertStringContainsString('P.1', $html);
        $this->assertStringContainsString('If this is a mistake', $html);
        $this->assertStringNotContainsString('Your password is', $html);
    }

    public function test_whatsapp_invite_stays_off_unless_the_flag_is_enabled_for_a_fake(): void
    {
        Http::preventStrayRequests();

        $sent = $this->inviteNew('wa-off@phase-one.test', 'Wa Off', '0777000111');
        $this->assertTrue($sent['success']);
        $this->assertStringContainsString('via email', $sent['message']);
        $this->assertStringNotContainsString('WhatsApp', $sent['message']);

        config(['invites.staff_whatsapp_enabled' => true]);
        $this->mock(WhatsAppBusinessService::class, function ($mock): void {
            $mock->shouldReceive('sendText')->once()->andReturn([]);
        });

        TeacherInviteLinkService::sendWhatsApp(
            TeacherInvite::query()->where('email', 'wa-off@phase-one.test')->first(),
            'raw-token-for-fake-only',
            $this->school,
            'P.1',
        );
    }

    public function test_retired_class_pages_redirect_and_the_class_page_links_the_forms(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/sections')
            ->assertRedirect(route('admin.classes.index'));

        $this->actingAs($this->admin)
            ->get('/admin/class-setup')
            ->assertRedirect(route('admin.classes.index'));

        $this->actingAs($this->admin)
            ->get('/admin/classes/create')
            ->assertOk()
            ->assertSee('P.1')
            ->assertDontSee('Primary One');

        $this->actingAs($this->admin)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Assign class teacher')
            ->assertSee('Edit stream');
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function inviteNew(string $email, string $name, string $phone = ''): array
    {
        return ClassTeacherInviteService::invite($this->admin, $this->link, [
            'confirmed' => true,
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
        ]);
    }

    private function teacher(string $email): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Grace Teacher',
            'email' => $email,
            'status' => 'active',
        ]);
    }
}
