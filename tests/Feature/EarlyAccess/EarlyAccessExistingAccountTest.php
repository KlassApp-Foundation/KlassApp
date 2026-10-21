<?php

namespace Tests\Feature\EarlyAccess;

use App\Models\EarlyAccessRequest;
use App\Models\User;
use App\Notifications\EarlyAccessRequestedNotification;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * An email that already belongs to a KlassApp account still stores an early
 * access request, tells the person to sign in or reset their password, and
 * is labelled Existing user on the platform list. A new email is unchanged.
 */
class EarlyAccessExistingAccountTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'This email already has a KlassApp account. Sign in, or reset your password.';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        Cache::flush();
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        config(['klassapp.signup_enabled' => false]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_existing_email_shows_the_account_message_and_both_links(): void
    {
        $this->makeUser('Taken@Example.com', 3);

        $this->get('/register')
            ->assertOk()
            ->assertSee('data-testid="early-access-form"', false)
            ->assertDontSee(self::MESSAGE);

        $response = $this->followingRedirects()->post('/early-access', $this->payload('taken@example.com'));

        $response->assertOk();
        $response->assertSeeText(self::MESSAGE);
        $response->assertSee('data-testid="early-access-existing-account"', false);
        $response->assertSee('href="'.route('login').'" data-testid="early-access-existing-sign-in"', false);
        $response->assertSee('href="'.route('password.email').'" data-testid="early-access-existing-reset"', false);

        $this->get(route('login'))->assertOk();
        $this->get(route('password.email'))
            ->assertOk()
            ->assertSee('data-testid="preview-reset-request-form"', false);
    }

    public function test_existing_email_request_is_stored_and_flagged_existing_user(): void
    {
        Notification::fake();

        $platform = $this->makeUser('platform@example.com', 1);
        $this->makeUser('Taken@Example.com', 3);

        $this->post('/early-access', $this->payload('taken@example.com'))
            ->assertRedirect(route('early-access.show'));

        $row = EarlyAccessRequest::sole();
        $this->assertSame('taken@example.com', $row->email);
        $this->assertSame('Hillside Academy', $row->school_name);
        $this->assertNotNull($row->consented_at);
        $this->assertTrue($row->isExistingUser());

        Notification::assertSentTo($platform, EarlyAccessRequestedNotification::class);
    }

    public function test_admin_list_shows_existing_user_label(): void
    {
        $this->makeUser('Taken@Example.com', 3);
        $platform = $this->makeUser('platform@example.com', 1);

        $this->post('/early-access', $this->payload('taken@example.com'))->assertRedirect();

        $row = EarlyAccessRequest::sole();

        $this->actingAs($platform)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertSee('taken@example.com')
            ->assertSee('data-testid="early-access-account-'.$row->id.'"', false)
            ->assertSee('data-testid="early-access-existing-user"', false)
            ->assertSee('Existing user');
    }

    public function test_existing_account_message_does_not_reveal_school_or_role(): void
    {
        $user = $this->makeUser('Taken@Example.com', 3, 'Northwind Existing School');
        $user->forceFill([
            'registration_role' => 'Existing Bursar Role',
            'teacher_designations' => ['Existing Bursar Role'],
        ])->save();
        $this->assertSame('Northwind Existing School', $user->school->name);

        $response = $this->followingRedirects()->post('/early-access', $this->payload('taken@example.com'));

        $response->assertOk();
        $response->assertSeeText(self::MESSAGE);
        $response->assertDontSee('Northwind Existing School');
        $response->assertDontSee('northwind-existing-school');
        $response->assertDontSee('Existing Bursar Role');
        $response->assertDontSee('Hillside Academy');
        $response->assertDontSee('Head teacher');

        $platform = $this->makeUser('platform@example.com', 1);
        $this->actingAs($platform)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertSee('Existing user')
            ->assertSee('Hillside Academy')
            ->assertSee('Head teacher')
            ->assertDontSee('Northwind Existing School')
            ->assertDontSee('Existing Bursar Role');
    }

    public function test_existing_account_reply_is_rate_limited_with_the_form(): void
    {
        $route = app('router')->getRoutes()->getByName('early-access.store');
        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());

        $this->makeUser('Taken@Example.com', 3, 'Northwind Existing School');

        // Own address, so this counter is the form's limit and not another test's.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.76']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/early-access', $this->payload('taken@example.com'))
                ->assertRedirect(route('early-access.show'));
        }

        $blocked = $this->post('/early-access', $this->payload('taken@example.com'));
        $blocked->assertStatus(429);
        $blocked->assertDontSee('Northwind Existing School');
        $blocked->assertDontSee('Existing Bursar Role');
        $blocked->assertDontSee(self::MESSAGE);
        $this->assertSame(5, EarlyAccessRequest::count());
    }

    public function test_new_email_is_unchanged(): void
    {
        $platform = $this->makeUser('platform@example.com', 1);

        $this->post('/early-access', $this->payload('newcomer@example.com'))
            ->assertRedirect(route('early-access.show'));

        $row = EarlyAccessRequest::sole();
        $this->assertSame('newcomer@example.com', $row->email);
        $this->assertFalse($row->isExistingUser());

        $this->get('/early-access')
            ->assertOk()
            ->assertSee('data-testid="early-access-success"', false)
            ->assertSee('Thank you. Your request is in, and we will contact you before launch.')
            ->assertDontSee('data-testid="early-access-existing-account"', false)
            ->assertDontSee('This email already has a KlassApp account')
            ->assertDontSee('Existing user');

        $this->actingAs($platform)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertSee('newcomer@example.com')
            ->assertSee('data-testid="early-access-account-'.$row->id.'"', false)
            ->assertDontSee('Existing user')
            ->assertDontSee('data-testid="early-access-existing-user"', false);
    }

    private function payload(string $email): array
    {
        return [
            'school_name' => 'Hillside Academy',
            'contact_name' => 'Alex Example',
            'contact_role' => 'Head teacher',
            'email' => $email,
            'phone' => '+16135550199',
            'country' => 'Canada',
            'approx_students' => '320',
            'consent' => '1',
        ];
    }

    private function makeUser(string $email, int $group, ?string $schoolName = null): User
    {
        $data = [
            'name' => 'Person '.$group,
            'email' => $email,
            'phone' => null,
            'password' => 'secret123',
        ];
        if ($schoolName !== null) {
            $data['school_name'] = $schoolName;
        }

        $user = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator($data);
        $user->forceFill(['usergroup_id' => $group, 'status' => 'active'])->save();

        return $user->fresh();
    }
}
