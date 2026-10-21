<?php

namespace Tests\Feature\Invites;

use App\Models\Country;
use App\Models\EarlyAccessRequest;
use App\Models\User;
use App\Services\SchoolInviteService;
use App\Support\PhoneCountry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SchoolInvitePhoneFormatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

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
            'email' => 'platform-phone@klassapp.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 1,
            'status' => 'active',
        ]);
    }

    private function earlyAccess(string $email, string $country): EarlyAccessRequest
    {
        return EarlyAccessRequest::create([
            'school_name' => 'Greenfield Primary',
            'contact_name' => 'Grace Nakato',
            'contact_role' => 'Head Teacher',
            'email' => $email,
            'phone' => '0701234567',
            'country' => $country,
            'approx_students' => 420,
            'consented_at' => now(),
        ]);
    }

    private function claim(string $email, string $country, string $phone): \Illuminate\Testing\TestResponse
    {
        $admin = $this->platformAdmin();
        ['token' => $token] = SchoolInviteService::issue($this->earlyAccess($email, $country), $admin);

        return $this->post(route('school-invite.claim', $token), [
            'name' => 'Grace Nakato',
            'phone' => $phone,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);
    }

    public function test_invite_page_shows_the_country_example_before_typing(): void
    {
        $admin = $this->platformAdmin();
        ['token' => $token] = SchoolInviteService::issue(
            $this->earlyAccess('hint@school.test', 'Uganda'),
            $admin,
        );

        $this->get(route('school-invite.show', $token))
            ->assertOk()
            ->assertSee('data-testid="invite-phone-hint"', false)
            ->assertSee('Your WhatsApp number, e.g. +256 765 274 289', false);
    }

    public function test_invite_claim_accepts_ugandan_local_0765274289(): void
    {
        $this->claim('local076@school.test', 'Uganda', '0765274289')
            ->assertRedirect(route('onboarding.choice'));

        $user = User::where('email', 'local076@school.test')->firstOrFail();
        $this->assertSame('+256765274289', $user->mobile_no);
        $this->assertSame('+256765274289', $user->school->phone);
    }

    /** @return array<string, array{string}> */
    public static function acceptedUgandanNumbers(): array
    {
        return [
            'local with spaces' => ['0765 274 289'],
            'international with plus' => ['+256765274289'],
            'international with plus and spaces' => ['+256 765 274 289'],
            'international with plus and dashes' => ['+256-765-274-289'],
            'international without plus' => ['256765274289'],
            'international without plus and spaces' => ['256 765 274 289'],
            'international without plus and dashes' => ['256-765-274-289'],
        ];
    }

    #[DataProvider('acceptedUgandanNumbers')]
    public function test_invite_claim_accepts_international_and_spaced_formats(string $phone): void
    {
        $email = 'fmt-'.md5($phone).'@school.test';

        $this->claim($email, 'Uganda', $phone)
            ->assertRedirect(route('onboarding.choice'));

        $this->assertSame('+256765274289', User::where('email', $email)->value('mobile_no'));
    }

    public function test_invite_claim_rejects_an_invalid_number_with_the_country_example(): void
    {
        $admin = $this->platformAdmin();
        ['token' => $token] = SchoolInviteService::issue(
            $this->earlyAccess('bad-phone@school.test', 'Uganda'),
            $admin,
        );

        $this->from(route('school-invite.show', $token))
            ->post(route('school-invite.claim', $token), [
                'name' => 'Grace Nakato',
                'phone' => '12345',
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])
            ->assertRedirect(route('school-invite.show', $token))
            ->assertSessionHasErrors([
                'phone' => 'Enter your WhatsApp number with the country code, e.g. +256 765 274 289.',
            ]);

        $this->assertNull(User::where('email', 'bad-phone@school.test')->first());
    }

    public function test_invite_claim_uses_the_invite_country_not_a_uganda_default(): void
    {
        // The countries table still has a placeholder prefix for Kenya. The
        // calling code must come from the country list, not that column.
        Country::create([
            'name' => 'Kenya',
            'short_name' => 'KE',
            'iso_code' => 'KEN',
            'tel_prefix' => '355',
            'status' => 1,
        ]);

        $this->assertSame('254', PhoneCountry::spec('Kenya')['code']);

        $this->claim('kenya@school.test', 'Kenya', '0712 345 678')
            ->assertRedirect(route('onboarding.choice'));

        $this->assertSame('+254712345678', User::where('email', 'kenya@school.test')->value('mobile_no'));
    }
}
