<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\API\OTPRequest;
use App\Http\Requests\API\ResetPasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InternationalPhoneLookupTest extends TestCase
{
    use RefreshDatabase;

    private School $uganda;

    private School $kenya;

    private User $ugandan;

    private User $kenyan;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->uganda = School::create([
            'name' => 'Lookup Uganda',
            'email' => 'lookup-ug@school.test',
            'phone' => '0700111222',
            'slug' => 'lookup-ug',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);
        $this->kenya = School::create([
            'name' => 'Lookup Kenya',
            'email' => 'lookup-ke@school.test',
            'phone' => '0700111333',
            'slug' => 'lookup-ke',
            'status' => 1,
            'registration_country' => 'Kenya',
            'toshi_enabled' => 0,
        ]);

        $this->ugandan = $this->parent($this->uganda, 'ug-lookup@school.test', 'Ugandan Lookup');
        $this->kenyan = $this->parent($this->kenya, 'ke-lookup@school.test', 'Kenyan Lookup');
    }

    public function test_login_reset_and_one_time_code_do_not_cross_countries(): void
    {
        $kenyanLogin = $this->postJson('/api/parent/login', [
            'email' => '+254712345678',
            'password' => 'password',
            'device_id' => 'device-ke',
        ]);
        $kenyanLogin->assertOk();
        $this->assertSame($this->kenyan->id, $kenyanLogin->json('parent_id'));

        $ugandanLogin = $this->postJson('/api/parent/login', [
            'email' => '+256712345678',
            'password' => 'password',
            'device_id' => 'device-ug',
        ]);
        $ugandanLogin->assertOk();
        $this->assertSame($this->ugandan->id, $ugandanLogin->json('parent_id'));

        $this->ugandan->refresh();
        $this->ugandan->forceFill(['device_id' => null])->saveQuietly();
        $legacyLogin = $this->postJson('/api/parent/login', [
            'email' => '0712345678',
            'password' => 'password',
            'device_id' => 'device-local',
        ]);
        $legacyLogin->assertOk();
        $this->assertSame($this->ugandan->id, $legacyLogin->json('parent_id'));

        $sameCountryReset = $this->passwordResetRequest('+256712345678');
        $this->assertTrue($sameCountryReset['passes'], json_encode($sameCountryReset['errors'] ?? []));
        $this->assertSame($this->ugandan->id, $sameCountryReset['user_id']);

        $crossCountryReset = $this->passwordResetRequest('+254712345678');
        $this->assertTrue($crossCountryReset['passes'], json_encode($crossCountryReset['errors'] ?? []));
        $this->assertSame($this->kenyan->id, $crossCountryReset['user_id']);

        $otp = OTPRequest::create('/api/password/store', 'POST', [
            'mobile_no' => '+254712345678',
            'usergroup' => 7,
            'password' => '000000',
        ]);
        $otp->setContainer($this->app);
        $otp->setRedirector($this->app->make('redirect'));
        $this->assertNotSame($this->ugandan->id, $otp->rules() ? $otp->user?->id : null);
        $this->assertSame($this->kenyan->id, $otp->user?->id);
    }

    public function test_signup_uniqueness_does_not_treat_another_country_as_the_same_number(): void
    {
        $this->kenyan->userprofile()->delete();
        $this->kenyan->delete();

        $cross = $this->signupErrors('+254712345678', 'cross@school.test', 'Kenya');
        $this->assertArrayNotHasKey('phone', $cross);

        $same = $this->signupErrors('+256712345678', 'same@school.test', 'Uganda');
        $this->assertArrayHasKey('phone', $same);
        $this->assertStringContainsString('already registered', $same['phone'][0]);
    }

    private function parent(School $school, string $email, string $name): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => $name,
            'email' => $email,
            'mobile_no' => '0712345678',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        $user->forceFill(['mobile_no' => '0712345678'])->saveQuietly();

        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'firstname' => 'Lookup',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    /**
     * Resolves the reset request without calling the controller, which sends an SMS.
     *
     * @return array{passes: bool, user_id: ?int}
     */
    private function passwordResetRequest(string $mobile): array
    {
        $request = ResetPasswordRequest::create('/api/password/reset', 'POST', [
            'mobile_no' => $mobile,
            'usergroup' => 7,
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $validator = validator($request->all(), $request->rules(), $request->messages());

        return [
            'passes' => $validator->passes(),
            'user_id' => $request->user?->id,
            'errors' => $validator->errors()->toArray(),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function signupErrors(string $phone, string $email, string $country): array
    {
        $request = RegisterRequest::create('/register', 'POST', [
            'name' => 'Signup Parent',
            'email' => $email,
            'phone' => $phone,
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'termsandcondn' => '1',
            'country' => $country,
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        return [];
    }
}
