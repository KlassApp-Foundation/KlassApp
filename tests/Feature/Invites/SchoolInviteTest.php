<?php

namespace Tests\Feature\Invites;

use App\Mail\SchoolInviteMail;
use App\Models\EarlyAccessRequest;
use App\Models\School;
use App\Models\SchoolInvite;
use App\Models\User;
use App\Enums\ToshiMode;
use App\Services\SchoolInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SchoolInviteTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::create([
            'name' => 'Platform Admin',
            'email' => 'platform@klassapp.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 1,
            'status' => 'active',
        ]);
    }

    private function earlyAccess(string $email = 'adopter@school.test'): EarlyAccessRequest
    {
        return EarlyAccessRequest::create([
            'school_name' => 'Greenfield Primary',
            'contact_name' => 'Grace Nakato',
            'contact_role' => 'Head Teacher',
            'email' => $email,
            'phone' => '0701234567',
            'country' => 'Uganda',
            'approx_students' => 420,
            'consented_at' => now(),
        ]);
    }

    public function test_platform_admin_can_invite_an_approved_request(): void
    {
        Mail::fake();

        $admin = $this->platformAdmin();
        $request = $this->earlyAccess();

        $this->actingAs($admin)
            ->post(route('platform.early-access.invite', $request))
            ->assertRedirect();

        $this->assertDatabaseHas('school_invites', [
            'email' => 'adopter@school.test',
            'school_name' => 'Greenfield Primary',
            'revoked_at' => null,
        ]);

        Mail::assertQueued(SchoolInviteMail::class, function (SchoolInviteMail $mail) {
            return $mail->hasTo('adopter@school.test')
                && str_contains($mail->inviteUrl, '/invite/school/')
                && $mail->schoolName === 'Greenfield Primary';
        });
    }

    public function test_a_valid_invite_claims_the_school_even_when_signup_is_closed(): void
    {
        config(['klassapp.signup_enabled' => false]);

        $admin = $this->platformAdmin();
        ['invite' => $invite, 'token' => $token] = SchoolInviteService::issue($this->earlyAccess(), $admin);

        $this->get(route('school-invite.show', $token))
            ->assertOk()
            ->assertSee('Greenfield Primary')
            ->assertSee('adopter@school.test');

        $response = $this->post(route('school-invite.claim', $token), [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertRedirect(route('onboarding.choice'));
        $this->assertAuthenticated();

        $user = User::where('email', 'adopter@school.test')->firstOrFail();
        $this->assertSame(3, (int) $user->usergroup_id);
        $this->assertNotNull($user->school);
        $this->assertSame('Greenfield Primary', $user->school->name);
        $this->assertSame('Uganda', $user->school->registration_country);
        $this->assertNotNull($invite->fresh()->claimed_at);

        $this->assertDatabaseHas('users', [
            'email' => 'adopter@school.test',
            'school_id' => $user->school_id,
            'usergroup_id' => 3,
        ]);
    }

    public function test_an_expired_invite_cannot_be_claimed(): void
    {
        $admin = $this->platformAdmin();
        ['invite' => $invite, 'token' => $token] = SchoolInviteService::issue($this->earlyAccess(), $admin);
        $invite->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->get(route('school-invite.show', $token))->assertSee('expired');

        $this->post(route('school-invite.claim', $token), [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('school-invite.show', $token));

        $this->assertSame(0, User::where('email', 'adopter@school.test')->count());
        $this->assertNull($invite->fresh()->claimed_at);
    }

    public function test_an_invite_is_single_use(): void
    {
        $admin = $this->platformAdmin();
        ['invite' => $invite, 'token' => $token] = SchoolInviteService::issue($this->earlyAccess(), $admin);

        $payload = [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ];

        $this->post(route('school-invite.claim', $token), $payload)->assertRedirect(route('onboarding.choice'));
        Auth::logout();

        $this->post(route('school-invite.claim', $token), $payload)
            ->assertRedirect(route('school-invite.show', $token));

        $this->assertSame(1, User::where('email', 'adopter@school.test')->count());
        $this->assertNotNull($invite->fresh()->claimed_at);
    }

    public function test_a_non_platform_admin_cannot_issue_an_invite(): void
    {
        $school = School::create(['name' => 'A School', 'email' => 'school@test.test', 'status' => 1]);
        $user = User::create([
            'name' => 'School Admin',
            'email' => 'school.admin@test.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('platform.early-access.invite', $this->earlyAccess()))
            ->assertForbidden();

        $this->assertSame(0, SchoolInvite::count());
    }

    public function test_a_platform_admin_can_resend_and_revoke_an_invite(): void
    {
        Mail::fake();

        $admin = $this->platformAdmin();
        ['invite' => $invite] = SchoolInviteService::issue($this->earlyAccess(), $admin);

        $this->actingAs($admin)
            ->post(route('platform.school-invites.resend', $invite))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('platform.school-invites.revoke', $invite))
            ->assertSessionHas('success');

        $this->assertNotNull($invite->fresh()->revoked_at);
        $this->assertSame('revoked', $invite->fresh()->status());
    }

    public function test_the_claim_choice_sets_the_toshi_mode_or_opens_the_wizard(): void
    {
        $school = School::create([
            'name' => 'Choice School',
            'email' => 'choice@test.test',
            'status' => 1,
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ]);
        $user = User::create([
            'name' => 'Choice Admin',
            'email' => 'choice.admin@test.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('onboarding.choice.store'), ['path' => 'toshi'])
            ->assertRedirect('/admin/dashboard');

        $this->assertSame(ToshiMode::Onboarding, $school->fresh()->toshi_mode);
        $this->assertSame(0, (int) $school->fresh()->toshi_enabled);

        $this->actingAs($user)
            ->post(route('onboarding.choice.store'), ['path' => 'wizard'])
            ->assertRedirect(route('admin.onboarding.wizard'));

        $this->assertSame(ToshiMode::Preview, $school->fresh()->toshi_mode);
    }
}
