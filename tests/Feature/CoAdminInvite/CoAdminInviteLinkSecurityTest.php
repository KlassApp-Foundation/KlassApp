<?php

namespace Tests\Feature\CoAdminInvite;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\CoAdminInviteLinkMail;
use App\Mail\CoAdminInviteMail;
use App\Models\CoAdminInvite;
use App\Models\School;
use App\Models\User;
use App\Services\CoAdminInviteLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoAdminInviteLinkSecurityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();

        \DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'CoAdmin Invite Test School',
            'slug' => 'coadmin-invite-test',
            'email' => 'school@coadmininvite.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
    }

    public function test_valid_token_shows_password_set_form(): void
    {
        ['token' => $token] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'new.coadmin@coadmininvite.test',
            name: 'Sarah Nansubuga',
        );

        $response = $this->get(route('coadmin.invite.form', $token));

        $response->assertOk();
        $response->assertSee('Set your password');
        $response->assertSee($this->school->name);
        $response->assertSee('new.coadmin@coadmininvite.test');
        $response->assertSee('as a Co-Admin');
        $response->assertSee(route('coadmin.invite.claim', $token), false);
    }

    public function test_invalid_token_shows_error_page(): void
    {
        $response = $this->get(route('coadmin.invite.form', Str::random(64)));

        $response->assertOk();
        $response->assertSee("This invite link doesn't work", false);
    }

    public function test_expired_token_shows_error_page(): void
    {
        $token = Str::random(64);

        CoAdminInvite::create([
            'school_id' => $this->school->id,
            'email' => 'old@coadmininvite.test',
            'token_hash' => hash('sha256', $token),
            'name' => 'Old Invite',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get(route('coadmin.invite.form', $token));

        $response->assertOk();
        $response->assertSee('This invite has expired');
    }

    public function test_claimed_token_shows_error_page(): void
    {
        $token = Str::random(64);

        CoAdminInvite::create([
            'school_id' => $this->school->id,
            'email' => 'used@coadmininvite.test',
            'token_hash' => hash('sha256', $token),
            'name' => 'Used Invite',
            'expires_at' => now()->addDay(),
            'claimed_at' => now(),
            'user_id' => 99,
        ]);

        $response = $this->get(route('coadmin.invite.form', $token));

        $response->assertOk();
        $response->assertSee('This invite has already been used');
    }

    public function test_valid_token_claim_creates_co_admin_with_chosen_password(): void
    {
        ['invite' => $invite, 'token' => $token] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'new.coadmin@coadmininvite.test',
            name: 'Sarah Nansubuga',
        );

        $response = $this->post(route('coadmin.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => 'new.coadmin@coadmininvite.test',
            'name' => 'Sarah Nansubuga',
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $coAdmin = User::where('email', 'new.coadmin@coadmininvite.test')->first();
        $this->assertNotNull($coAdmin);
        $this->assertSame(0, (int) $coAdmin->is_reset);
        $this->assertTrue(
            auth()->attempt(['email' => 'new.coadmin@coadmininvite.test', 'password' => 'StrongP4ssw0rd'])
        );
        auth()->logout();

        $this->assertDatabaseHas('userprofiles', [
            'user_id' => $coAdmin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Sarah Nansubuga',
            'status' => 'active',
        ]);

        $invite->refresh();
        $this->assertNotNull($invite->claimed_at);
        $this->assertEquals($coAdmin->id, $invite->user_id);
    }

    public function test_expired_token_cannot_be_claimed(): void
    {
        $token = Str::random(64);

        CoAdminInvite::create([
            'school_id' => $this->school->id,
            'email' => 'old@coadmininvite.test',
            'token_hash' => hash('sha256', $token),
            'name' => 'Old Invite',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->post(route('coadmin.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'old@coadmininvite.test']);
    }

    public function test_claimed_token_cannot_be_reused(): void
    {
        ['invite' => $invite, 'token' => $token] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'once@coadmininvite.test',
            name: 'First Claim',
        );

        $this->post(route('coadmin.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ]);

        $totalBefore = User::count();

        $response = $this->post(route('coadmin.invite.claim', $token), [
            'password' => 'DifferentP4ss',
            'password_confirmation' => 'DifferentP4ss',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertSame(1, User::where('email', 'once@coadmininvite.test')->count());
        $this->assertSame($totalBefore, User::count());
        $invite->refresh();
        $this->assertNotNull($invite->claimed_at);
    }

    public function test_weak_password_is_rejected_on_claim(): void
    {
        ['token' => $token] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'weak@coadmininvite.test',
            name: 'Weak Password',
        );

        $response = $this->from(route('coadmin.invite.form', $token))
            ->post(route('coadmin.invite.claim', $token), [
                'password' => 'abc',
                'password_confirmation' => 'abc',
            ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weak@coadmininvite.test']);
    }

    public function test_invite_email_contains_link_and_no_password(): void
    {
        ['invite' => $invite, 'token' => $token] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'mail@coadmininvite.test',
            name: 'Mail Recipient',
        );

        CoAdminInviteLinkService::sendEmail($invite, $token, $this->school);

        Mail::assertQueued(CoAdminInviteLinkMail::class, function (CoAdminInviteLinkMail $mail) use ($token) {
            $rendered = $mail->render();

            return $mail->inviteUrl === url('/invite/co-admin/'.$token)
                && ! property_exists($mail, 'password')
                && str_contains($rendered, 'Set Your Password')
                && str_contains($rendered, url('/invite/co-admin/'.$token))
                && ! str_contains(strtolower($rendered), 'password:**');
        });
        Mail::assertNotQueued(CoAdminInviteMail::class);
    }

    public function test_tampered_token_is_rejected(): void
    {
        ['token' => $token] = CoAdminInviteLinkService::issue(
            school: $this->school,
            email: 'tamper@coadmininvite.test',
            name: 'Tamper Test',
        );

        $tampered = substr($token, 0, -1).(substr($token, -1) === 'A' ? 'B' : 'A');

        $response = $this->get(route('coadmin.invite.form', $tampered));

        $response->assertOk();
        $response->assertSee("This invite link doesn't work", false);
        $this->assertDatabaseMissing('users', ['email' => 'tamper@coadmininvite.test']);
    }
}
