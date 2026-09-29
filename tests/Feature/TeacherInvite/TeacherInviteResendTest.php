<?php

namespace Tests\Feature\TeacherInvite;

use App\Mail\TeacherInviteLinkMail;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Services\TeacherInviteLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherInviteResendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function admin(): User
    {
        $school = School::create([
            'name'   => 'Test School ' . uniqid(),
            'slug'   => 'test-' . uniqid(),
            'email'  => 'test-' . uniqid() . '@school.ug',
            'phone'  => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);

        return User::factory()->create([
            'school_id'    => $school->id,
            'usergroup_id' => 3,
        ]);
    }

    private function stream(int $schoolId): StandardLink
    {
        $section = Section::create([
            'school_id' => $schoolId,
            'name'      => 'P1 A',
            'status'    => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $schoolId,
            'name'      => 'primary',
            'order'     => 1,
            'status'    => 1,
        ]);
        $year = AcademicYear::create([
            'school_id'   => $schoolId,
            'name'        => '2026',
            'start_date'  => '2026-01-01',
            'end_date'    => '2026-12-31',
            'status'      => 1,
        ]);

        return StandardLink::create([
            'school_id'        => $schoolId,
            'section_id'       => $section->id,
            'standard_id'      => $standard->id,
            'academic_year_id' => $year->id,
            'status'           => 1,
            'stream'           => 'A',
        ]);
    }

    private function issueInvite(User $admin): array
    {
        $school = School::find($admin->school_id);
        $result = TeacherInviteLinkService::issue(
            school: $school,
            email: 'pending@school.ug',
            name: 'Pending Teacher',
            standardLink: $this->stream($admin->school_id),
        );
        TeacherInviteLinkService::sendEmail($result['invite'], $result['token'], $school, 'P1 A');

        return $result;
    }

    public function test_resend_issues_a_fresh_token_and_invalidates_the_old_link(): void
    {
        $admin = $this->admin();
        ['invite' => $invite, 'token' => $oldToken] = $this->issueInvite($admin);

        $this->get(route('teacher.invite.form', $oldToken))->assertOk()->assertSee('Set your password');

        $response = $this->actingAs($admin)->post(route('admin.class-teacher-invite.resend', $invite));
        $response->assertSessionHas('successmessage');

        $invite->refresh();
        $this->assertNotSame($oldToken, $invite->token_hash);

        $mails = Mail::queued(TeacherInviteLinkMail::class);
        $this->assertCount(2, $mails);
        $newToken = Str::after($mails->last()->inviteUrl, '/invite/teacher/');
        $this->assertNotSame($oldToken, $newToken);

        // Old link is dead, new link works
        $this->get(route('teacher.invite.form', $oldToken))->assertOk()->assertSee('Invalid link');
        $this->get(route('teacher.invite.form', $newToken))->assertOk()->assertSee('Set your password');
    }

    public function test_resend_extends_expired_invites(): void
    {
        $admin = $this->admin();
        ['invite' => $invite] = $this->issueInvite($admin);
        $invite->expires_at = now()->subDay();
        $invite->save();

        $this->actingAs($admin)->post(route('admin.class-teacher-invite.resend', $invite))
            ->assertSessionHas('successmessage');

        $invite->refresh();
        $this->assertTrue($invite->expires_at->isFuture());
    }

    public function test_resend_is_refused_for_other_schools(): void
    {
        $admin = $this->admin();
        $this->stream($admin->school_id); // pass the onboarding gate for the admin's own school
        $otherAdmin = $this->admin();
        ['invite' => $invite] = $this->issueInvite($otherAdmin);

        $this->actingAs($admin)->post(route('admin.class-teacher-invite.resend', $invite))
            ->assertStatus(403);
    }

    public function test_claimed_invite_cannot_be_resent(): void
    {
        $admin = $this->admin();
        ['invite' => $invite] = $this->issueInvite($admin);
        $invite->claimed_at = now();
        $invite->save();

        $oldHash = $invite->token_hash;

        $this->actingAs($admin)->post(route('admin.class-teacher-invite.resend', $invite))
            ->assertRedirect(route('admin.classes'))
            ->assertSessionHas('errormessage');

        $invite->refresh();
        $this->assertSame($oldHash, $invite->token_hash);
        $this->assertCount(1, Mail::queued(TeacherInviteLinkMail::class));
    }

    public function test_invite_page_lists_pending_invites_only(): void
    {
        $admin = $this->admin();

        ['invite' => $pending] = $this->issueInvite($admin);

        $claimed = TeacherInvite::create([
            'school_id' => $admin->school_id,
            'email'     => 'claimed@school.ug',
            'token_hash' => hash('sha256', Str::random(64)),
            'name'      => 'Claimed Teacher',
            'expires_at' => now()->addDay(),
            'claimed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.class-teacher-invite.create', $pending->standardLink->section_id)
        );

        $response->assertOk();
        $response->assertSee('Pending invites');
        $response->assertSee('pending@school.ug');
        $response->assertDontSee('claimed@school.ug');
    }

    public function test_invite_mail_never_contains_a_password(): void
    {
        $mailable = new TeacherInviteLinkMail(
            name: 'No Password',
            schoolName: 'Test School',
            inviteUrl: 'https://example.test/invite/teacher/token',
            className: 'P1 A',
            expiresAt: now()->addHours(72),
        );

        $rendered = $mailable->render();

        $this->assertStringNotContainsString('Password:', $rendered);
        $this->assertFalse(property_exists($mailable, 'password'));
    }
}
