<?php

namespace Tests\Feature\EarlyAccess;

use App\Models\EarlyAccessRequest;
use App\Models\User;
use App\Notifications\EarlyAccessRequestedNotification;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Task 2: early access page, request storage, admin list and CSV export.
 */
class EarlyAccessPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        // The test cache store is a file store; clear rate limiter hits.
        \Illuminate\Support\Facades\Cache::flush();
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
        config(['klassapp.signup_enabled' => false, 'klassapp.privacy_notice_url' => 'https://example.org/privacy']);
    }

    protected function tearDown(): void
    {
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'school_name' => 'Hillside Academy',
            'contact_name' => 'Alex Example',
            'contact_role' => 'Head teacher',
            'email' => 'alex@example.com',
            'phone' => '+15550100',
            'country' => 'Exampleland',
            'approx_students' => '320',
            'consent' => '1',
        ], $overrides);
    }

    public function test_T2_early_access_page_shows_form_tagline_privacy_link_and_static_countdown_date(): void
    {
        $response = $this->get('/early-access');

        $response->assertOk();
        $response->assertSee('An open education protocol for humans and agents.');
        $response->assertSee('href="https://example.org/privacy"', false);
        $response->assertSee('name="website"', false);
        // Fixed UTC instant, static fallback text without JavaScript.
        $response->assertSee('datetime="2026-12-15T00:00:00Z"', false);
        $response->assertSee('15 December 2026, 00:00 UTC');
        $response->assertSee('prefers-reduced-motion', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertDontSee('Africa');
        $response->assertDontSee('Uganda');
    }

    public function test_T2_early_access_request_is_stored_logged_and_notifies_platform_admins_only(): void
    {
        Notification::fake();
        Log::spy();

        $platform = $this->makeUser('platform@example.com', 1);
        $schoolAdmin = $this->makeUser('schooladmin@example.com', 3);

        $this->post('/early-access', $this->validPayload())
            ->assertRedirect(route('early-access.show'));

        $row = EarlyAccessRequest::sole();
        $this->assertSame('Hillside Academy', $row->school_name);
        $this->assertSame(320, $row->approx_students);
        $this->assertNotNull($row->consented_at);

        Notification::assertSentTo($platform, EarlyAccessRequestedNotification::class);
        Notification::assertNotSentTo($schoolAdmin, EarlyAccessRequestedNotification::class);
        Log::shouldHaveReceived('info')->withArgs(fn ($m) => $m === 'Early access request received')->once();

        $this->get('/early-access')->assertSee('data-testid="early-access-success"', false);
    }

    public function test_T2_early_access_requires_consent(): void
    {
        $this->post('/early-access', $this->validPayload(['consent' => null]))
            ->assertSessionHasErrors('consent');

        $this->assertSame(0, EarlyAccessRequest::count());
    }

    public function test_T2_early_access_spam_trap_drops_the_request(): void
    {
        $this->post('/early-access', $this->validPayload(['website' => 'http://spam.example']))
            ->assertRedirect(route('early-access.show'));

        $this->assertSame(0, EarlyAccessRequest::count());
    }

    public function test_T2_early_access_store_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/early-access', $this->validPayload(['email' => "a{$i}@example.com"]))->assertRedirect();
        }

        $this->post('/early-access', $this->validPayload(['email' => 'late@example.com']))->assertStatus(429);
        $this->assertSame(5, EarlyAccessRequest::count());
    }

    public function test_T2_early_access_list_and_export_are_platform_admin_only(): void
    {
        EarlyAccessRequest::create($this->validPayload(['consented_at' => now(), 'school_name' => '=HYPERLINK("x")']));

        $this->get(route('platform.early-access.index'))->assertRedirect('/login');
        $this->get(route('platform.early-access.export'))->assertRedirect('/login');

        $schoolAdmin = $this->makeUser('schooladmin@example.com', 3);
        $this->actingAs($schoolAdmin)->get(route('platform.early-access.index'))->assertForbidden();
        $this->actingAs($schoolAdmin)->get(route('platform.early-access.export'))->assertForbidden();

        $platform = $this->makeUser('platform@example.com', 1);
        $this->actingAs($platform)->get(route('platform.early-access.index'))
            ->assertOk()->assertSee('alex@example.com');

        $csv = $this->actingAs($platform)->get(route('platform.early-access.export'));
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString('school_name', $body);
        $this->assertStringContainsString('alex@example.com', $body);
        $this->assertStringContainsString("'=HYPERLINK", $body);
    }

    public function test_T2_early_access_migration_is_safe_to_run_twice(): void
    {
        $migration = require database_path('migrations/2026_10_15_000001_create_early_access_requests_table.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('early_access_requests'));
    }

    private function makeUser(string $email, int $group): User
    {
        $user = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
            'name' => 'Person ' . $group,
            'email' => $email,
            'phone' => null,
            'password' => 'secret123',
        ]);
        $user->forceFill(['usergroup_id' => $group, 'status' => 'active'])->save();

        return $user->fresh();
    }
}
