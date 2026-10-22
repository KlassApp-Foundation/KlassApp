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
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SchoolInviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A claim grants Growth. Without this row the claim rolls back.
        \App\Models\Plan::create([
            'name' => 'growth',
            'display_name' => 'Growth',
            'cycle' => 30,
            'amount' => 35,
            'is_active' => 1,
            'order' => 2,
        ]);
    }

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

    public function test_a_second_claim_of_a_claimed_invite_cannot_create_a_second_school(): void
    {
        $admin = $this->platformAdmin();
        ['invite' => $invite] = SchoolInviteService::issue($this->earlyAccess(), $admin);

        // Two submissions that both loaded the invite before either claimed it.
        $first = SchoolInvite::findOrFail($invite->id);
        $second = SchoolInvite::findOrFail($invite->id);
        $this->assertNull($second->claimed_at);

        $schoolsBefore = School::count();

        $claimOne = SchoolInviteService::claim($first, [
            'name' => 'Grace Nakato',
            'phone' => '+256701234567',
            'password' => 'StrongPass123!',
        ]);
        $this->assertTrue($claimOne['success']);

        $claimTwo = SchoolInviteService::claim($second, [
            'name' => 'Someone Else',
            'phone' => '+256709876543',
            'password' => 'StrongPass123!',
        ]);

        $this->assertFalse($claimTwo['success']);
        $this->assertStringContainsString('already been used', $claimTwo['message']);
        $this->assertSame($schoolsBefore + 1, School::count());
        $this->assertSame(1, School::where('name', 'Greenfield Primary')->count());
        $this->assertSame(1, User::where('email', 'adopter@school.test')->count());
        $this->assertNotNull($invite->fresh()->claimed_at);
    }

    public function test_a_queue_failure_is_reported_and_the_invite_can_be_retried(): void
    {
        $admin = $this->platformAdmin();
        $request = $this->earlyAccess();

        Mail::shouldReceive('to')->andThrow(new RuntimeException('queue connection refused'));

        $this->actingAs($admin)
            ->post(route('platform.early-access.invite', $request))
            ->assertRedirect()
            ->assertSessionMissing('success')
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'could not be queued'));

        $invite = SchoolInvite::where('email', 'adopter@school.test')->firstOrFail();
        $this->assertNotNull($invite->email_failed_at);

        $this->actingAs($admin)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertSee('Email not sent')
            ->assertSee('data-testid="invite-retry-button"', false)
            ->assertDontSee('Invite sent');

        // shouldReceive replaced the mail manager. Mail::fake() asks that
        // manager for its default driver, so put the real manager back first.
        $this->app->forgetInstance('mail.manager');
        Mail::clearResolvedInstances();
        Mail::fake();

        $this->actingAs($admin)
            ->post(route('platform.school-invites.resend', $invite))
            ->assertSessionHas('success');

        $this->assertNull($invite->fresh()->email_failed_at);
        Mail::assertQueued(SchoolInviteMail::class, fn (SchoolInviteMail $mail) => $mail->hasTo('adopter@school.test'));
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

        $school->forceFill(['is_test' => 1])->save();

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

    public function test_created_by_matches_users_id_and_nulls_on_delete(): void
    {
        $createdBy = collect(Schema::getColumns('school_invites'))->firstWhere('name', 'created_by');
        $usersId = collect(Schema::getColumns('users'))->firstWhere('name', 'id');

        $this->assertNotNull($createdBy);
        $this->assertNotNull($usersId);
        $this->assertTrue($createdBy['nullable']);
        $this->assertSame(
            strtolower((string) $usersId['type']),
            strtolower((string) $createdBy['type'])
        );

        $foreignKey = collect(Schema::getForeignKeys('school_invites'))->first(
            fn (array $fk) => in_array('created_by', $fk['columns'] ?? [], true)
        );

        $this->assertNotNull($foreignKey);
        $this->assertSame('users', $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
        $this->assertSame('set null', $foreignKey['on_delete']);
    }

    public function test_a_ticked_invite_claims_a_school_with_is_test_set(): void
    {
        Mail::fake();

        $admin = $this->platformAdmin();
        $request = $this->earlyAccess('ticked@school.test');

        $page = $this->actingAs($admin)->get(route('platform.early-access.index'));
        $page->assertOk();
        $this->assertTestSchoolCheckboxIsUnchecked($page);

        $this->actingAs($admin)
            ->post(route('platform.early-access.invite', $request), ['is_test' => '1'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $invite = SchoolInvite::where('email', 'ticked@school.test')->firstOrFail();
        $this->assertSame(1, (int) $invite->is_test);

        $this->actingAs($admin)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertSee('data-testid="invite-test-badge"', false)
            ->assertSee('Test', false);

        $isTestAtInsert = null;
        School::creating(function (School $school) use (&$isTestAtInsert) {
            if (strcasecmp((string) $school->email, 'ticked@school.test') === 0) {
                $isTestAtInsert = $school->is_test;
            }
        });

        Auth::logout();

        $this->post(route('school-invite.claim', $this->queuedInviteToken()), [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('onboarding.choice'));

        $this->assertSame(1, (int) $isTestAtInsert);
        $this->assertSame(1, (int) School::where('email', 'ticked@school.test')->value('is_test'));
    }

    public function test_an_unticked_invite_claims_a_school_with_is_test_clear(): void
    {
        Mail::fake();

        $admin = $this->platformAdmin();
        $request = $this->earlyAccess('unticked@school.test');

        $page = $this->actingAs($admin)->get(route('platform.early-access.index'));
        $page->assertOk();
        $this->assertTestSchoolCheckboxIsUnchecked($page);

        $this->actingAs($admin)
            ->post(route('platform.early-access.invite', $request))
            ->assertRedirect()
            ->assertSessionHas('success');

        $invite = SchoolInvite::where('email', 'unticked@school.test')->firstOrFail();
        $this->assertSame(0, (int) $invite->is_test);

        $this->actingAs($admin)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertDontSee('data-testid="invite-test-badge"', false);

        $isTestAtInsert = null;
        School::creating(function (School $school) use (&$isTestAtInsert) {
            if (strcasecmp((string) $school->email, 'unticked@school.test') === 0) {
                $isTestAtInsert = $school->is_test;
            }
        });

        Auth::logout();

        $this->post(route('school-invite.claim', $this->queuedInviteToken()), [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('onboarding.choice'));

        $this->assertTrue($isTestAtInsert === null || (int) $isTestAtInsert === 0);
        $this->assertSame(0, (int) School::where('email', 'unticked@school.test')->value('is_test'));
    }

    public function test_a_non_admin_cannot_set_the_test_school_flag(): void
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
            ->post(route('platform.early-access.invite', $this->earlyAccess('nonadmin@school.test')), [
                'is_test' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(0, SchoolInvite::count());
        $this->assertNull(School::where('email', 'nonadmin@school.test')->first());
    }

    public function test_a_tampered_claim_request_cannot_set_is_test(): void
    {
        $admin = $this->platformAdmin();
        ['token' => $token] = SchoolInviteService::issue(
            $this->earlyAccess('tamper@school.test'),
            $admin,
            false,
        );

        $this->post(route('school-invite.claim', $token), [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'is_test' => '1',
        ])->assertRedirect(route('onboarding.choice'));

        $this->assertSame(0, (int) SchoolInvite::where('email', 'tamper@school.test')->value('is_test'));
        $this->assertSame(0, (int) School::where('email', 'tamper@school.test')->value('is_test'));
    }

    private function assertTestSchoolCheckboxIsUnchecked(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertSee('name="is_test"', false);
        $response->assertSee('data-testid="early-access-test-school"', false);
        $response->assertSee('Test school', false);

        preg_match_all('/<input\b[^>]*data-testid="early-access-test-school"[^>]*>/i', $response->getContent(), $matches);
        $this->assertNotEmpty($matches[0]);

        foreach ($matches[0] as $tag) {
            $this->assertDoesNotMatchRegularExpression('/(?:^|\s)checked(?:\s|=|>|$)/i', $tag);
        }
    }

    private function queuedInviteToken(): string
    {
        $token = null;

        Mail::assertQueued(SchoolInviteMail::class, function (SchoolInviteMail $mail) use (&$token) {
            $token = basename((string) parse_url($mail->inviteUrl, PHP_URL_PATH));

            return $token !== '';
        });

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $token;
    }
}
