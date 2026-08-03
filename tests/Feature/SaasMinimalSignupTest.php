<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Services\OnboardingStepsService;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SaasMinimalSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_email_password_signup_creates_placeholder_school_and_lands_in_toshi(): void
    {
        $response = $this->post('/register', [
            'name' => 'Grace Nakato',
            'email' => 'grace@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ]);

        $response->assertRedirect('/admin/dashboard?toshi_onboarding=1');
        $this->assertAuthenticated();

        $user = User::where('email', 'grace@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(3, (int) $user->usergroup_id);
        $this->assertSame('+256701234567', $user->mobile_no);
        $this->assertSame(1, (int) $user->email_verified);

        $school = School::find($user->school_id);
        $this->assertNotNull($school);
        $this->assertSame("Grace's School", $school->name);
        $this->assertNull($school->curriculum);
        $this->assertSame(1, (int) $school->toshi_enabled);
        $this->assertFalse(AcademicYear::where('school_id', $school->id)->exists());

        $this->assertTrue(OnboardingStepsService::isStepComplete('school_name', $school) === false);
        $this->assertFalse(OnboardingStepsService::isStepComplete('curriculum', $school));
        $this->assertFalse(OnboardingStepsService::isStepComplete('academic_year', $school));

        $keys = array_column(OnboardingStepsService::incompleteSteps($school, $user->id), 'key');
        $this->assertSame('school_name', $keys[0]);
        $this->assertContains('curriculum', $keys);
        $academicIdx = array_search('academic_year', $keys, true);
        $standardsIdx = array_search('standards', $keys, true);
        $this->assertNotFalse($academicIdx);
        $this->assertNotFalse($standardsIdx);
        $this->assertLessThan($standardsIdx, $academicIdx, 'Academic year must be asked before classes');
    }

    public function test_phone_is_required_on_signup_form(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Grace Nakato',
            'email' => 'grace2@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('phone');
        $this->assertNull(User::where('email', 'grace2@example.com')->first());
    }

    public function test_placeholder_school_names_do_not_collide_for_same_first_name(): void
    {
        $bootstrap = app(SchoolSignupBootstrapService::class);

        $first = $bootstrap->bootstrap([
            'name' => 'Grace A',
            'email' => 'grace-a@example.com',
            'phone' => '0701111111',
            'password' => 'secret123',
            'email_verified' => true,
        ]);

        $second = $bootstrap->bootstrap([
            'name' => 'Grace B',
            'email' => 'grace-b@example.com',
            'phone' => '0702222222',
            'password' => 'secret123',
            'email_verified' => true,
        ]);

        $this->assertSame("Grace's School", $first->school->name);
        $this->assertSame("Grace's School-2", $second->school->name);
    }

    public function test_google_oauth_signup_uses_same_bootstrap_shape(): void
    {
        session([
            'saas_signup' => [
                'name' => 'Okello Dan',
                'email' => 'okello@example.com',
                'phone' => '+256703333333',
            ],
        ]);

        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-123');
        $abstractUser->shouldReceive('getEmail')->andReturn('okello@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Okello Dan');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/a.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/admin/dashboard?toshi_onboarding=1');
        $this->assertAuthenticated();

        $user = User::where('email', 'okello@example.com')->first();
        $this->assertNotNull($user);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'google_id')) {
            $this->assertSame('google-123', $user->google_id);
        }

        $school = School::find($user->school_id);
        $this->assertSame("Okello's School", $school->name);
        $this->assertNull($school->curriculum);
        $this->assertSame(1, (int) $school->toshi_enabled);
        $this->assertFalse(AcademicYear::where('school_id', $school->id)->exists());
        $this->assertSame('+256703333333', $school->phone);
    }

    public function test_curriculum_null_is_not_treated_as_complete(): void
    {
        $school = School::create([
            'name' => "Ada's School",
            'email' => 'ada@test.sch.ug',
            'phone' => '+256704444444',
            'slug' => 'adas-school',
            'status' => 1,
            'toshi_enabled' => 1,
            'curriculum' => null,
        ]);

        $this->assertFalse(OnboardingStepsService::isStepComplete('curriculum', $school));
        $this->assertTrue(OnboardingStepsService::isPlaceholderSchoolName($school->name));
        $this->assertFalse(OnboardingStepsService::isStepComplete('school_name', $school));
    }
}
