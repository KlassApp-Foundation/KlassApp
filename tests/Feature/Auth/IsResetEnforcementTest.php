<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsResetEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        \DB::table('settings')->insertOrIgnore([
            ['key' => 'login_status', 'value' => '1'],
        ]);
        \Config::set('settings.login_status', 1);

        $this->school = School::create([
            'name' => 'Reset Test School',
            'email' => 'reset-test@test.sch.ug',
            'phone' => '+256700111222',
            'slug' => 'reset-test-school',
            'status' => 1,
        ]);
    }

    public function test_user_with_is_reset_zero_is_not_intercepted(): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Normal Teacher',
            'email' => 'normal-teacher@test.sch.ug',
            'password' => Hash::make('OldPassword!123'),
            'is_reset' => 0,
            'status' => 'active',
        ]);
        $user->userprofile()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'firstname' => 'Normal',
            'lastname' => 'Teacher',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'normal-teacher@test.sch.ug',
            'password' => 'OldPassword!123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_superadmin_with_is_reset_zero_is_not_intercepted(): void
    {
        $user = User::create([
            'school_id' => null,
            'usergroup_id' => 1,
            'name' => 'Super Admin',
            'email' => 'superadmin-reset@test.sch.ug',
            'password' => Hash::make('OldPassword!123'),
            'is_reset' => 0,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'superadmin-reset@test.sch.ug',
            'password' => 'OldPassword!123',
        ]);

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_is_reset_one_is_redirected_to_force_change(): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Fresh Teacher',
            'email' => 'fresh-teacher@test.sch.ug',
            'password' => Hash::make('TempPass123!'),
            'is_reset' => 1,
            'status' => 'active',
        ]);
        $user->userprofile()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'firstname' => 'Fresh',
            'lastname' => 'Teacher',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'fresh-teacher@test.sch.ug',
            'password' => 'TempPass123!',
        ]);

        $response->assertRedirect('/password/force-change');
        $this->assertAuthenticatedAs($user);
    }

    public function test_force_change_form_is_reachable_when_authenticated_and_is_reset_one(): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Fresh Teacher',
            'email' => 'fresh-teacher@test.sch.ug',
            'password' => Hash::make('TempPass123!'),
            'is_reset' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/password/force-change');

        $response->assertOk();
        $response->assertViewIs('auth.force-change-password');
    }

    public function test_force_change_form_redirects_when_is_reset_zero(): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Normal Teacher',
            'email' => 'normal-teacher@test.sch.ug',
            'password' => Hash::make('OldPassword!123'),
            'is_reset' => 0,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/password/force-change');

        $response->assertRedirect('/');
    }

    public function test_force_change_updates_password_and_clears_is_reset(): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Fresh Teacher',
            'email' => 'fresh-teacher@test.sch.ug',
            'password' => Hash::make('TempPass123!'),
            'is_reset' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/password/force-change', [
            'current_password' => 'TempPass123!',
            'password' => 'NewSecurePass1!',
            'password_confirmation' => 'NewSecurePass1!',
        ]);

        $response->assertRedirect('/');

        $user->refresh();
        $this->assertSame(0, (int) $user->is_reset);
        $this->assertTrue(Hash::check('NewSecurePass1!', $user->password));
    }

    public function test_force_change_fails_with_wrong_current_password(): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Fresh Teacher',
            'email' => 'fresh-teacher@test.sch.ug',
            'password' => Hash::make('TempPass123!'),
            'is_reset' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->from('/password/force-change')->post('/password/force-change', [
            'current_password' => 'WrongPassword!',
            'password' => 'NewSecurePass1!',
            'password_confirmation' => 'NewSecurePass1!',
        ]);

        $response->assertRedirect('/password/force-change');
        $response->assertSessionHasErrors(['current_password']);

        $user->refresh();
        $this->assertSame(1, (int) $user->is_reset);
    }

    public function test_force_change_fails_with_weak_password(): void
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Fresh Teacher',
            'email' => 'fresh-teacher@test.sch.ug',
            'password' => Hash::make('TempPass123!'),
            'is_reset' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->from('/password/force-change')->post('/password/force-change', [
            'current_password' => 'TempPass123!',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertRedirect('/password/force-change');
        $response->assertSessionHasErrors(['password']);

        $user->refresh();
        $this->assertSame(1, (int) $user->is_reset);
    }
}
