<?php

namespace Tests\Feature\EarlyAccess;

use App\Mail\EarlyAccessAccountMail;
use App\Models\EarlyAccessRequest;
use App\Models\User;
use App\Notifications\EarlyAccessRequestedNotification;
use App\Services\DemoSchoolCommsGuard;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The early-access page gives every address the same reply. An active
 * account is emailed a sign-in link. The page never says which case it was.
 */
class EarlyAccessExistingAccountTest extends TestCase
{
    use RefreshDatabase;

    private const THANKS = 'Thanks, check your email for next steps';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        Cache::flush();
        DemoSchoolCommsGuard::flushCache();
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
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        DemoSchoolCommsGuard::flushCache();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_new_and_existing_emails_get_the_same_response_body(): void
    {
        $this->makeUser('Taken@Example.com', 3, 'Northwind Existing School');

        $new = $this->post('/early-access', $this->payload('newcomer@example.com'));
        $new->assertRedirect(route('early-access.show'));
        $newPage = $this->get($new->headers->get('Location'));

        $existing = $this->post('/early-access', $this->payload('taken@example.com'));
        $existing->assertRedirect(route('early-access.show'));
        $existingPage = $this->get($existing->headers->get('Location'));

        $this->assertSame($new->status(), $existing->status());
        $this->assertSame($new->headers->get('Location'), $existing->headers->get('Location'));
        $this->assertSame($this->normalized($new->getContent()), $this->normalized($existing->getContent()));

        $newPage->assertOk()->assertSee(self::THANKS);
        $existingPage->assertOk()->assertSee(self::THANKS);
        $this->assertSame($this->normalized($newPage->getContent()), $this->normalized($existingPage->getContent()));

        foreach ([$newPage, $existingPage] as $page) {
            $page->assertDontSee('already has a KlassApp account');
            $page->assertDontSee('Existing user');
            $page->assertDontSee('Sign in, or reset your password');
            $page->assertDontSee('Northwind Existing School');
            $page->assertDontSee('northwind-existing-school');
            $page->assertDontSee('data-testid="early-access-existing-account"', false);
        }
    }

    public function test_mail_is_queued_only_for_an_active_account(): void
    {
        $active = $this->makeUser('Taken@Example.com', 3, 'Northwind Existing School');
        $inactive = $this->makeUser('quiet@example.com', 3);
        $inactive->forceFill(['status' => 'inactive'])->save();
        $demo = $this->makeUser('demo.person@demo.klassapp.test', 3);
        $demo->school->forceFill(['is_demo' => 1])->save();
        DemoSchoolCommsGuard::flushCache();

        $this->post('/early-access', $this->payload('newcomer@example.com'))->assertRedirect();
        $this->post('/early-access', $this->payload('quiet@example.com'))->assertRedirect();
        $this->post('/early-access', $this->payload('demo.person@demo.klassapp.test'))->assertRedirect();
        Mail::assertNotQueued(EarlyAccessAccountMail::class);

        $this->post('/early-access', $this->payload('taken@example.com'))->assertRedirect();

        Mail::assertQueued(EarlyAccessAccountMail::class, function (EarlyAccessAccountMail $mail) use ($active) {
            $html = $mail->render();

            return $mail->hasTo($active->email)
                && str_contains($mail->loginUrl, '/login')
                && str_contains($mail->resetUrl, '/password/reset')
                && str_contains($html, $mail->loginUrl)
                && str_contains($html, $mail->resetUrl)
                && ! str_contains($html, 'Northwind Existing School')
                && ! str_contains($html, 'Hillside Academy');
        });
        Mail::assertQueued(EarlyAccessAccountMail::class, 1);
    }

    public function test_existing_email_request_is_stored_and_the_admin_list_shows_the_label(): void
    {
        Notification::fake();

        $platform = $this->makeUser('platform@example.com', 1);
        $this->makeUser('Taken@Example.com', 3);

        $this->post('/early-access', $this->payload('taken@example.com'))
            ->assertRedirect(route('early-access.show'));

        $row = EarlyAccessRequest::where('email', 'taken@example.com')->firstOrFail();
        $this->assertSame('Hillside Academy', $row->school_name);
        $this->assertNotNull($row->consented_at);
        $this->assertTrue($row->isExistingUser());

        Notification::assertSentTo($platform, EarlyAccessRequestedNotification::class);

        $this->get('/early-access')
            ->assertOk()
            ->assertSee(self::THANKS)
            ->assertDontSee('already has a KlassApp account')
            ->assertDontSee('Existing user');

        $this->actingAs($platform)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertSee('taken@example.com')
            ->assertSee('data-testid="early-access-account-'.$row->id.'"', false)
            ->assertSee('data-testid="early-access-existing-user"', false)
            ->assertSee('Existing user');
    }

    public function test_new_email_keeps_the_request_and_shows_no_account_label(): void
    {
        $platform = $this->makeUser('platform@example.com', 1);

        $this->post('/early-access', $this->payload('newcomer@example.com'))
            ->assertRedirect(route('early-access.show'));

        $row = EarlyAccessRequest::sole();
        $this->assertSame('newcomer@example.com', $row->email);
        $this->assertFalse($row->isExistingUser());
        Mail::assertNotQueued(EarlyAccessAccountMail::class);

        $this->get('/early-access')
            ->assertOk()
            ->assertSee('data-testid="early-access-success"', false)
            ->assertSee(self::THANKS)
            ->assertDontSee('already has a KlassApp account')
            ->assertDontSee('Existing user');

        $this->actingAs($platform)
            ->get(route('platform.early-access.index'))
            ->assertOk()
            ->assertSee('newcomer@example.com')
            ->assertDontSee('Existing user')
            ->assertDontSee('data-testid="early-access-existing-user"', false);
    }

    public function test_existing_account_reply_is_rate_limited_with_the_form(): void
    {
        $route = app('router')->getRoutes()->getByName('early-access.store');
        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());

        $this->makeUser('Taken@Example.com', 3, 'Northwind Existing School');
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.76']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/early-access', $this->payload('taken@example.com'))
                ->assertRedirect(route('early-access.show'));
        }

        $blocked = $this->post('/early-access', $this->payload('taken@example.com'));
        $blocked->assertStatus(429);
        $blocked->assertDontSee('Northwind Existing School');
        $blocked->assertDontSee('already has a KlassApp account');
        $blocked->assertDontSee('Existing user');
        $this->assertSame(5, EarlyAccessRequest::where('email', 'taken@example.com')->count());
        Mail::assertQueued(EarlyAccessAccountMail::class, 5);
    }

    private function normalized(string $html): string
    {
        $html = preg_replace('/csrf-token" content="[^"]*"/', 'csrf-token" content="TOKEN"', $html) ?? $html;

        return preg_replace('/name="_token" value="[^"]*"/', 'name="_token" value="TOKEN"', $html) ?? $html;
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
            'name' => 'Person '.$group.' '.$email,
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
