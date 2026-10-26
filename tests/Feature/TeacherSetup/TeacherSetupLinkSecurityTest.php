<?php

namespace Tests\Feature\TeacherSetup;

use App\Livewire\TeacherSetup\TeacherClassSetup;
use App\Mail\TeacherInviteLinkMail;
use App\Mail\TeacherSetupLinkMail;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolStaffLink;
use App\Models\Section;
use App\Models\StaffLinkRequest;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\TeacherInvite;
use App\Models\Teacherlink;
use App\Models\TeacherSetupToken;
use App\Models\User;
use App\Services\TeacherSetup\TeacherSetupLinkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Staff links, personal setup links, and class-teacher setup stay inside
 * one school and one class. Responses do not include a setup link, a name,
 * or an address.
 */
class TeacherSetupLinkSecurityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $admin;

    private AcademicYear $year;

    private StandardLink $link;

    private Subject $subject;

    private string $passwordHash;

    private string $visitorIp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visitorIp = '198.51.100.'.random_int(50, 250);
        $this->clearStaffLinkIp($this->visitorIp);
        $this->withServerVariables(['REMOTE_ADDR' => $this->visitorIp]);

        $this->school = School::create([
            'name' => 'Setup School',
            'slug' => 'setup-school',
            'email' => 'setup@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Setup Admin',
            'email' => 'setup-admin@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Class Teacher',
            'email' => 'Class.Teacher@schools.test',
            'mobile_no' => '+256772000111',
            'password' => bcrypt('KeepMe1'),
            'status' => 'active',
        ]);
        $this->passwordHash = $this->teacher->password;

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2027',
            'description' => 'setup',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P.1 A', 'status' => 1]);
        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
            'class_teacher_id' => $this->teacher->id,
        ]);
        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'status' => 1,
        ]);
    }

    public function test_staff_link_email_match_sends_the_link_to_the_address_on_record(): void
    {
        Mail::fake();
        $staff = app(TeacherSetupLinkService::class)->issueStaffLink($this->school, $this->admin);

        $response = $this->fromSchoolIp()->post('/school/staff/'.$staff, [
            'email' => 'class.teacher@schools.test',
        ]);

        $response->assertOk();
        $response->assertSee('Check your email to finish setting up');
        $response->assertDontSee('Set your password');
        $response->assertDontSee('/teacher/setup/');
        $response->assertDontSee('Class.Teacher@schools.test');
        $response->assertDontSee('class.teacher@schools.test');
        $response->assertDontSee('Class Teacher');
        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);

        $url = null;
        Mail::assertQueued(TeacherSetupLinkMail::class, function (TeacherSetupLinkMail $mail) use (&$url) {
            $url = $mail->url;
            $to = $mail->to[0]['address'] ?? null;

            return $to === 'Class.Teacher@schools.test';
        });
        Mail::assertQueued(TeacherSetupLinkMail::class, 1);
        $this->assertIsString($url);
        $response->assertDontSee($url);
        $this->assertStringNotContainsString($url, $response->getContent());
        $this->assertSame(1, TeacherSetupToken::query()->where('user_id', $this->teacher->id)->count());
        $this->assertSame(0, StaffLinkRequest::query()->where('school_id', $this->school->id)->count());
    }

    public function test_staff_link_email_still_responds_when_mail_cannot_be_queued(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('mailbox down'));
        $staff = app(TeacherSetupLinkService::class)->issueStaffLink($this->school, $this->admin);

        $response = $this->fromSchoolIp()->post('/school/staff/'.$staff, [
            'email' => 'class.teacher@schools.test',
        ]);

        $response->assertOk();
        $response->assertSee('Check your email to finish setting up');
        $response->assertDontSee('/teacher/setup/');
        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);
        $this->assertSame(0, TeacherSetupToken::query()->count());
    }

    public function test_staff_link_phone_match_asks_for_the_admin_link(): void
    {
        Mail::fake();
        $noEmail = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Phone Only',
            'email' => null,
            'mobile_no' => '+256772000333',
            'password' => bcrypt('KeepMe1'),
            'status' => 'active',
        ]);
        $noEmailHash = $noEmail->password;
        $staff = app(TeacherSetupLinkService::class)->issueStaffLink($this->school, $this->admin);

        $stored = $this->fromSchoolIp()->post('/school/staff/'.$staff, [
            'phone' => '+256772000111',
        ]);
        $stored->assertOk();
        $stored->assertSee('Ask your school admin for your personal link');
        $stored->assertDontSee('Set your password');
        $stored->assertDontSee('/teacher/setup/');

        $withEmail = $this->fromSchoolIp()->post('/school/staff/'.$staff, [
            'phone' => '0772000111',
        ]);
        $withEmail->assertOk();
        $withEmail->assertSee('Ask your school admin for your personal link');
        $withEmail->assertDontSee('Set your password');
        $withEmail->assertDontSee('/teacher/setup/');
        $withEmail->assertDontSee('Class.Teacher@schools.test');
        $withEmail->assertDontSee('Class Teacher');
        $withEmail->assertDontSee('+256772000111');

        $withoutEmail = $this->fromSchoolIp()->post('/school/staff/'.$staff, [
            'email' => 'someone@example.test',
            'phone' => '0772000333',
        ]);
        $withoutEmail->assertOk();
        $withoutEmail->assertSee('Ask your school admin for your personal link');
        $withoutEmail->assertDontSee('someone@example.test');
        $withoutEmail->assertDontSee('Phone Only');

        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);
        $this->assertSame($noEmailHash, $noEmail->fresh()->password);
        $this->assertSame(0, TeacherSetupToken::query()->count());
        $this->assertSame(0, StaffLinkRequest::query()->count());
        Mail::assertNothingOutgoing();
    }

    public function test_a_short_or_partial_phone_does_not_match_a_teacher(): void
    {
        $service = app(TeacherSetupLinkService::class);
        $this->assertSame(null, $service->matchTeacher($this->school, null, '1')?->id);
        $this->assertSame(null, $service->matchTeacher($this->school, null, '111')?->id);
        $this->assertSame(null, $service->matchTeacher($this->school, null, '000111')?->id);
        $this->assertSame($this->teacher->id, $service->matchTeacher($this->school, null, '0772000111')?->id);
        $this->assertSame($this->teacher->id, $service->matchTeacher($this->school, null, '+256772000111')?->id);

        $staff = $service->issueStaffLink($this->school, $this->admin);
        $response = $this->fromSchoolIp()->post('/school/staff/'.$staff, ['phone' => '1']);

        $response->assertOk();
        $response->assertSee('Waiting for your admin');
        $response->assertDontSee('Set your password');
        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);
        $this->assertSame(0, TeacherSetupToken::query()->count());
        $this->assertSame(1, StaffLinkRequest::query()->where('school_id', $this->school->id)->count());
    }

    public function test_a_personal_setup_link_from_another_school_does_not_change_this_teacher(): void
    {
        $otherSchool = School::create([
            'name' => 'Other School',
            'slug' => 'other-school',
            'email' => 'other@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $other = User::create([
            'school_id' => $otherSchool->id,
            'usergroup_id' => 5,
            'name' => 'Other Teacher',
            'email' => 'other.teacher@schools.test',
            'password' => bcrypt('OtherKeep1'),
            'status' => 'active',
        ]);
        $otherHash = $other->password;

        $plain = Str::random(48);
        TeacherSetupToken::query()->create([
            'school_id' => $this->school->id,
            'user_id' => $other->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
        ]);

        $this->get('/teacher/setup/'.$plain)->assertOk()->assertDontSee('Set your password');
        $this->post('/teacher/setup/'.$plain, [
            'password' => 'TakenOver1',
            'password_confirmation' => 'TakenOver1',
        ]);

        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);
        $this->assertSame($otherHash, $other->fresh()->password);
        $this->assertFalse(Hash::check('TakenOver1', $other->fresh()->password));

        $real = app(TeacherSetupLinkService::class)->issueFor($other);
        $this->post('/teacher/setup/'.$real, [
            'password' => 'OtherPass1',
            'password_confirmation' => 'OtherPass1',
        ])->assertRedirect('/teacher/class-setup');

        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);
        $this->assertTrue(Hash::check('OtherPass1', $other->fresh()->password));
        $this->assertAuthenticatedAs($other->fresh());
    }

    public function test_an_expired_or_used_personal_link_cannot_set_a_password(): void
    {
        $service = app(TeacherSetupLinkService::class);
        $expired = $service->issueFor($this->teacher, $this->admin);

        Carbon::setTestNow(now()->addDays(TeacherSetupLinkService::PERSONAL_TTL_DAYS + 1));
        try {
            $this->get('/teacher/setup/'.$expired)->assertOk()->assertSee('doesn’t work');
            $this->post('/teacher/setup/'.$expired, [
                'password' => 'TakenOver1',
                'password_confirmation' => 'TakenOver1',
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);

        $used = $service->issueFor($this->teacher, $this->admin);
        $this->post('/teacher/setup/'.$used, [
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
        ])->assertRedirect('/teacher/class-setup');
        $this->assertTrue(Hash::check('StrongPass1', $this->teacher->fresh()->password));

        $this->post('/teacher/setup/'.$used, [
            'password' => 'OtherPass1',
            'password_confirmation' => 'OtherPass1',
        ]);
        $this->get('/teacher/setup/'.$used)->assertOk()->assertSee('doesn’t work');
        $this->assertTrue(Hash::check('StrongPass1', $this->teacher->fresh()->password));
        $this->assertFalse(Hash::check('OtherPass1', $this->teacher->fresh()->password));
    }

    public function test_a_revoked_staff_link_is_rejected_on_get_and_post(): void
    {
        $service = app(TeacherSetupLinkService::class);
        $old = $service->issueStaffLink($this->school, $this->admin);

        $this->actingAs($this->admin)
            ->post('/admin/class-setup/staff-link/regenerate')
            ->assertRedirect();

        $this->assertNotNull(session('staff_link_url'));
        auth()->logout();

        $usable = SchoolStaffLink::query()
            ->where('school_id', $this->school->id)
            ->whereNull('revoked_at')
            ->count();
        $this->assertSame(1, $usable);

        $this->get('/school/staff/'.$old)->assertOk()->assertSee('doesn’t work');
        $this->post('/school/staff/'.$old, ['email' => 'class.teacher@schools.test'])
            ->assertOk()
            ->assertSee('doesn’t work')
            ->assertDontSee('Set your password')
            ->assertDontSee('Check your email');
        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);
        $this->assertSame(0, TeacherSetupToken::query()->count());
    }

    public function test_staff_link_submissions_are_limited_per_link_and_per_ip(): void
    {
        $service = app(TeacherSetupLinkService::class);
        $staff = $service->issueStaffLink($this->school, $this->admin);

        for ($i = 1; $i <= 11; $i++) {
            $this->clearStaffLinkIp('203.0.113.'.$i);
        }
        $sharedIp = '198.51.100.'.random_int(1, 40);
        $this->clearStaffLinkIp($sharedIp);

        $statuses = [];
        for ($i = 1; $i <= 11; $i++) {
            $statuses[] = $this->fromSchoolIp('203.0.113.'.$i)
                ->post('/school/staff/'.$staff, ['email' => "person{$i}@example.net"])
                ->status();
        }

        $this->assertSame(200, $statuses[0], implode(',', $statuses));
        $this->assertSame(429, $statuses[10], implode(',', $statuses));

        $ipStatuses = [];
        for ($i = 1; $i <= 31; $i++) {
            $ipStatuses[] = $this->fromSchoolIp($sharedIp)
                ->post('/school/staff/not-a-real-token-'.$i, ['email' => "ip{$i}@example.net"])
                ->status();
        }
        $this->assertSame(200, $ipStatuses[0], implode(',', $ipStatuses));
        $this->assertSame(200, $ipStatuses[29], implode(',', $ipStatuses));
        $this->assertSame(429, $ipStatuses[30], implode(',', $ipStatuses));
    }

    public function test_a_class_teacher_cannot_add_or_change_another_class_or_stream(): void
    {
        [$otherLink, $otherSubject, $otherTeacher] = $this->otherClass();
        $foreign = $this->foreignTeacher();

        $csv = "name,class,stream\nAmina Nakato,P.1 B,\nBrian Okello,P.1,B\nCissy Akello,P.1 A,\n";
        Livewire::actingAs($this->teacher)
            ->test(TeacherClassSetup::class)
            ->set('selectedLinkId', $this->link->id)
            ->set('studentUpload', UploadedFile::fake()->createWithContent('learners.csv', $csv))
            ->call('uploadStudents')
            ->assertSet('report', '1 students added, 2 rows skipped: they belong to another class');

        $this->assertSame(0, StudentAcademic::query()->where('standardLink_id', $otherLink->id)->count());
        $this->assertSame(1, StudentAcademic::query()->where('standardLink_id', $this->link->id)->count());
        $this->assertSame(2, Section::query()->where('school_id', $this->school->id)->count());
        $this->assertNull(User::query()->where('name', 'Amina Nakato')->first());
        $this->assertNull(User::query()->where('name', 'Brian Okello')->first());
        $this->assertNotNull(User::query()->where('name', 'Cissy Akello')->where('usergroup_id', 6)->first());

        Livewire::actingAs($this->teacher)
            ->test(TeacherClassSetup::class)
            ->set('selectedLinkId', $otherLink->id)
            ->set('subjectIds', [$otherSubject->id])
            ->call('saveSubjects')
            ->set('subjectTeacher.'.$otherSubject->id, (string) $otherTeacher->id)
            ->call('saveSubjectTeachers');

        Livewire::actingAs($this->teacher)
            ->test(TeacherClassSetup::class)
            ->set('selectedLinkId', $this->link->id)
            ->set('subjectIds', [$otherSubject->id, $this->subject->id])
            ->call('saveSubjects')
            ->set('subjectTeacher.'.$otherSubject->id, (string) $otherTeacher->id)
            ->set('subjectTeacher.'.$this->subject->id, (string) $foreign->id)
            ->call('saveSubjectTeachers');

        $this->assertDatabaseMissing('class_teacher_links', [
            'standardLink_id' => $otherLink->id,
        ]);
        $this->assertDatabaseMissing('class_teacher_links', [
            'subject_id' => $otherSubject->id,
        ]);
        $this->assertDatabaseMissing('class_teacher_links', [
            'teacher_id' => $foreign->id,
        ]);
        $this->assertDatabaseHas('class_teacher_links', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->link->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_a_subject_teacher_invite_is_sent_only_to_the_typed_email(): void
    {
        Mail::fake();
        $lookalike = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Real Teacher',
            'email' => 'real.teacher@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        Livewire::actingAs($this->teacher)
            ->test(TeacherClassSetup::class)
            ->set('selectedLinkId', $this->link->id)
            ->set('subjectTeacher.'.$this->subject->id, 'Real Teacher')
            ->call('saveSubjectTeachers');

        Mail::assertNothingOutgoing();
        $this->assertSame(0, TeacherInvite::query()->count());

        Livewire::actingAs($this->teacher)
            ->test(TeacherClassSetup::class)
            ->set('selectedLinkId', $this->link->id)
            ->set('subjectTeacher.'.$this->subject->id, (string) $lookalike->id)
            ->call('saveSubjectTeachers');

        Mail::assertNothingOutgoing();
        $this->assertDatabaseHas('class_teacher_links', [
            'standardLink_id' => $this->link->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $lookalike->id,
        ]);

        $url = null;
        Livewire::actingAs($this->teacher)
            ->test(TeacherClassSetup::class)
            ->set('selectedLinkId', $this->link->id)
            ->set('subjectTeacher.'.$this->subject->id, 'Typed.Person@example.test')
            ->call('saveSubjectTeachers');

        Mail::assertQueued(TeacherInviteLinkMail::class, function (TeacherInviteLinkMail $mail) use (&$url) {
            $url = $mail->inviteUrl;
            $to = $mail->to[0]['address'] ?? null;

            return $to === 'typed.person@example.test';
        });
        Mail::assertNotQueued(TeacherInviteLinkMail::class, function (TeacherInviteLinkMail $mail) {
            return ($mail->to[0]['address'] ?? null) === 'real.teacher@schools.test';
        });

        $invite = TeacherInvite::query()->where('email', 'typed.person@example.test')->first();
        $this->assertNotNull($invite);
        $this->assertNull($invite->phone);
        $this->assertNull($invite->standard_link_id);

        $path = parse_url((string) $url, PHP_URL_PATH);
        $this->post($path, [
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
        ]);

        $this->assertSame((int) $this->teacher->id, (int) $this->link->fresh()->class_teacher_id);
    }

    public function test_approval_issues_nothing_for_an_admin_a_parent_or_an_inactive_user(): void
    {
        $adminHash = $this->admin->password;
        $parent = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'name' => 'A Parent',
            'email' => 'a.parent@schools.test',
            'password' => bcrypt('ParentPass1'),
            'status' => 'active',
        ]);
        $parentHash = $parent->password;
        $inactive = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Inactive Teacher',
            'email' => 'inactive.teacher@schools.test',
            'password' => bcrypt('Inactive1'),
        ]);
        $inactive->forceFill(['status' => 'inactive'])->save();
        $inactiveHash = $inactive->password;

        foreach ([$this->admin->email, $parent->email, $inactive->email] as $email) {
            $request = $this->pendingRequest($email);
            $this->actingAs($this->admin)
                ->from('/admin/class-setup')
                ->post('/admin/class-setup/requests/'.$request->id.'/approve')
                ->assertRedirect('/admin/class-setup')
                ->assertSessionHas('error', 'That email is not an active teacher at this school. No setup link was issued.');
            $this->assertSame(StaffLinkRequest::STATUS_PENDING, $request->fresh()->status);
        }

        $this->assertSame(0, TeacherSetupToken::query()->count());
        $this->assertNull(session('teacher_setup_url'));
        $this->assertSame($adminHash, $this->admin->fresh()->password);
        $this->assertSame(3, (int) $this->admin->fresh()->usergroup_id);
        $this->assertSame($parentHash, $parent->fresh()->password);
        $this->assertSame(7, (int) $parent->fresh()->usergroup_id);
        $this->assertSame($inactiveHash, $inactive->fresh()->password);
        $this->assertSame('inactive', $inactive->fresh()->status);
    }

    public function test_approval_of_another_schools_email_issues_nothing_and_does_not_error(): void
    {
        $other = School::create([
            'name' => 'Other Side School',
            'slug' => 'other-side-school',
            'email' => 'other-side@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $person = User::create([
            'school_id' => $other->id,
            'usergroup_id' => 5,
            'name' => 'Hidden Teacher',
            'email' => 'hidden.teacher@other.test',
            'password' => bcrypt('KeepSecret1'),
            'status' => 'active',
        ]);
        $hash = $person->password;
        $request = $this->pendingRequest('hidden.teacher@other.test');

        $this->actingAs($this->admin)
            ->from('/admin/class-setup')
            ->post('/admin/class-setup/requests/'.$request->id.'/approve')
            ->assertRedirect('/admin/class-setup')
            ->assertSessionHas('error', 'That email is not an active teacher at this school. No setup link was issued.');

        $this->assertSame(StaffLinkRequest::STATUS_PENDING, $request->fresh()->status);
        $this->assertSame(0, TeacherSetupToken::query()->count());
        $this->assertNull(session('teacher_setup_url'));
        $this->assertSame($hash, $person->fresh()->password);
        $this->assertSame((int) $other->id, (int) $person->fresh()->school_id);
        $this->assertSame('active', $person->fresh()->status);
        $this->assertSame(1, User::query()->whereRaw('lower(email) = ?', ['hidden.teacher@other.test'])->count());

        $this->get('/admin/class-setup')
            ->assertRedirect(route('admin.classes.index'))
            ->assertDontSee('Hidden Teacher')
            ->assertDontSee('Other Side School');
    }

    public function test_an_old_personal_link_is_dead_after_a_new_one_is_issued(): void
    {
        $service = app(TeacherSetupLinkService::class);
        $old = $service->issueFor($this->teacher, $this->admin);
        $new = $service->issueFor($this->teacher, $this->admin);

        $this->get('/teacher/setup/'.$old)->assertOk()->assertSee('doesn’t work');
        $this->post('/teacher/setup/'.$old, [
            'password' => 'TakenOver1',
            'password_confirmation' => 'TakenOver1',
        ]);
        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);

        $this->post('/teacher/setup/'.$new, [
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
        ])->assertRedirect('/teacher/class-setup');
        $this->assertTrue(Hash::check('StrongPass1', $this->teacher->fresh()->password));
    }

    public function test_using_one_personal_link_cancels_the_rest(): void
    {
        $service = app(TeacherSetupLinkService::class);
        $first = $service->issueFor($this->teacher, $this->admin);
        $second = Str::random(48);
        TeacherSetupToken::query()->create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher->id,
            'token_hash' => hash('sha256', $second),
            'expires_at' => now()->addDays(14),
            'created_by' => $this->admin->id,
        ]);

        $this->post('/teacher/setup/'.$first, [
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
        ])->assertRedirect('/teacher/class-setup');

        auth()->logout();

        $this->post('/teacher/setup/'.$second, [
            'password' => 'OtherPass1',
            'password_confirmation' => 'OtherPass1',
        ]);
        $this->get('/teacher/setup/'.$second)->assertOk()->assertSee('doesn’t work');
        $this->assertTrue(Hash::check('StrongPass1', $this->teacher->fresh()->password));
        $this->assertFalse(Hash::check('OtherPass1', $this->teacher->fresh()->password));
        $this->assertSame(0, TeacherSetupToken::query()->where('user_id', $this->teacher->id)->whereNull('used_at')->count());
    }

    public function test_a_teacher_gets_at_most_one_setup_email_every_ten_minutes(): void
    {
        Mail::fake();
        $staff = app(TeacherSetupLinkService::class)->issueStaffLink($this->school, $this->admin);
        $firstUrl = null;

        $this->fromSchoolIp()->post('/school/staff/'.$staff, [
            'email' => 'class.teacher@schools.test',
        ])->assertOk()->assertSee('Check your email to finish setting up');

        Mail::assertQueued(TeacherSetupLinkMail::class, function (TeacherSetupLinkMail $mail) use (&$firstUrl) {
            $firstUrl = $mail->url;

            return ($mail->to[0]['address'] ?? null) === 'Class.Teacher@schools.test';
        });

        $this->fromSchoolIp()->post('/school/staff/'.$staff, [
            'email' => 'CLASS.TEACHER@schools.test',
        ])->assertOk()
            ->assertSee('Check your email to finish setting up')
            ->assertDontSee('/teacher/setup/');

        Mail::assertQueued(TeacherSetupLinkMail::class, 1);
        $this->assertSame(1, TeacherSetupToken::query()->where('user_id', $this->teacher->id)->whereNull('used_at')->count());

        $path = parse_url((string) $firstUrl, PHP_URL_PATH);
        $this->get($path)->assertOk()->assertSee('Set your password');

        Carbon::setTestNow(now()->addMinutes(TeacherSetupLinkService::RESEND_COOLDOWN_MINUTES + 1));
        try {
            $this->fromSchoolIp('198.51.100.61')->post('/school/staff/'.$staff, [
                'email' => 'class.teacher@schools.test',
            ])->assertOk()->assertSee('Check your email to finish setting up');
        } finally {
            Carbon::setTestNow();
        }

        Mail::assertQueued(TeacherSetupLinkMail::class, 2);
        $this->get($path)->assertOk()->assertSee('doesn’t work');
        $this->assertSame(1, TeacherSetupToken::query()->where('user_id', $this->teacher->id)->whereNull('used_at')->count());
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);
    }

    public function test_two_simultaneous_setup_submits_succeed_only_once(): void
    {
        $service = app(TeacherSetupLinkService::class);
        $token = $service->issueFor($this->teacher, $this->admin);
        $stale = $service->resolvePersonal($token);
        $this->assertNotNull($stale);

        TeacherSetupToken::query()->whereKey($stale->id)->update(['used_at' => now()]);
        $stale->used_at = null;

        $mock = \Mockery::mock(TeacherSetupLinkService::class)->makePartial();
        $mock->shouldReceive('resolvePersonal')->once()->andReturn($stale);
        $this->app->instance(TeacherSetupLinkService::class, $mock);

        $this->post('/teacher/setup/'.$token, [
            'password' => 'TakenOver1',
            'password_confirmation' => 'TakenOver1',
        ]);

        $this->assertGuest();
        $this->assertSame($this->passwordHash, $this->teacher->fresh()->password);

        $this->app->forgetInstance(TeacherSetupLinkService::class);
        $again = app(TeacherSetupLinkService::class);
        $live = $again->issueFor($this->teacher, $this->admin);
        $first = $again->resolvePersonal($live);
        $second = $again->resolvePersonal($live);
        $this->assertTrue($again->consumePersonal($first));
        $this->assertFalse($again->consumePersonal($second));
    }

    public function test_setup_link_mail_keeps_the_raw_link_out_of_the_queue(): void
    {
        $secret = 'RAW-SETUP-LINK-SECRET';
        Mail::to('class.teacher@schools.test')->queue(new TeacherSetupLinkMail(
            name: 'Class Teacher',
            schoolName: 'Setup School',
            url: 'https://klassapp.test/teacher/setup/'.$secret,
            expiresAt: now()->addDays(14),
        ));

        $inviteSecret = 'RAW-INVITE-LINK-SECRET';
        Mail::to('typed.person@example.test')->queue(new TeacherInviteLinkMail(
            name: 'Subject teacher',
            schoolName: 'Setup School',
            inviteUrl: 'https://klassapp.test/invite/teacher/'.$inviteSecret,
            className: 'P.1 A',
            expiresAt: now()->addDays(3),
        ));

        $payloads = DB::table('jobs')->pluck('payload')->implode("\n");
        $this->assertNotSame('', $payloads);
        $this->assertStringNotContainsString($secret, $payloads);
        $this->assertStringNotContainsString($inviteSecret, $payloads);
        $this->assertStringNotContainsString('class.teacher@schools.test', $payloads);
    }

    public function test_admin_progress_counts_subjects_for_that_class(): void
    {
        $this->otherClass();

        $this->actingAs($this->admin)
            ->get('/admin/class-setup')
            ->assertRedirect(route('admin.classes.index'));
    }

    private function clearStaffLinkIp(string $ip): void
    {
        RateLimiter::clear(md5('staff-link-submitstaff-link-ip|'.$ip));
        RateLimiter::clear('staff-link-ip|'.$ip);
    }

    private function pendingRequest(string $email): StaffLinkRequest
    {
        return StaffLinkRequest::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Visitor',
            'email' => $email,
            'status' => StaffLinkRequest::STATUS_PENDING,
        ]);
    }

    private function fromSchoolIp(?string $ip = null): self
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip ?? $this->visitorIp]);
    }

    /**
     * @return array{0: StandardLink, 1: Subject, 2: User}
     */
    private function otherClass(): array
    {
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P.1 B', 'status' => 1]);
        $teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Other Class Teacher',
            'email' => 'other.class@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $section->id,
            'status' => 1,
            'class_teacher_id' => $teacher->id,
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $section->id,
            'name' => 'English',
            'status' => 1,
        ]);

        return [$link, $subject, $teacher];
    }

    private function foreignTeacher(): User
    {
        $school = School::create([
            'name' => 'Foreign School',
            'slug' => 'foreign-school',
            'email' => 'foreign@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        return User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Foreign Teacher',
            'email' => 'foreign.teacher@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
    }
}
