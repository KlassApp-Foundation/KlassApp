<?php

namespace Tests\Feature\Invites;

use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Models\CurrentPlan;
use App\Models\EarlyAccessRequest;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CoAdminInviteLinkService;
use App\Services\EarlyAdopterOffer;
use App\Services\OnboardingEngine;
use App\Services\SchoolInviteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class EarlyAdopterOfferTest extends TestCase
{
    use RefreshDatabase;

    private Plan $freemium;

    private Plan $growth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        config(['app.timezone' => 'Africa/Kampala', 'klassapp.signup_enabled' => false]);
        date_default_timezone_set('Africa/Kampala');

        \DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->freemium = Plan::create([
            'name' => 'freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'amount' => 0,
            'is_active' => 1,
            'order' => 1,
        ]);
        $this->growth = Plan::create([
            'name' => 'growth',
            'display_name' => 'Growth',
            'cycle' => 30,
            'amount' => 35,
            'is_active' => 1,
            'order' => 2,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_an_early_adopter_claim_gets_growth_free_until_three_months_after_the_claim(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-21 09:00:00', 'Africa/Kampala'));

        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'platform@klassapp.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 1,
            'status' => 'active',
        ]);
        ['token' => $token] = SchoolInviteService::issue($this->earlyAccess(), $admin);

        $this->post(route('school-invite.claim', $token), [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('onboarding.choice'));

        $user = User::where('email', 'adopter@school.test')->firstOrFail();
        $school = $user->school;

        $current = CurrentPlan::where('school_id', $school->id)->firstOrFail();
        $this->assertSame($this->growth->id, (int) $current->plan_id);
        $this->assertSame('running', $current->status);
        $this->assertFalse((bool) $current->is_trial);
        $this->assertNull($current->trial_ends_at);
        $this->assertSame(1, CurrentPlan::where('school_id', $school->id)->count());

        $this->assertSame(1, Subscription::where('school_id', $school->id)->count());
        $subscription = Subscription::where('school_id', $school->id)->firstOrFail();
        $this->assertSame($this->growth->id, (int) $subscription->plan_id);
        $this->assertSame('approved', $subscription->status);
        $this->assertSame('2026-10-21', $subscription->start_date->toDateString());
        $this->assertSame('2027-01-21', $subscription->end_date->toDateString());
        $this->assertEquals(0, (float) $subscription->amount_paid);
        $this->assertNull($subscription->payment_method);
        $this->assertNull($subscription->payment_reference);

        $this->assertDatabaseHas('school_details', [
            'school_id' => $school->id,
            'meta_key' => EarlyAdopterOffer::OFFER_KEY,
            'meta_value' => EarlyAdopterOffer::OFFER_VALUE,
        ]);
        $recordedAt = SchoolDetail::where('school_id', $school->id)
            ->where('meta_key', EarlyAdopterOffer::OFFER_AT_KEY)
            ->value('meta_value');
        $this->assertTrue(Carbon::parse($recordedAt)->equalTo(now()));

        $this->get('/admin/editprofile')
            ->assertOk()
            ->assertSee('Current plan:', false)
            ->assertSee('Growth — free until 21 Jan 2027', false);

        $this->get('/admin/subscriptions')
            ->assertOk()
            ->assertSee('growth', false)
            ->assertSee('21 Jan 2027', false);

        $school->load('subscription.plan');
        $label = app(EarlyAdopterOffer::class)->endLabel($school, $school->subscription->first()->plan);
        $this->assertSame('free until 21 Jan 2027', $label);
    }

    public function test_a_co_admin_invite_does_not_change_the_school_plan(): void
    {
        $school = School::create([
            'name' => 'Ordinary Secondary',
            'email' => 'ordinary@school.test',
            'slug' => 'ordinary-secondary',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
        ]);
        $admin = User::create([
            'name' => 'Ordinary Admin',
            'email' => 'ordinary.admin@school.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'status' => 'active',
        ]);
        CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id' => $this->freemium->id,
            'status' => 'running',
        ]);
        $subscription = Subscription::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'plan_id' => $this->freemium->id,
            'status' => 'approved',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-01',
            'amount_paid' => 0,
        ]);

        ['token' => $token] = CoAdminInviteLinkService::issue(
            school: $school,
            email: 'coadmin@school.test',
            name: 'Sarah Nansubuga',
        );

        $this->post(route('coadmin.invite.claim', $token), [
            'password' => 'StrongP4ssw0rd',
            'password_confirmation' => 'StrongP4ssw0rd',
        ])->assertRedirect(route('login'));

        $current = CurrentPlan::where('school_id', $school->id)->firstOrFail();
        $this->assertSame($this->freemium->id, (int) $current->plan_id);
        $this->assertSame(1, CurrentPlan::where('school_id', $school->id)->count());

        $subscription->refresh();
        $this->assertSame($this->freemium->id, (int) $subscription->plan_id);
        $this->assertSame('2026-06-01', $subscription->end_date->toDateString());
        $this->assertSame(1, Subscription::where('school_id', $school->id)->count());
        $this->assertNull($school->detailValue(EarlyAdopterOffer::OFFER_KEY));
        $this->assertNull($school->detailValue(EarlyAdopterOffer::OFFER_AT_KEY));
    }

    public function test_a_repeat_claim_does_not_extend_the_growth_period(): void
    {
        $claimedAt = Carbon::parse('2026-10-21 09:00:00', 'Africa/Kampala');
        Carbon::setTestNow($claimedAt);

        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'platform-repeat@klassapp.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 1,
            'status' => 'active',
        ]);
        ['invite' => $invite, 'token' => $token] = SchoolInviteService::issue(
            $this->earlyAccess('repeat@school.test'),
            $admin,
        );

        $this->post(route('school-invite.claim', $token), [
            'name' => 'Grace Nakato',
            'phone' => '0701234567',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('onboarding.choice'));

        $user = User::where('email', 'repeat@school.test')->firstOrFail();
        $school = $user->school;
        $offerAt = $school->detailValue(EarlyAdopterOffer::OFFER_AT_KEY);

        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Africa/Kampala'));
        auth()->logout();

        $this->post(route('school-invite.claim', $token), [
            'name' => 'Someone Else',
            'phone' => '0709876543',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('school-invite.show', $token));

        app(EarlyAdopterOffer::class)->apply($school->fresh(), $user, now());

        $subscription = Subscription::where('school_id', $school->id)->firstOrFail();
        $this->assertSame('2026-10-21', $subscription->start_date->toDateString());
        $this->assertSame('2027-01-21', $subscription->end_date->toDateString());
        $this->assertSame(1, Subscription::where('school_id', $school->id)->count());
        $this->assertSame($offerAt, $school->fresh()->detailValue(EarlyAdopterOffer::OFFER_AT_KEY));
        $this->assertNotNull($invite->fresh()->claimed_at);
        $this->assertSame(1, User::where('email', 'repeat@school.test')->count());

        app(OnboardingEngine::class)->savePlan($school->fresh(), $this->growth->id, skipCompletionCheck: true);

        $current = CurrentPlan::where('school_id', $school->id)->firstOrFail();
        $this->assertFalse((bool) $current->is_trial);
        $this->assertNull($current->trial_ends_at);
        $subscription->refresh();
        $this->assertSame('2027-01-21', $subscription->end_date->toDateString());
        $this->assertSame('approved', $subscription->status);

        $method = new ReflectionMethod(AgentToshi::class, 'persistSelectedPlan');
        $method->invoke(new AgentToshi(), $school->id, $this->growth->id);

        $subscription->refresh();
        $this->assertSame('2027-01-21', $subscription->end_date->toDateString());
        $this->assertSame('approved', $subscription->status);
        $this->assertSame(1, Subscription::where('school_id', $school->id)->count());
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

}
