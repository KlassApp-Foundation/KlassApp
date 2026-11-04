<?php

namespace Tests\Feature\Onboarding;

use App\Mail\StudentParentSetupLinkMail;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentParentLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\ParentMagicLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * T-094: an imported parent or student has no password. They get the same
 * one-time setup link a teacher gets, by email, and the admin can resend it
 * from the profile. A parent who never gets an email keeps the WhatsApp
 * magic login.
 */
class StudentParentSetupLinksTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Setup Link School',
            'email' => 'office@setup-link.test',
            'phone' => '+256700600001',
            'slug' => 'setup-link',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->year = AcademicYear::create([
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
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'status' => 'active',
        ]);
    }

    public function test_imported_student_with_an_email_gets_a_setup_link_and_no_password(): void
    {
        $student = $this->importStudent([
            'name' => 'Aisha Namusoke',
            'email' => 'aisha.namusoke@example.test',
        ]);

        $this->assertNull($student->password);
        $this->assertSame(1, (int) $student->is_reset);

        $invite = TeacherInvite::query()
            ->where('school_id', $this->school->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertNotNull($invite);
        $this->assertSame('student', $invite->role);
        $this->assertSame('aisha.namusoke@example.test', $invite->email);
        $this->assertNull($invite->claimed_at);

        Mail::assertQueued(StudentParentSetupLinkMail::class, function (StudentParentSetupLinkMail $mail) use ($student) {
            return $mail->role === 'student'
                && $mail->name === 'Aisha Namusoke'
                && str_contains($mail->inviteUrl, '/invite/account/');
        });
        Mail::assertNotQueued(\App\Mail\TeacherInviteLinkMail::class);

        $mail = Mail::queued(StudentParentSetupLinkMail::class)->first();
        $this->assertStringContainsString($this->tokenFromUrl($mail->inviteUrl), $mail->inviteUrl);
        $this->assertFalse(property_exists($mail, 'password'));
    }

    public function test_imported_student_without_an_email_gets_no_password_and_no_link(): void
    {
        $student = $this->importStudent(['name' => 'Brian Okello']);

        $this->assertNull($student->password);
        $this->assertSame(1, (int) $student->is_reset);
        $this->assertStringEndsWith('.local', (string) $student->email);

        $this->assertSame(0, TeacherInvite::query()->where('user_id', $student->id)->count());
        Mail::assertNothingQueued();
    }

    public function test_imported_parent_has_no_password_and_no_link_without_an_email(): void
    {
        $student = $this->importStudent([
            'name' => 'Joan Kirabo',
            'parent' => 'Sarah Kirabo',
            'parent_phone' => '+256700600111',
        ]);

        $parent = $this->parentOf($student);

        $this->assertNull($parent->password);
        $this->assertSame(1, (int) $parent->is_reset);
        $this->assertSame('Sarah Kirabo', $parent->name);
        $this->assertSame(0, TeacherInvite::query()->where('user_id', $parent->id)->count());
        Mail::assertNothingQueued();
    }

    public function test_imported_parent_with_a_supplied_email_gets_a_setup_link(): void
    {
        $student = $this->importStudent([
            'name' => 'Peter Kirabo',
            'parent' => 'Sarah Kirabo',
            'parent_phone' => '+256700600112',
            'parent_email' => 'sarah.kirabo@example.test',
        ]);

        $parent = $this->parentOf($student);

        $this->assertSame('sarah.kirabo@example.test', $parent->email);
        $this->assertNull($parent->password);

        $invite = TeacherInvite::query()
            ->where('school_id', $this->school->id)
            ->where('user_id', $parent->id)
            ->first();

        $this->assertNotNull($invite);
        $this->assertSame('parent', $invite->role);

        Mail::assertQueued(StudentParentSetupLinkMail::class, function (StudentParentSetupLinkMail $mail) {
            return $mail->role === 'parent' && $mail->name === 'Sarah Kirabo';
        });
    }

    public function test_student_sets_a_password_once_and_the_link_stops_working(): void
    {
        $student = $this->importStudent([
            'name' => 'Aisha Namusoke',
            'email' => 'aisha.namusoke@example.test',
        ]);

        $token = $this->latestTokenFor($student);

        $this->get('/invite/account/'.$token)
            ->assertOk()
            ->assertSee('Set your password')
            ->assertSee('Set password')
            ->assertDontSee('Create account');

        $this->post('/invite/account/'.$token, [
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect('/login')
            ->assertSessionHas('successmessage');

        $student->refresh();
        $this->assertTrue(Hash::check('NewPass123', (string) $student->password));
        $this->assertSame(0, (int) $student->is_reset);

        $invite = TeacherInvite::query()->where('user_id', $student->id)->first();
        $this->assertNotNull($invite->claimed_at);

        $this->get('/invite/account/'.$token)
            ->assertOk()
            ->assertSee('already been used');

        $this->post('/invite/account/'.$token, [
            'password' => 'Another123',
            'password_confirmation' => 'Another123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewPass123', (string) $student->fresh()->password));
    }

    public function test_resend_clears_a_legacy_random_password_on_a_never_set_up_row(): void
    {
        $student = $this->importStudent([
            'name' => 'Aisha Namusoke',
            'email' => 'aisha.namusoke@example.test',
        ]);

        // An older import stored a random hash nobody ever saw.
        $student->forceFill(['password' => bcrypt('never-shown'), 'is_reset' => 1])->save();

        $this->actingAs($this->admin)
            ->post('/admin/students/'.$student->id.'/resend-setup')
            ->assertRedirect('/admin/students/'.$student->id)
            ->assertSessionHas('successmessage');

        $this->assertNull($student->fresh()->password);
        $this->assertSame(1, (int) $student->fresh()->is_reset);
    }

    public function test_admin_resend_on_the_student_profile_revokes_the_old_link(): void
    {
        $student = $this->importStudent([
            'name' => 'Aisha Namusoke',
            'email' => 'aisha.namusoke@example.test',
        ]);

        $oldToken = $this->latestTokenFor($student);

        $this->actingAs($this->admin)
            ->get('/admin/students/'.$student->id)
            ->assertOk()
            ->assertSee('Resend setup link');

        $this->actingAs($this->admin)
            ->post('/admin/students/'.$student->id.'/resend-setup')
            ->assertRedirect('/admin/students/'.$student->id)
            ->assertSessionHas('successmessage', 'A new setup link was sent. The previous link no longer works.');

        $this->assertSame(2, TeacherInvite::query()->where('user_id', $student->id)->count());

        $old = TeacherInvite::query()->where('user_id', $student->id)->orderBy('id')->first();
        $this->assertNotNull($old->revoked_at);

        $this->get('/invite/account/'.$oldToken)
            ->assertOk()
            ->assertSee('This invite was replaced');

        $newToken = $this->latestTokenFor($student);
        $this->assertNotSame($oldToken, $newToken);

        $this->get('/invite/account/'.$newToken)->assertOk()->assertSee('Set your password');
    }

    public function test_admin_resend_on_the_parent_profile_revokes_the_old_link(): void
    {
        $parent = $this->parentWithEmail('sarah.kirabo@example.test', 'Sarah Kirabo');

        $oldToken = $this->latestTokenFor($parent);

        $this->actingAs($this->admin)
            ->get('/admin/parent/show/'.$parent->name)
            ->assertOk()
            ->assertSee('Resend setup link');

        $this->actingAs($this->admin)
            ->post('/admin/parent/show/'.$parent->name.'/resend-setup')
            ->assertRedirect('/admin/parent/show/'.$parent->name)
            ->assertSessionHas('successmessage');

        $this->assertNotNull(
            TeacherInvite::query()->where('user_id', $parent->id)->orderBy('id')->first()->revoked_at
        );

        $this->get('/invite/account/'.$oldToken)->assertOk()->assertSee('This invite was replaced');
        $this->get('/invite/account/'.$this->latestTokenFor($parent))->assertOk()->assertSee('Set your password');
    }

    public function test_resend_refuses_a_parent_without_a_usable_email(): void
    {
        $student = $this->importStudent([
            'name' => 'Joan Kirabo',
            'parent' => 'Sarah Kirabo',
            'parent_phone' => '+256700600113',
        ]);
        $parent = $this->parentOf($student);

        $this->actingAs($this->admin)
            ->post('/admin/parent/show/'.$parent->name.'/resend-setup')
            ->assertRedirect('/admin/parent/show/'.$parent->name)
            ->assertSessionHas('errormessage', 'This parent has no email yet. Add one before they can be invited.');

        $this->assertSame(0, TeacherInvite::query()->where('user_id', $parent->id)->count());
        Mail::assertNothingQueued();
    }

    public function test_parent_without_a_password_can_still_use_the_whatsapp_magic_login(): void
    {
        $student = $this->importStudent([
            'name' => 'Joan Kirabo',
            'parent' => 'Sarah Kirabo',
            'parent_phone' => '+256700600114',
        ]);
        $parent = $this->parentOf($student);
        $this->assertNull($parent->password);

        // The limiter uses the file cache, which outlives one test.
        \Illuminate\Support\Facades\Cache::flush();

        $url = app(ParentMagicLoginService::class)->issueLinkForPhone('+256700600114', $parent);
        $this->assertNotNull($url);

        $this->get($url)->assertOk()->assertSee('Open parent dashboard');

        $this->post(route('parent.magic-login.confirm'))->assertRedirect(route('parent.dashboard'));

        $this->assertAuthenticatedAs($parent);
        $this->assertNull($parent->fresh()->password);
    }

    public function test_resend_stops_at_the_hourly_issuing_cap(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        config([
            'invites.force_rate_limits' => true,
            'invites.create_attempts' => 1,
            'invites.create_per_invite_attempts' => 1,
        ]);

        $student = $this->importStudent([
            'name' => 'Aisha Namusoke',
            'email' => 'aisha.namusoke@example.test',
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/students/'.$student->id.'/resend-setup')
            ->assertRedirect('/admin/students/'.$student->id)
            ->assertSessionHas('successmessage');

        $this->actingAs($this->admin)
            ->post('/admin/students/'.$student->id.'/resend-setup')
            ->assertRedirect('/admin/students/'.$student->id)
            ->assertSessionHas('errormessage', 'Too many setup links. Wait an hour and try again.');

        $this->assertSame(2, TeacherInvite::query()->where('user_id', $student->id)->count());
    }

    public function test_another_schools_student_is_not_resendable(): void
    {
        $otherSchool = School::create([
            'name' => 'Other Setup School',
            'email' => 'office@other-setup.test',
            'phone' => '+256700600099',
            'slug' => 'other-setup',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $otherStudent = User::create([
            'school_id' => $otherSchool->id,
            'usergroup_id' => 6,
            'name' => 'Foreign Student',
            'email' => 'foreign.student@example.test',
            'password' => null,
            'is_reset' => 1,
            'email_verified' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/students/'.$otherStudent->id.'/resend-setup')
            ->assertNotFound();

        $this->assertSame(0, TeacherInvite::query()->where('user_id', $otherStudent->id)->count());
    }

    public function test_teacher_invite_tokens_are_not_accepted_as_a_family_setup_link(): void
    {
        $token = 'teacher-token-'.str_repeat('a', 40);
        TeacherInvite::create([
            'school_id' => $this->school->id,
            'email' => 'teacher.one@example.test',
            'token_hash' => hash('sha256', $token),
            'role' => 'class_teacher',
            'name' => 'Teacher One',
            'expires_at' => now()->addHours(2),
        ]);

        $this->get('/invite/account/'.$token)
            ->assertOk()
            ->assertSee("This invite link doesn't work", false);
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function importStudent(array $draft): User
    {
        $result = app(OnboardingEngine::class)->saveStudents($this->school, $this->year, [$draft]);

        return User::query()->findOrFail((int) $result['created'][0]['user_id']);
    }

    private function parentOf(User $student): User
    {
        $link = StudentParentLink::query()
            ->where('school_id', $this->school->id)
            ->where('student_id', $student->id)
            ->where('status', 1)
            ->firstOrFail();

        return User::query()->findOrFail((int) $link->parent_id);
    }

    private function parentWithEmail(string $email, string $name): User
    {
        $parent = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'name' => $name,
            'email' => $email,
            'password' => null,
            'is_reset' => 1,
            'email_verified' => 1,
            'mobile_no' => null,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'firstname' => $name,
            'lastname' => '',
        ]);

        $issued = app(\App\Services\StudentParentAccountInviteService::class)->inviteIfNeeded($parent, $this->admin);
        $this->assertTrue($issued['invited'], $issued['reason']);

        return $parent->fresh();
    }

    private function latestTokenFor(User $person): string
    {
        $mail = Mail::queued(StudentParentSetupLinkMail::class)
            ->reverse()
            ->first(function (StudentParentSetupLinkMail $mail) use ($person) {
                return $mail->name === $person->name;
            });

        $this->assertNotNull($mail, 'No setup email was queued for '.$person->name.'.');

        return $this->tokenFromUrl($mail->inviteUrl);
    }

    private function tokenFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $this->assertStringStartsWith('/invite/account/', $path);

        return substr($path, strlen('/invite/account/'));
    }
}
