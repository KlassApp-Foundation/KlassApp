<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Onboarding\Steps\ToshiOnboardingV2Driver;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CountryDefaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Foxtrot', 'Democratic Republic of the Congo'] as $i => $name) {
            Country::create([
                'name' => $name,
                'short_name' => 'C'.$i,
                'status' => 1,
                'order' => 1,
            ]);
        }

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 8,
        ]);
        $this->kenya = Country::create([
            'name' => 'Kenya',
            'short_name' => 'KE',
            'status' => 1,
            'order' => 9,
        ]);
    }

    private Country $uganda;

    private Country $kenya;

    public function test_signup_defaults_to_uganda_and_another_country_can_be_saved(): void
    {
        Mail::fake();

        $page = $this->get('/register');
        $page->assertOk();
        $page->assertSee('data-testid="register-country"', false);
        $page->assertSee('value="Uganda"', false);
        $this->assertMatchesRegularExpression(
            '#<option value="Uganda" selected#',
            $page->getContent(),
        );

        $this->post('/register', [
            'name' => 'Grace Nakato',
            'email' => 'grace-country@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ])->assertRedirect();

        $defaultSchool = User::where('email', 'grace-country@example.com')->firstOrFail()->school;
        $this->assertSame('Uganda', $defaultSchool->registration_country);
        $this->assertSame($this->uganda->id, (int) $defaultSchool->country_id);

        $this->post('/register', [
            'name' => 'Amina Kariuki',
            'email' => 'amina-country@example.com',
            'phone' => '0701234568',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
            'country' => 'Kenya',
        ])->assertRedirect();

        $kenyaSchool = User::where('email', 'amina-country@example.com')->firstOrFail()->school;
        $this->assertSame('Kenya', $kenyaSchool->registration_country);
        $this->assertSame($this->kenya->id, (int) $kenyaSchool->country_id);

        $operator = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
            'name' => 'Operator Admin',
            'email' => 'operator-country@example.com',
            'phone' => '0701234569',
            'password' => 'secret123',
            'school_name' => 'Operator Primary',
            'registration_country' => 'France',
        ]);
        $this->assertSame('France', $operator->school->registration_country);
    }

    public function test_wizard_defaults_to_uganda_and_another_country_can_be_saved(): void
    {
        [$school, $admin] = $this->school("Ada's School", 'wizard-country@example.com');

        $component = Livewire::actingAs($admin)->test(ManualOnboardingWizard::class);
        $component->assertSet('countryName', 'Uganda');

        $index = null;
        foreach ($component->get('steps') as $i => $step) {
            if (($step['key'] ?? '') === 'country') {
                $index = $i;
            }
        }
        $this->assertNotNull($index);

        $component->call('goToStep', $index);
        $html = $component->html();
        $this->assertMatchesRegularExpression(
            '#<option[^>]*value="Uganda"[^>]*selected#',
            $html,
        );

        $component->set('countryName', 'Kenya')->call('next');
        $component->assertSet('errorMessage', '');

        $fresh = $school->fresh();
        $this->assertSame('Kenya', $fresh->registration_country);
        $this->assertSame($this->kenya->id, (int) $fresh->country_id);
    }

    public function test_toshi_setup_step_defaults_to_uganda_and_another_country_can_be_saved(): void
    {
        config(['toshi.onboarding_v2' => true]);

        [$school, $admin] = $this->school("Toshi's School", 'toshi-country@example.com');
        $driver = app(ToshiOnboardingV2Driver::class);

        $driver->handleReply($school, 'River Primary', $admin->id);
        $driver->handleReply($school->fresh(), 'Up to 500', $admin->id);

        $prompt = $driver->promptNext($school->fresh(), $admin->id);
        $this->assertSame('country', $prompt['key']);
        $this->assertSame('Uganda', $prompt['options'][0]['value'] ?? null);
        $this->assertStringContainsString('Uganda is selected', (string) $prompt['question']);

        $kept = $driver->handleReply($school->fresh(), '', $admin->id);
        $this->assertNotSame('rejected', $kept['status']);
        $this->assertSame('Uganda', $school->fresh()->registration_country);
        $this->assertSame($this->uganda->id, (int) $school->fresh()->country_id);

        [$other, $otherAdmin] = $this->school("Toshi's Other School", 'toshi-kenya@example.com');
        $otherDriver = app(ToshiOnboardingV2Driver::class);
        $otherDriver->handleReply($other, 'Hill Primary', $otherAdmin->id);
        $otherDriver->handleReply($other->fresh(), 'Up to 500', $otherAdmin->id);
        $chosen = $otherDriver->handleReply($other->fresh(), 'Kenya', $otherAdmin->id);
        $this->assertNotSame('rejected', $chosen['status']);
        $this->assertSame('Kenya', $other->fresh()->registration_country);
        $this->assertSame($this->kenya->id, (int) $other->fresh()->country_id);
    }

    public function test_settings_preselect_uganda_and_a_later_change_needs_confirmation(): void
    {
        [$school, $admin] = $this->school('Settings Primary', 'settings-country@example.com');

        $edit = $this->actingAs($admin)->getJson('/admin/schooldetails/edit/'.$school->id);
        $edit->assertOk();
        $edit->assertJsonPath('details.country_id', null);
        $edit->assertJsonPath('details.default_country_id', $this->uganda->id);
        $this->assertNotSame(7, (int) $this->uganda->id);

        $vue = file_get_contents(resource_path('assets/js/components/schooldetail/Edit.vue'));
        $this->assertIsString($vue);
        $this->assertStringNotContainsString('country_id || 7', $vue);
        $this->assertStringContainsString('default_country_id', $vue);
        $this->assertStringContainsString('confirm_country_change', $vue);
        $this->assertStringContainsString('Changing the country affects phone formats', $vue);

        $payload = [
            'name' => $school->name,
            'moto' => 'Learn well',
            'about_us' => 'A test school',
            'country_id' => $this->uganda->id,
            'ministry_code' => 'EMIS-100',
            'board' => 'uneb',
        ];

        $this->actingAs($admin)
            ->post('/admin/schooldetails/update/'.$school->id, $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame('Uganda', $school->fresh()->registration_country);

        $this->actingAs($admin)
            ->post('/admin/schooldetails/update/'.$school->id, array_merge($payload, [
                'country_id' => $this->kenya->id,
                'ministry_code' => null,
                'board' => 'cambridge',
            ]))
            ->assertSessionHasErrors('confirm_country_change');
        $this->assertSame('Uganda', $school->fresh()->registration_country);
        $this->assertSame($this->uganda->id, (int) $school->fresh()->country_id);

        $this->actingAs($admin)
            ->post('/admin/schooldetails/update/'.$school->id, array_merge($payload, [
                'country_id' => $this->kenya->id,
                'ministry_code' => null,
                'board' => 'cambridge',
                'confirm_country_change' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $school->fresh();
        $this->assertSame('Kenya', $fresh->registration_country);
        $this->assertSame($this->kenya->id, (int) $fresh->country_id);
    }

    public function test_country_confirmation_has_three_cases(): void
    {
        [$school, $admin] = $this->school('Confirm Primary', 'confirm-country@example.com');
        $message = 'Confirm the country change. It affects phone formats and curriculum defaults.';
        $base = [
            'name' => $school->name,
            'moto' => 'Learn well',
            'about_us' => 'A test school',
        ];

        $this->actingAs($admin)
            ->post('/admin/schooldetails/update/'.$school->id, array_merge($base, [
                'country_id' => $this->uganda->id,
                'ministry_code' => 'EMIS-100',
                'board' => 'uneb',
            ]))
            ->assertSessionHasNoErrors();
        $this->assertSame('Uganda', $school->fresh()->registration_country);
        $this->assertSame($this->uganda->id, (int) $school->fresh()->country_id);

        $this->actingAs($admin)
            ->post('/admin/schooldetails/update/'.$school->id, array_merge($base, [
                'country_id' => $this->kenya->id,
                'board' => 'cambridge',
            ]))
            ->assertSessionHasErrors(['confirm_country_change' => $message]);
        $this->assertSame('Uganda', $school->fresh()->registration_country);
        $this->assertSame($this->uganda->id, (int) $school->fresh()->country_id);

        $this->actingAs($admin)
            ->post('/admin/schooldetails/update/'.$school->id, array_merge($base, [
                'country_id' => $this->kenya->id,
                'board' => 'cambridge',
                'confirm_country_change' => '1',
            ]))
            ->assertSessionHasNoErrors();
        $this->assertSame('Kenya', $school->fresh()->registration_country);
        $this->assertSame($this->kenya->id, (int) $school->fresh()->country_id);

        [$named, $namedAdmin] = $this->school('Named Only', 'named-only@example.com');
        $named->forceFill([
            'registration_country' => 'Uganda',
            'country_id' => null,
        ])->save();
        $this->actingAs($namedAdmin)
            ->post('/admin/schooldetails/update/'.$named->id, array_merge($base, [
                'name' => $named->name,
                'country_id' => $this->kenya->id,
                'board' => 'cambridge',
            ]))
            ->assertSessionHasErrors(['confirm_country_change' => $message]);
        $this->assertNull($named->fresh()->country_id);
        $this->assertSame('Uganda', $named->fresh()->registration_country);

        $vue = file_get_contents(resource_path('assets/js/components/schooldetail/Edit.vue'));
        $this->assertStringContainsString('errors.confirm_country_change', $vue);
        $this->assertStringContainsString('data-testid="country-change-error"', $vue);
        $this->assertStringContainsString('status < 400', $vue);
        $this->assertStringContainsString($message, $vue);
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function school(string $name, string $email): array
    {
        $school = School::create([
            'name' => $name,
            'email' => $email,
            'phone' => '0700'.random_int(100000, 999999),
            'slug' => 'country-'.substr(md5($email), 0, 12),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => config('toshi.default_mode'),
            'curriculum' => null,
            'country_id' => null,
            'registration_country' => null,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Country Admin',
            'email' => 'admin-'.$email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Country',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return [$school->fresh(), $admin];
    }
}
