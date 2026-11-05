<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\API\OTPRequest;
use App\Http\Requests\API\ResetPasswordRequest;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Support\PhoneCountry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A bare local number must not resolve to a person in another country or
 * another school. The same full number still matches the person it belongs to.
 */
class PhoneLookupIsolationTest extends TestCase
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
            'name' => 'Isolation Uganda',
            'email' => 'iso-ug@school.test',
            'phone' => '0000000101',
            'slug' => 'iso-ug',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);
        $this->kenya = School::create([
            'name' => 'Isolation Kenya',
            'email' => 'iso-ke@school.test',
            'phone' => '0000000102',
            'slug' => 'iso-ke',
            'status' => 1,
            'registration_country' => 'Kenya',
            'toshi_enabled' => 0,
        ]);

        $this->ugandan = $this->parent($this->uganda, 'iso-ug@parent.test', 'Ugandan Parent');
        $this->kenyan = $this->parent($this->kenya, 'iso-ke@parent.test', 'Kenyan Parent');
    }

    public function test_login_by_phone_does_not_match_across_countries_or_schools(): void
    {
        $this->assertNotContains('0712345678', PhoneCountry::keys('+254712345678', 'Uganda'));
        $this->assertNotContains('712345678', PhoneCountry::keys('+254712345678', 'Uganda'));

        $kenyan = $this->postJson('/api/parent/login', [
            'email' => '+254712345678',
            'password' => 'password',
            'device_id' => 'device-ke',
        ]);
        $kenyan->assertOk();
        $this->assertSame($this->kenyan->id, $kenyan->json('parent_id'));
        $this->assertNotSame($this->ugandan->id, $kenyan->json('parent_id'));

        $ugandan = $this->postJson('/api/parent/login', [
            'email' => '+256712345678',
            'password' => 'password',
            'device_id' => 'device-ug',
        ]);
        $ugandan->assertOk();
        $this->assertSame($this->ugandan->id, $ugandan->json('parent_id'));
        $this->assertNotSame($this->kenyan->id, $ugandan->json('parent_id'));

        $this->ugandan->refresh();
        $this->ugandan->forceFill(['device_id' => null])->saveQuietly();
        $local = $this->postJson('/api/parent/login', [
            'email' => '0712345678',
            'password' => 'password',
            'device_id' => 'device-local',
        ]);
        $local->assertOk();
        $this->assertSame($this->ugandan->id, $local->json('parent_id'));
        $this->assertNotSame($this->kenyan->id, $local->json('parent_id'));
    }

    public function test_password_reset_by_phone_does_not_match_across_countries_or_schools(): void
    {
        $kenyan = $this->passwordResetUser('+254712345678');
        $this->assertSame($this->kenyan->id, $kenyan);
        $this->assertNotSame($this->ugandan->id, $kenyan);

        $ugandan = $this->passwordResetUser('+256712345678');
        $this->assertSame($this->ugandan->id, $ugandan);
        $this->assertNotSame($this->kenyan->id, $ugandan);

        $local = $this->passwordResetUser('0712345678');
        $this->assertSame($this->ugandan->id, $local);
        $this->assertNotSame($this->kenyan->id, $local);
    }

    public function test_one_time_code_by_phone_does_not_match_across_countries_or_schools(): void
    {
        $kenyan = $this->oneTimeCodeUser('+254712345678');
        $this->assertSame($this->kenyan->id, $kenyan);
        $this->assertNotSame($this->ugandan->id, $kenyan);

        $ugandan = $this->oneTimeCodeUser('+256712345678');
        $this->assertSame($this->ugandan->id, $ugandan);
        $this->assertNotSame($this->kenyan->id, $ugandan);

        $local = $this->oneTimeCodeUser('0712345678');
        $this->assertSame($this->ugandan->id, $local);
        $this->assertNotSame($this->kenyan->id, $local);
    }

    public function test_whatsapp_sender_lookup_does_not_match_across_countries_or_schools(): void
    {
        $ugandanRow = WhatsAppUser::create([
            'phone' => '0712345678',
            'user_id' => $this->ugandan->id,
            'school_id' => $this->uganda->id,
            'opted_in' => true,
        ]);
        $kenyanRow = WhatsAppUser::create([
            'phone' => '712345678',
            'user_id' => $this->kenyan->id,
            'school_id' => $this->kenya->id,
            'opted_in' => true,
        ]);

        $kenyan = WhatsAppUser::findByPhone('+254712345678');
        $this->assertSame($kenyanRow->id, $kenyan?->id);
        $this->assertNotSame($ugandanRow->id, $kenyan?->id);

        $this->assertNull(WhatsAppUser::findByPhone('+254712345678', (int) $this->uganda->id));
        $this->assertSame($kenyanRow->id, WhatsAppUser::findByPhone('+254712345678', (int) $this->kenya->id)?->id);
        $this->assertSame($kenyanRow->id, WhatsAppUser::findByPhone('254712345678', (int) $this->kenya->id)?->id);

        $this->assertNull(WhatsAppUser::findByPhone('0712345678', (int) $this->kenya->id));
        $this->assertSame($ugandanRow->id, WhatsAppUser::findByPhone('+256712345678', (int) $this->uganda->id)?->id);
        $this->assertSame($ugandanRow->id, WhatsAppUser::findByPhone('0712345678', (int) $this->uganda->id)?->id);
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

    private function passwordResetUser(string $mobile): ?int
    {
        $request = ResetPasswordRequest::create('/api/password/reset', 'POST', [
            'mobile_no' => $mobile,
            'usergroup' => 7,
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->rules();

        return $request->user?->id;
    }

    private function oneTimeCodeUser(string $mobile): ?int
    {
        $request = OTPRequest::create('/api/password/store', 'POST', [
            'mobile_no' => $mobile,
            'usergroup' => 7,
            'password' => '000000',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->rules();

        return $request->user?->id;
    }
}
