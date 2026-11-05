<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SchoolAdminSetupLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('settings')->insertOrIgnore([
            ['key' => 'login_status', 'value' => '1'],
        ]);
        config(['settings.login_status' => 1]);

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_signup_email_verification_then_first_login_lands_on_setup_step_one(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Grace Nakato',
            'email' => 'grace@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ])->assertRedirect(route('register.verify'));

        $this->assertGuest();

        $code = null;
        Mail::assertQueued(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertNotNull($code);

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $step = OnboardingEngine::earliestUnfinishedStep($user->school, $user->id);
        $destination = OnboardingEngine::setupDestination($user->school, $user->id);

        $this->assertSame('school_name', $step['key'] ?? null);
        $this->assertNotNull($destination);
        $this->assertStringContainsString('step=school_name', $destination);

        $this->post('/register/verify', ['code' => $code])
            ->assertRedirect($destination);
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect();
        $this->assertGuest();

        $login = $this->post('/login', [
            'email' => 'grace@example.com',
            'password' => 'secret123',
        ]);

        $login->assertRedirect($destination);
        $this->assertAuthenticated();
        $this->assertStringContainsString('step=school_name', (string) $login->headers->get('Location'));

        $this->get($destination)
            ->assertOk()
            ->assertSee('data-testid="manual-wizard-page"', false);
    }

    public function test_partly_set_up_school_admin_login_lands_on_the_earliest_unfinished_step(): void
    {
        [$school, $admin] = $this->schoolAdmin('Riverside Primary', 'riverside@setup-login.test');
        $school->forceFill(['curriculum' => null])->save();

        $step = OnboardingEngine::earliestUnfinishedStep($school->fresh(), $admin->id);
        $destination = OnboardingEngine::setupDestination($school->fresh(), $admin->id);

        $this->assertSame('student_size', $step['key'] ?? null);
        $this->assertNotNull($destination);
        $this->assertStringContainsString('step=student_size', $destination);

        $login = $this->withSession(['url.intended' => '/admin/dashboard'])->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $login->assertRedirect($destination);
        $this->assertStringNotContainsString('/admin/dashboard', (string) $login->headers->get('Location'));

        $this->get($destination)
            ->assertOk()
            ->assertSee('data-testid="manual-wizard-page"', false);
    }

    public function test_finished_school_admin_login_lands_on_the_dashboard(): void
    {
        [$school, $admin] = $this->schoolAdmin('Finished Primary', 'finished@setup-login.test');
        $school->forceFill(['onboarding_finished_at' => now()])->save();

        $this->assertNull(OnboardingEngine::setupDestination($school->fresh(), $admin->id));

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_teacher_login_is_unchanged(): void
    {
        [$school] = $this->schoolAdmin('Unfinished Primary', 'unfinished-teacher@setup-login.test');

        $teacher = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Subject Teacher',
            'email' => 'subject.teacher@setup-login.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
            'email_verified_at' => now(),
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Subject',
            'lastname' => 'Teacher',
            'status' => 'active',
        ]);

        $this->assertNotNull(OnboardingEngine::setupDestination($school->fresh(), $teacher->id));

        $login = $this->post('/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $login->assertRedirect('/teacher/dashboard');
        $this->assertStringNotContainsString('onboarding/wizard', (string) $login->headers->get('Location'));
        $this->assertAuthenticatedAs($teacher);
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function schoolAdmin(string $name, string $email): array
    {
        $school = School::create([
            'name' => $name,
            'email' => $email,
            'phone' => '0700'.random_int(100000, 999999),
            'slug' => 'setup-login-'.substr(md5($email), 0, 12),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => config('toshi.default_mode'),
            'curriculum' => null,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Setup Admin',
            'email' => 'admin-'.$email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
            'email_verified_at' => now(),
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Setup',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return [$school->fresh(), $admin->fresh()];
    }
}
