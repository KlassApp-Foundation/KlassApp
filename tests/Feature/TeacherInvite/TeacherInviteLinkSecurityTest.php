<?php

namespace Tests\Feature\TeacherInvite;

use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\TeacherInviteLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherInviteLinkSecurityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;
    private StandardLink $standardLink;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        // Create school manually — no School factory exists for this project
        $this->school = School::create([
            'name' => 'Invite Test School',
            'slug' => 'invite-test-school',
            'email' => 'school@invite.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 1,
            'name' => 'Admin Test',
            'email' => 'admin@invite.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        // AcademicYear + Standard required by standards_link FK
        $year = \App\Models\AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Test Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = \App\Models\Standard::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'code' => 'P1',
            'order' => 1,
            'status' => 1,
        ]);

        $this->standardLink = StandardLink::create([
            'school_id' => $this->school->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'status' => 1,
        ]);
    }

    /** @test */
    public function valid_token_shows_password_set_form()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'new.teacher@invite.test',
            name: 'Alice Okello',
            standardLink: $this->standardLink,
        );

        $response = $this->get(route('teacher.invite.form', $token));

        $response->assertOk();
        $response->assertSee('Set your password');
        $response->assertSee($this->school->name);
        $response->assertSee('new.teacher@invite.test');
        $response->assertSee('P.1');
    }

    /** @test */
    public function invalid_token_shows_error_page()
    {
        $response = $this->get(route('teacher.invite.form', Str::random(64)));

        $response->assertOk();
        $response->assertSee('Invalid link');
    }

    /** @test */
    public function expired_token_shows_error_page()
    {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);

        TeacherInvite::create([
            'school_id' => $this->school->id,
            'email' => 'old@invite.test',
            'token_hash' => $tokenHash,
            'name' => 'Old Invite',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get(route('teacher.invite.form', $token));

        $response->assertOk();
        $response->assertSee('Link expired');
    }

    /** @test */
    public function claimed_token_shows_error_page()
    {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);

        TeacherInvite::create([
            'school_id' => $this->school->id,
            'email' => 'used@invite.test',
            'token_hash' => $tokenHash,
            'name' => 'Used Invite',
            'expires_at' => now()->addDay(),
            'claimed_at' => now(),
            'user_id' => 99,
        ]);

        $response = $this->get(route('teacher.invite.form', $token));

        $response->assertOk();
        $response->assertSee('Already used');
    }

    /** @test */
    public function valid_token_claim_creates_user_with_chosen_password()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'new.teacher@invite.test',
            name: 'Alice Okello',
            standardLink: $this->standardLink,
        );

        $response = $this->post(route('teacher.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'new.teacher@invite.test',
            'name' => 'Alice Okello',
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'status' => 'active',
        ]);

        // Verify user can authenticate with chosen password
        $this->assertTrue(
            auth()->attempt(['email' => 'new.teacher@invite.test', 'password' => 'StrongP4ssw0rd'])
        );
        auth()->logout();

        // Verify userprofile created
        $teacher = User::where('email', 'new.teacher@invite.test')->first();
        $this->assertNotNull($teacher);
        $this->assertDatabaseHas('userprofiles', [
            'user_id' => $teacher->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'firstname' => 'Alice Okello',
            'status' => 'active',
        ]);

        // Verify class_teacher_id set
        $this->standardLink->refresh();
        $this->assertEquals($teacher->id, $this->standardLink->class_teacher_id);
        $this->section->refresh();
        $this->assertEquals($teacher->id, $this->section->class_teacher_id);

        // Verify invite marked claimed
        $invite->refresh();
        $this->assertNotNull($invite->claimed_at);
        $this->assertEquals($teacher->id, $invite->user_id);
    }

    /** @test */
    public function expired_token_cannot_be_claimed()
    {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);

        TeacherInvite::create([
            'school_id' => $this->school->id,
            'email' => 'old@invite.test',
            'token_hash' => $tokenHash,
            'name' => 'Old Invite',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->post(route('teacher.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'This invite link has expired.');

        // No user should be created
        $this->assertDatabaseMissing('users', ['email' => 'old@invite.test']);
    }

    /** @test */
    public function already_claimed_token_cannot_be_reused()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'first@invite.test',
            name: 'First User',
        );

        // Claim it once
        $this->post(route('teacher.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ]);

        // Try to claim it again
        $response = $this->post(route('teacher.invite.claim', $token), [
            'password' => 'AnotherP4ssword1',
            'password_confirmation' => 'AnotherP4ssword1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'This invite has already been used.');

        // Only one user
        $this->assertEquals(1, User::where('email', 'first@invite.test')->count());
    }

    /** @test */
    public function password_must_be_at_least_8_characters()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'short@invite.test',
            name: 'Short Pw',
        );

        $response = $this->post(route('teacher.invite.claim', $token), [
            'password' => 'Ab1',
            'password_confirmation' => 'Ab1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'short@invite.test']);
    }

    /** @test */
    public function password_must_contain_uppercase_lowercase_and_digit()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'weak@invite.test',
            name: 'Weak Pw',
        );

        // No uppercase
        $this->post(route('teacher.invite.claim', $token), [
            'password' => 'alllowercase1',
            'password_confirmation' => 'alllowercase1',
        ])->assertSessionHasErrors('password');

        // No number
        $this->post(route('teacher.invite.claim', $token), [
            'password' => 'AllUpperCase',
            'password_confirmation' => 'AllUpperCase',
        ])->assertSessionHasErrors('password');

        // No lowercase
        $this->post(route('teacher.invite.claim', $token), [
            'password' => 'ALLUPPERCASE1',
            'password_confirmation' => 'ALLUPPERCASE1',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@invite.test']);
    }

    /** @test */
    public function password_confirmation_must_match()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'mismatch@invite.test',
            name: 'Mismatch',
        );

        $response = $this->post(route('teacher.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'DifferentP4ss',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'mismatch@invite.test']);
    }

    /** @test */
    public function crafted_token_is_rejected()
    {
        $crafterToken = Str::random(64);

        $this->get(route('teacher.invite.form', $crafterToken))
            ->assertSee('Invalid link');

        $this->post(route('teacher.invite.claim', $crafterToken), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ])
            ->assertRedirect(route('teacher.invite.form', $crafterToken))
            ->assertSessionHas('error', 'This invite link is invalid.');
    }

    /** @test */
    public function token_hash_is_never_plain_text()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'hashed@invite.test',
            name: 'Hash Test',
        );

        $this->assertNotEquals($token, $invite->token_hash);
        $this->assertEquals(64, strlen($invite->token_hash));
    }

    /** @test */
    public function class_teacher_assignment_is_optional()
    {
        ['invite' => $invite, 'token' => $token] = TeacherInviteLinkService::issue(
            school: $this->school,
            email: 'noteacher@invite.test',
            name: 'No Class',
            standardLink: null,
        );

        $this->post(route('teacher.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ]);

        $teacher = User::where('email', 'noteacher@invite.test')->first();
        $this->assertNotNull($teacher);
        $this->assertEquals(0, StandardLink::where('class_teacher_id', $teacher->id)->count());
    }
}
