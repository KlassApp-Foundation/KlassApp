<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Requests\RegisterRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Signup keeps both behaviours: the country default and countries-table check,
 * and phone numbers normalised with the chosen country as the dialling region.
 * A number that already has a country code is accepted for any chosen country.
 */
class RegisterRequestPhoneCountryTest extends TestCase
{
    use RefreshDatabase;

    private Country $uganda;

    private Country $kenya;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);
        $this->kenya = Country::create([
            'name' => 'Kenya',
            'short_name' => 'KE',
            'status' => 1,
            'order' => 2,
        ]);
    }

    public function test_blank_country_defaults_to_uganda_and_the_number_is_stored_as_e164(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Grace Nakato',
            'email' => 'grace-phone-country@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ])->assertRedirect();

        $user = User::where('email', 'grace-phone-country@example.com')->firstOrFail();
        $this->assertSame('+256701234567', $user->mobile_no);
        $this->assertSame('Uganda', $user->school->registration_country);
        $this->assertSame($this->uganda->id, (int) $user->school->country_id);
    }

    public function test_chosen_country_supplies_the_dialling_code_and_another_country_number_is_accepted(): void
    {
        $kenyaLocal = $this->validated([
            'phone' => '0712 345 678',
            'country' => 'Kenya',
            'email' => 'ke-local@example.com',
        ]);
        $this->assertSame('+254712345678', $kenyaLocal['phone']);
        $this->assertSame('Kenya', $kenyaLocal['country']);

        $foreignOnKenya = $this->validated([
            'phone' => '+1 415 555 2671',
            'country' => 'Kenya',
            'email' => 'us-on-ke@example.com',
        ]);
        $this->assertSame('+14155552671', $foreignOnKenya['phone']);
        $this->assertSame('Kenya', $foreignOnKenya['country']);

        $kenyaOnUganda = $this->validated([
            'phone' => '+254712345678',
            'country' => 'Uganda',
            'email' => 'ke-on-ug@example.com',
        ]);
        $this->assertSame('+254712345678', $kenyaOnUganda['phone']);
        $this->assertSame('Uganda', $kenyaOnUganda['country']);
    }

    public function test_unknown_country_is_rejected_and_a_code_or_id_becomes_the_country_name(): void
    {
        $errors = $this->errors([
            'phone' => '+256701234567',
            'country' => 'Narnia',
            'email' => 'narnia@example.com',
        ]);
        $this->assertArrayHasKey('country', $errors);
        $this->assertStringContainsString('Choose a country from the list.', $errors['country'][0]);
        $this->assertArrayNotHasKey('phone', $errors);

        $byCode = $this->validated([
            'phone' => '0712345678',
            'country' => 'KE',
            'email' => 'by-code@example.com',
        ]);
        $this->assertSame('Kenya', $byCode['country']);
        $this->assertSame('+254712345678', $byCode['phone']);

        $byId = $this->validated([
            'phone' => '+14155552671',
            'country' => (string) $this->kenya->id,
            'email' => 'by-id@example.com',
        ]);
        $this->assertSame('Kenya', $byId['country']);
        $this->assertSame('+14155552671', $byId['phone']);
    }

    public function test_an_invalid_number_is_rejected_for_the_chosen_country(): void
    {
        $errors = $this->errors([
            'phone' => '12345',
            'country' => 'Kenya',
            'email' => 'bad-phone@example.com',
        ]);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayNotHasKey('country', $errors);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, mixed>
     */
    private function validated(array $overrides): array
    {
        $request = $this->request($overrides);

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            $this->fail('RegisterRequest rejected a valid signup: '.json_encode($e->errors()));
        }

        return $request->validated();
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, list<string>>
     */
    private function errors(array $overrides): array
    {
        $request = $this->request($overrides);

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        return [];
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function request(array $overrides): RegisterRequest
    {
        $request = RegisterRequest::create('/register', 'POST', array_merge([
            'name' => 'Signup Admin',
            'email' => 'signup@example.com',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsandcondn' => '1',
        ], $overrides));
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        return $request;
    }
}
