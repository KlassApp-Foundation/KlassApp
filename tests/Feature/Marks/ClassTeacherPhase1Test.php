<?php

namespace Tests\Feature\Marks;

use App\Logging\ScrubInviteTokensProcessor;
use App\Mail\TeacherInviteMail;
use App\Models\AcademicYear;
use App\Models\Academics\Classes;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $token = Str::random(64);
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

    public function test_issuing_one_invite_does_not_throttle_a_different_invite_or_school(): void
    {
        config([
            'invites.force_rate_limits' => true,
            'invites.create_attempts' => 100,
            'invites.create_per_invite_attempts' => 1,
        ]);
        $this->clearIssueLimits((int) $this->school->id);

        $email = 'one@phase-one.test';
        $this->clearIssueLimits((int) $this->school->id, hash('sha256', $email));
        $this->assertTrue($this->inviteNew($email, 'One Teacher')['success']);

        $again = $this->inviteNew($email, 'One Teacher');
        $this->assertFalse($again['success']);
        $this->assertStringContainsString('Too many', $again['message']);
        $this->assertSame(1, TeacherInvite::query()->where('school_id', $this->school->id)->where('email', $email)->count());

        $otherEmail = 'two@phase-one.test';
        $this->clearIssueLimits((int) $this->school->id, hash('sha256', $otherEmail));
        $this->assertTrue($this->inviteNew($otherEmail, 'Two Teacher')['success']);

        $other = $this->otherSchool();
        $this->clearIssueLimits((int) $other['school']->id, hash('sha256', $email));
        $elsewhere = ClassTeacherInviteService::invite($other['admin'], $other['link'], [
            'confirmed' => true,
            'email' => $email,
            'name' => 'One Teacher',
        ]);
        $this->assertTrue($elsewhere['success'], $elsewhere['message'] ?? '');
    }

    public function test_issuing_is_limited_per_school_and_a_different_school_is_not(): void
    {
        config([
            'invites.force_rate_limits' => true,
            'invites.create_attempts' => 2,
            'invites.create_per_invite_attempts' => 100,
        ]);
        $this->clearIssueLimits((int) $this->school->id);

        $this->assertTrue($this->inviteNew('school-a-one@phase-one.test', 'One Teacher')['success']);
        $this->assertTrue($this->inviteNew('school-a-two@phase-one.test', 'Two Teacher')['success']);
        $blocked = $this->inviteNew('school-a-three@phase-one.test', 'Three Teacher');
        $this->assertFalse($blocked['success']);
        $this->assertStringContainsString('Too many', $blocked['message']);

        $other = $this->otherSchool();
        $this->clearIssueLimits((int) $other['school']->id);
        $elsewhere = ClassTeacherInviteService::invite($other['admin'], $other['link'], [
            'confirmed' => true,
            'email' => 'school-b@phase-one.test',
            'name' => 'Other School Teacher',
        ]);
        $this->assertTrue($elsewhere['success'], $elsewhere['message'] ?? '');
    }

    public function test_accepting_one_invite_does_not_throttle_a_different_invite_or_school(): void
    {
        config([
            'invites.force_rate_limits' => true,
            'invites.accept_attempts' => 2,
            'invites.accept_school_attempts' => 100,
        ]);

        ['token' => $token, 'invite' => $invite] = TeacherInviteLinkService::issue(
            $this->school,
            'accept@phase-one.test',
            'Accept Teacher',
            $this->link,
        );
        ['token' => $otherToken, 'invite' => $otherInvite] = TeacherInviteLinkService::issue(
            $this->school,
            'accept-other@phase-one.test',
            'Other Accept',
            $this->link,
        );
        $foreign = $this->otherSchool();
        ['token' => $foreignToken, 'invite' => $foreignInvite] = TeacherInviteLinkService::issue(
            $foreign['school'],
            'accept-foreign@phase-one.test',
            'Foreign Accept',
            $foreign['link'],
        );

        $this->clearAcceptLimits((int) $this->school->id, (string) $invite->id);
        $this->clearAcceptLimits((int) $this->school->id, (string) $otherInvite->id);
        $this->clearAcceptLimits((int) $foreign['school']->id, (string) $foreignInvite->id);

        $ip = '203.0.113.50';
        $this->withServerVariables(['REMOTE_ADDR' => $ip]);
        $this->get(route('teacher.invite.form', $token))->assertOk();
        $this->get(route('teacher.invite.form', $token))->assertOk();
        $this->get(route('teacher.invite.form', $token))
            ->assertStatus(429)
            ->assertSee('Too many attempts')
            ->assertDontSee($token);

        $this->withServerVariables(['REMOTE_ADDR' => $ip]);
        $this->get(route('teacher.invite.form', $otherToken))
            ->assertOk()
            ->assertSee('Set your password');
        $this->get(route('teacher.invite.form', $foreignToken))
            ->assertOk()
            ->assertSee('Set your password');
    }

    public function test_accepting_is_limited_per_school_and_a_different_school_is_not(): void
    {
        config([
            'invites.force_rate_limits' => true,
            'invites.accept_attempts' => 100,
            'invites.accept_school_attempts' => 2,
        ]);

        ['token' => $first] = TeacherInviteLinkService::issue(
            $this->school,
            'school-cap-one@phase-one.test',
            'Cap One',
            $this->link,
        );
        ['token' => $second] = TeacherInviteLinkService::issue(
            $this->school,
            'school-cap-two@phase-one.test',
            'Cap Two',
            $this->link,
        );
        ['token' => $third] = TeacherInviteLinkService::issue(
            $this->school,
            'school-cap-three@phase-one.test',
            'Cap Three',
            $this->link,
        );
        $foreign = $this->otherSchool();
        ['token' => $foreignToken] = TeacherInviteLinkService::issue(
            $foreign['school'],
            'school-cap-foreign@phase-one.test',
            'Cap Foreign',
            $foreign['link'],
        );

        $this->clearAcceptLimits((int) $this->school->id);
        $this->clearAcceptLimits((int) $foreign['school']->id);

        $this->get(route('teacher.invite.form', $first))->assertOk();
        $this->get(route('teacher.invite.form', $second))->assertOk();
        $this->get(route('teacher.invite.form', $third))
            ->assertStatus(429)
            ->assertSee('Too many attempts')
            ->assertDontSee($third);

        $this->get(route('teacher.invite.form', $foreignToken))
            ->assertOk()
            ->assertSee('Set your password');
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
            ->assertOk()
            ->assertSee('P.1')
            ->assertSee('Class setup');

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

    public function test_store_accepts_the_schools_class_name_and_rejects_any_other_name(): void
    {
        $standard = Standard::query()->where('school_id', $this->school->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.classes.store'), [
                'standard_id' => $standard->id,
                'name' => 'P.1',
            ])
            ->assertRedirect(route('admin.classes.add'));

        $names = app(ClassTeacherAssignmentService::class)->namesForSchool((int) $this->school->id);
        $this->assertLessThan(array_search('P.2', $names, true), array_search('P.1', $names, true));

        $this->assertDatabaseHas('classes', [
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'name' => 'P.1',
            'position' => array_search('P.1', $names, true) + 1,
        ]);

        $other = School::create([
            'name' => 'Other Name School',
            'slug' => 'other-name-school',
            'email' => 'other@phase-one.test',
            'status' => 'active',
        ]);
        $foreignStandard = Standard::create([
            'school_id' => $other->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.classes.add'))
            ->post(route('admin.classes.store'), [
                'standard_id' => $standard->id,
                'name' => 'Primary 1',
            ])
            ->assertRedirect(route('admin.classes.add'))
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->from(route('admin.classes.add'))
            ->post(route('admin.classes.store'), [
                'standard_id' => $foreignStandard->id,
                'name' => 'P.1',
            ])
            ->assertRedirect(route('admin.classes.add'))
            ->assertSessionHasErrors('standard_id');

        $this->assertSame(1, Classes::query()->where('school_id', $this->school->id)->count());
    }

    public function test_stream_form_rejects_a_teacher_id_that_is_not_in_the_school(): void
    {
        $foreignSchool = School::create([
            'name' => 'Foreign Phase School',
            'slug' => 'foreign-phase-school',
            'email' => 'foreign@phase-one.test',
            'status' => 'active',
        ]);
        $foreignTeacher = User::factory()->create([
            'school_id' => $foreignSchool->id,
            'usergroup_id' => 5,
            'email' => 'foreign-teacher@phase-one.test',
            'status' => 'active',
        ]);
        $year = AcademicYear::query()->where('school_id', $this->school->id)->firstOrFail();
        $standard = Standard::query()->where('school_id', $this->school->id)->firstOrFail();
        $controller = app(\App\Http\Controllers\Admin\SectionController::class);

        Auth::logout();
        $loggedOut = Request::create('/', 'POST', [
            'class_teacher_id' => $this->teacher('signed-out@phase-one.test')->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'standard_name' => '1',
            'no_of_students' => 10,
            'count' => 0,
        ]);

        try {
            $controller->createStandardLink($this->school->id, $year->id, $loggedOut);
            $this->fail('A signed-out assignment should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('class_teacher_id', $e->errors());
        }

        Auth::setUser($this->admin);
        $invalid = Request::create('/', 'POST', [
            'class_teacher_id' => $foreignTeacher->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'standard_name' => '1',
            'no_of_students' => 10,
            'count' => 0,
        ]);

        try {
            $controller->createStandardLink($this->school->id, $year->id, $invalid);
            $this->fail('A teacher from another school should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('class_teacher_id', $e->errors());
        }

        $this->assertNull($this->link->fresh()->class_teacher_id);
        $this->assertDatabaseMissing('standards_link', [
            'class_teacher_id' => $foreignTeacher->id,
        ]);

        $edit = Request::create('/', 'POST', [
            'class_teacher_id' => $this->admin->id,
            'no_of_students' => 12,
            'standard' => '1',
            'count' => 0,
        ]);

        try {
            $controller->editStandardLink($this->school->id, $year->id, $this->link->id, $edit);
            $this->fail('A non-teacher id should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('class_teacher_id', $e->errors());
        }

        $this->assertNull($this->link->fresh()->class_teacher_id);
    }

    public function test_store_accepts_a_new_level_and_sorts_by_level_not_alphabetically(): void
    {
        foreach (['P.2', 'P.6', 'P.10'] as $name) {
            Section::create([
                'school_id' => $this->school->id,
                'name' => $name,
                'status' => 1,
            ]);
        }

        $names = app(ClassTeacherAssignmentService::class)->namesForSchool((int) $this->school->id);
        $this->assertContains('P.7', $names);
        $this->assertLessThan(array_search('P.2', $names, true), array_search('P.1', $names, true));
        $this->assertLessThan(array_search('P.7', $names, true), array_search('P.6', $names, true));
        $this->assertLessThan(array_search('P.10', $names, true), array_search('P.7', $names, true));
        $this->assertLessThan(array_search('P.10', $names, true), array_search('P.2', $names, true));

        $standard = Standard::query()->where('school_id', $this->school->id)->firstOrFail();
        $this->actingAs($this->admin)
            ->post(route('admin.classes.store'), [
                'standard_id' => $standard->id,
                'name' => 'P.7',
            ])
            ->assertRedirect(route('admin.classes.add'));

        $this->assertDatabaseHas('classes', [
            'school_id' => $this->school->id,
            'name' => 'P.7',
            'position' => array_search('P.7', $names, true) + 1,
        ]);
    }

    public function test_saving_the_stream_form_with_the_same_class_teacher_keeps_the_pending_invite(): void
    {
        $teacher = $this->teacher('same@phase-one.test');
        $this->link->class_teacher_id = $teacher->id;
        $this->link->save();
        $this->section->class_teacher_id = $teacher->id;
        $this->section->save();

        ['token' => $token] = TeacherInviteLinkService::issue(
            $this->school,
            'pending-same@phase-one.test',
            'Pending Same',
            $this->link,
        );
        $invite = TeacherInvite::query()->where('email', 'pending-same@phase-one.test')->firstOrFail();
        $hash = $invite->token_hash;
        $logs = ActivityLog::query()->where('log_name', 'class_teacher')->count();
        $inviteCount = TeacherInvite::query()->where('school_id', $this->school->id)->count();

        $year = AcademicYear::query()->where('school_id', $this->school->id)->firstOrFail();
        Auth::setUser($this->admin);
        $edit = Request::create('/', 'POST', [
            'class_teacher_id' => $teacher->id,
            'no_of_students' => 40,
            'standard' => '1',
            'count' => 0,
        ]);

        app(\App\Http\Controllers\Admin\SectionController::class)
            ->editStandardLink($this->school->id, $year->id, $this->link->id, $edit);

        $fresh = $invite->fresh();
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->revoked_at);
        $this->assertSame($hash, $fresh->token_hash);
        $this->assertSame($invite->id, $fresh->id);
        $this->assertSame($inviteCount, TeacherInvite::query()->where('school_id', $this->school->id)->count());
        $this->assertSame($logs, ActivityLog::query()->where('log_name', 'class_teacher')->count());
        $this->assertSame($teacher->id, (int) $this->link->fresh()->class_teacher_id);
        $checked = TeacherInviteLinkService::validateToken($token);
        $this->assertNull($checked['error']);
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

    /**
     * @return array{school: School, admin: User, link: StandardLink}
     */
    private function otherSchool(): array
    {
        $suffix = Str::lower(Str::random(8));
        $school = School::create([
            'name' => 'Second Phase School',
            'slug' => 'second-phase-'.$suffix,
            'email' => 'second-'.$suffix.'@phase-one.test',
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Second Admin',
            'email' => 'second-admin-'.$suffix.'@phase-one.test',
            'status' => 'active',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
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
            'name' => 'P.2',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'status' => 1,
        ]);

        return ['school' => $school, 'admin' => $admin, 'link' => $link];
    }

    private function clearIssueLimits(int $schoolId, ?string $inviteKey = null): void
    {
        RateLimiter::clear(md5('teacher-invite-issue'.'school:'.$schoolId));
        if ($inviteKey !== null) {
            RateLimiter::clear(md5('teacher-invite-issue'.'school:'.$schoolId.'|invite:'.$inviteKey));
        }
    }

    private function clearAcceptLimits(int $schoolId, ?string $inviteKey = null): void
    {
        RateLimiter::clear(md5('teacher-invite-accept'.'school:'.$schoolId));
        if ($inviteKey !== null) {
            RateLimiter::clear(md5('teacher-invite-accept'.'school:'.$schoolId.'|invite:'.$inviteKey));
        }
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
