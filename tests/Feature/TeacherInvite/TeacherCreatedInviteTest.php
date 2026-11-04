<?php

namespace Tests\Feature\TeacherInvite;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Logging\ScrubInviteTokensProcessor;
use App\Mail\TeacherInviteLinkMail;
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
use App\Services\OnboardingEngine;
use App\Services\TeacherAccountInviteService;
use App\Services\TeacherInviteLinkService;
use App\Traits\RegisterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\TestCase;

/**
 * Teachers created by the school get a setup link and no password.
 * Brand-new class-teacher invites that do not create a row yet stay on
 * TeacherInviteLinkService, unchanged.
 */
class TeacherCreatedInviteTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    private StandardLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);

        Mail::fake();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Invite Created School',
            'email' => 'office@invite-created.test',
            'phone' => '+256700111000',
            'slug' => 'invite-created',
            'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
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

        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Ada Admin',
            'email' => 'ada.admin@invite-created.test',
            'status' => 'active',
        ]);
    }

    public function test_onboarding_import_sends_a_setup_link_and_sets_no_password(): void
    {
        $logged = '';
        Log::listen(function ($event) use (&$logged): void {
            $logged .= $event->message.json_encode($event->context);
        });

        $this->actingAs($this->admin);
        app(OnboardingEngine::class)->saveTeachers($this->school, $this->year, [[
            'name' => 'John Teacher',
            'email' => 'john.teacher@example.test',
        ]]);

        $teacher = User::query()->where('email', 'john.teacher@example.test')->first();
        $this->assertNotNull($teacher);
        $this->assertNull($teacher->password);
        $this->assertSame(0, (int) $teacher->is_reset);
        $this->assertFalse(Auth::attempt(['email' => 'john.teacher@example.test', 'password' => 'Setup1pass']));

        $token = $this->queuedToken('john.teacher@example.test');
        $this->assertStringNotContainsString($token, $logged);

        $logger = new Logger('t091');
        $handler = new TestHandler();
        $logger->pushHandler($handler);
        $logger->pushProcessor(new ScrubInviteTokensProcessor());
        $logger->info('Opened /invite/teacher/'.$token, ['invite_url' => url('/invite/teacher/'.$token)]);
        $encoded = json_encode($handler->getRecords());
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($token, $encoded);

        $this->post(route('teacher.invite.claim', $token), [
            'password' => 'Setup1pass',
            'password_confirmation' => 'Setup1pass',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Auth::attempt(['email' => 'john.teacher@example.test', 'password' => 'Setup1pass']));
        $this->assertSame('claimed', TeacherInviteLinkService::validateToken($token)['error']);
    }

    public function test_placeholder_emails_get_no_invite_and_no_password(): void
    {
        $generated = 'teacher.'.Str::lower(Str::random(8)).'@'.$this->school->slug.'.test';
        $local = 'nameless@'.$this->school->slug.'.local';

        app(OnboardingEngine::class)->saveTeachers($this->school, $this->year, [
            ['name' => 'Placeholder Teacher', 'email' => $generated],
            ['name' => 'Local Teacher', 'email' => $local],
        ]);

        foreach ([$generated, $local] as $email) {
            $teacher = User::query()->where('email', $email)->first();
            $this->assertNotNull($teacher);
            $this->assertNull($teacher->password);
        }

        Mail::assertNothingQueued();
        $this->assertSame(0, TeacherInvite::query()->count());

        $this->actingAs($this->admin)
            ->get('/admin/teacher/show/'.rawurlencode('Placeholder Teacher'))
            ->assertOk()
            ->assertSee('This teacher has no email yet. Add one before they can be invited.')
            ->assertDontSee('Resend setup link');
    }

    public function test_admin_add_teacher_and_spreadsheet_import_send_a_setup_link(): void
    {
        $this->actingAs($this->admin);
        $harness = new class
        {
            use RegisterUser;
        };

        $user = $harness->CreateTeacher((object) [
            'name' => 'Form Teacher',
            'email' => 'form.teacher@example.test',
            'mobile_no' => '+256700222333',
            'firstname' => 'Form',
            'lastname' => 'Teacher',
            'gender' => 'female',
            'date_of_birth' => '1991-02-02',
            'blood_group' => null,
            'address' => 'Kampala',
            'city_id' => null,
            'country_id' => null,
            'pincode' => null,
            'aadhar_number' => null,
            'joining_date' => '2026-01-10',
            'notes' => null,
            'marital_status' => null,
            'qualification_id' => null,
            'ug_degree' => null,
            'pg_degree' => null,
            'specialization' => null,
            'designation' => 'teacher',
            'sub_designation' => null,
            'employee_id' => 'EMP900',
            'job_type' => null,
            'interested_in' => null,
            'reporting_to' => null,
        ], $this->school->id, $this->year, '', 5);

        $this->assertNull($user->password);
        $this->assertFalse(Auth::attempt(['email' => 'form.teacher@example.test', 'password' => 'Setup1pass']));
        Mail::assertQueued(TeacherInviteLinkMail::class, function (TeacherInviteLinkMail $mail): bool {
            return str_contains($mail->inviteUrl, '/invite/teacher/');
        });
    }

    public function test_resend_revokes_the_previous_link_and_is_hidden_after_sign_in(): void
    {
        $this->actingAs($this->admin);
        app(OnboardingEngine::class)->saveTeachers($this->school, $this->year, [[
            'name' => 'Resend Teacher',
            'email' => 'resend.teacher@example.test',
        ]]);
        $teacher = User::query()->where('email', 'resend.teacher@example.test')->firstOrFail();
        $first = TeacherInvite::query()->where('email', 'resend.teacher@example.test')->firstOrFail();
        $firstHash = $first->token_hash;

        $this->get('/admin/teachers')->assertOk()->assertSee('Resend setup link');
        $this->post('/admin/teacher/show/'.$teacher->name.'/resend-setup')
            ->assertRedirect('/admin/teacher/show/'.$teacher->name);

        $this->assertNotNull($first->fresh()->revoked_at);
        $open = TeacherInvite::query()
            ->where('email', 'resend.teacher@example.test')
            ->whereNull('revoked_at')
            ->whereNull('claimed_at')
            ->first();
        $this->assertNotNull($open);
        $this->assertNotSame($firstHash, $open->token_hash);
        Mail::assertQueued(TeacherInviteLinkMail::class, 2);

        ActivityLog::query()->create([
            'log_name' => 'login',
            'description' => 'Logged In',
            'causer_id' => $teacher->id,
            'causer_type' => User::class,
            'school_id' => $teacher->school_id,
        ]);

        $this->get('/admin/teacher/show/'.rawurlencode($teacher->name))
            ->assertOk()
            ->assertDontSee('Resend setup link');
        $this->post('/admin/teacher/show/'.$teacher->name.'/resend-setup')
            ->assertRedirect()
            ->assertSessionHas('errormessage', 'This teacher has already signed in.');
    }

    public function test_assigning_a_never_signed_in_teacher_sends_the_setup_link(): void
    {
        $teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Never Signed In',
            'email' => 'never.signed@example.test',
            'password' => null,
            'status' => 'active',
        ]);

        $result = app(ClassTeacherAssignmentService::class)->assign($this->admin, $this->link, [
            'confirmed' => true,
            'email' => 'never.signed@example.test',
            'existing_teacher_id' => $teacher->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame($teacher->id, (int) $this->link->fresh()->class_teacher_id);
        Mail::assertQueued(TeacherInviteLinkMail::class);
        Mail::assertNotQueued(TeacherInviteMail::class);
        $this->assertNull($teacher->fresh()->password);
    }

    public function test_assigning_a_signed_in_teacher_sends_only_the_notice(): void
    {
        $teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Signed In Teacher',
            'email' => 'signed.in@example.test',
            'status' => 'active',
        ]);
        ActivityLog::query()->create([
            'log_name' => 'login',
            'description' => 'Logged In',
            'causer_id' => $teacher->id,
            'causer_type' => User::class,
            'school_id' => $teacher->school_id,
        ]);

        app(ClassTeacherAssignmentService::class)->assign($this->admin, $this->link, [
            'confirmed' => true,
            'email' => 'signed.in@example.test',
            'existing_teacher_id' => $teacher->id,
        ]);

        Mail::assertQueued(TeacherInviteMail::class, function (TeacherInviteMail $mail): bool {
            return $mail->existingAccount === true && $mail->email === 'signed.in@example.test';
        });
        Mail::assertNotQueued(TeacherInviteLinkMail::class);
    }

    public function test_resend_is_rate_limited_by_the_signed_in_admins_school(): void
    {
        config([
            'invites.force_rate_limits' => true,
            'invites.create_attempts' => 1,
            'invites.create_per_invite_attempts' => 10,
        ]);

        $this->actingAs($this->admin);
        $teacher = $this->makeTeacher('limited.teacher@example.test', 'Limited Teacher');
        $otherSchool = School::create([
            'name' => 'Other Invite School',
            'email' => 'office@other-invite.test',
            'phone' => '+256700111999',
            'slug' => 'other-invite',
            'status' => 1,
        ]);
        $otherAdmin = User::factory()->create([
            'school_id' => $otherSchool->id,
            'usergroup_id' => 3,
            'name' => 'Other Admin',
            'email' => 'other.admin@example.test',
            'status' => 'active',
        ]);
        $otherTeacher = User::factory()->create([
            'school_id' => $otherSchool->id,
            'usergroup_id' => 5,
            'name' => 'Other Teacher',
            'email' => 'other.teacher@example.test',
            'password' => null,
            'status' => 'active',
        ]);

        $this->clearIssueLimit((int) $this->school->id);
        $this->clearIssueLimit((int) $otherSchool->id);

        $this->post('/admin/teacher/show/'.rawurlencode($teacher->name).'/resend-setup')->assertSessionHas('successmessage');
        $this->post('/admin/teacher/show/'.rawurlencode($teacher->name).'/resend-setup', ['school_id' => $otherSchool->id])
            ->assertSessionHas('errormessage', 'Too many setup links. Wait an hour and try again.');

        $this->actingAs($otherAdmin)
            ->post('/admin/teacher/show/'.rawurlencode($otherTeacher->name).'/resend-setup')
            ->assertSessionHas('successmessage');
    }

    public function test_bad_invite_tokens_are_limited_per_ip(): void
    {
        config([
            'invites.force_rate_limits' => true,
            'invites.accept_invalid_attempts' => 2,
        ]);

        $ip = '198.51.100.20';
        RateLimiter::clear(md5('teacher-invite-accept'.ClassTeacherAssignmentService::INVALID_ACCEPT_KEY_PREFIX.$ip));

        $first = Str::random(TeacherInviteLinkService::TOKEN_LENGTH);
        $second = Str::random(TeacherInviteLinkService::TOKEN_LENGTH);
        $third = Str::random(TeacherInviteLinkService::TOKEN_LENGTH);

        $this->withServerVariables(['REMOTE_ADDR' => $ip]);
        $this->get('/invite/teacher/'.$first)->assertOk();
        $this->get('/invite/teacher/'.$second)->assertOk();
        $this->get('/invite/teacher/'.$third)->assertStatus(429);

        $other = Str::random(TeacherInviteLinkService::TOKEN_LENGTH);
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.21'])
            ->get('/invite/teacher/'.$other)
            ->assertOk();
    }

    public function test_an_email_that_already_has_a_signed_in_account_is_not_reset(): void
    {
        $existing = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Already Teaching',
            'email' => 'already.teaching@example.test',
            'status' => 'active',
        ]);
        $hash = $existing->password;
        ActivityLog::query()->create([
            'log_name' => 'login',
            'description' => 'Logged In',
            'causer_id' => $existing->id,
            'causer_type' => User::class,
            'school_id' => $existing->school_id,
        ]);

        $issued = TeacherInviteLinkService::issue($this->school, 'already.teaching@example.test', 'Already Teaching');
        $this->post(route('teacher.invite.claim', $issued['token']), [
            'password' => 'Setup1pass',
            'password_confirmation' => 'Setup1pass',
        ])->assertSessionHas('error', TeacherInviteLinkService::EMAIL_ALREADY_REGISTERED);

        $this->assertSame($hash, $existing->fresh()->password);
    }

    private function queuedToken(string $email): string
    {
        foreach (Mail::queued(TeacherInviteLinkMail::class) as $mail) {
            $candidate = basename((string) parse_url($mail->inviteUrl, PHP_URL_PATH));
            $row = TeacherInvite::query()->where('token_hash', hash('sha256', $candidate))->first();
            if ($row !== null && $row->email === $email) {
                return $candidate;
            }
        }

        $this->fail('No setup link was queued for '.$email);
    }

    private function makeTeacher(string $email, string $name): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => $name,
            'email' => $email,
            'password' => null,
            'status' => 'active',
        ]);
    }

    private function clearIssueLimit(int $schoolId): void
    {
        RateLimiter::clear(md5('teacher-invite-issue'.'school:'.$schoolId));
    }
}
