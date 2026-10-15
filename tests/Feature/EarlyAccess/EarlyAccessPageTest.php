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
        config(['klassapp.signup_enabled' => false, 'services.whatsapp.privacy_notice_url' => 'https://example.org/privacy']);
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
            'country' => 'Canada',
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
        // Global wording: no country or region framing outside the country list.
        $copy = preg_replace('#<select\b.*?</select>#s', '', $response->getContent());
        $this->assertStringNotContainsString('Africa', $copy);
        $this->assertStringNotContainsString('Uganda', $copy);
    }

    public function test_T2_early_access_privacy_link_reads_the_whatsapp_privacy_notice_value(): void
    {
        config(['services.whatsapp.privacy_notice_url' => 'https://example.net/notice']);

        $this->get('/early-access')
            ->assertSee('href="https://example.net/notice"', false)
            ->assertSee('data-testid="early-access-privacy-link"', false);
    }

    public function test_T2_early_access_privacy_link_hidden_when_url_is_empty(): void
    {
        config(['services.whatsapp.privacy_notice_url' => null]);

        $response = $this->get('/early-access');

        $response->assertOk();
        $response->assertDontSee('data-testid="early-access-privacy-link"', false);
        $response->assertDontSee('/privacy-policy', false);
    }

    public function test_T2_early_access_country_is_a_select_list(): void
    {
        $response = $this->get('/early-access');

        $response->assertSee('<select id="ea-country" name="country"', false);
        $response->assertSee('<option value="Canada"', false);
        $response->assertSee('<option value="Other"', false);
        $response->assertDontSee('<input id="ea-country"', false);
    }

    public function test_T2_early_access_country_must_come_from_the_list(): void
    {
        $this->post('/early-access', $this->validPayload(['country' => 'Exampleland']))
            ->assertSessionHasErrors(['country' => 'Please choose your country from the list.']);

        $this->assertSame(0, EarlyAccessRequest::count());
    }

    public function test_T2_early_access_country_list_includes_active_countries_from_the_app(): void
    {
        DB::table('countries')->insert(['name' => 'Testonia', 'short_name' => 'TS', 'iso_code' => 'TST', 'tel_prefix' => '999', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $this->get('/early-access')->assertSee('<option value="Testonia"', false);
    }

    public function test_T2_early_access_validation_messages_are_friendly_for_every_field(): void
    {
        $response = $this->from('/early-access')->post('/early-access', [
            'school_name' => '', 'contact_name' => '', 'contact_role' => '', 'email' => '',
            'phone' => '', 'country' => '', 'approx_students' => '',
        ]);

        $response->assertSessionHasErrors([
            'school_name' => 'Please tell us the name of your school.',
            'contact_name' => 'Please tell us your name.',
            'contact_role' => 'Please tell us your role at the school, for example head teacher.',
            'email' => 'Please enter your email address so we can reply.',
            'phone' => 'Please enter a phone number we can reach you on.',
            'country' => 'Please choose your country.',
            'approx_students' => 'Please tell us roughly how many students you have.',
            'consent' => 'Please tick the box to let us store your details and contact you.',
        ]);

        $page = $this->get('/early-access')->getContent();
        $this->assertStringNotContainsString('The approx students field', $page);
        $this->assertStringNotContainsString('field is required', $page);
    }

    public function test_T2_early_access_errors_show_inline_once_with_one_line_summary(): void
    {
        $this->from('/early-access')->post('/early-access', $this->validPayload(['approx_students' => '', 'email' => 'not-an-email']));

        $page = $this->get('/early-access')->getContent();

        // Each message appears exactly once, next to its field.
        $this->assertSame(1, substr_count($page, 'Please tell us roughly how many students you have.'));
        $this->assertSame(1, substr_count($page, 'Please enter a valid email address, like name@example.com.'));
        $this->assertStringContainsString('id="ea-approx_students-error"', $page);
        $this->assertStringContainsString('aria-describedby="ea-email-error"', $page);
        // One-line summary linking to the first field with an error, no message list.
        $this->assertSame(1, substr_count($page, 'data-testid="early-access-error-summary"'));
        $this->assertStringContainsString('<a href="#ea-email">Go to the first one</a>', $page);
        $this->assertStringNotContainsString('data-testid="auth-flash-error"', $page);
    }

    public function test_T2_early_access_countdown_uses_singular_and_plural_units(): void
    {
        $node = trim((string) shell_exec('command -v node 2>/dev/null'));
        if ($node === '') {
            $this->markTestSkipped('node is not installed');
        }

        $page = $this->get('/early-access')->getContent();
        $this->assertSame(1, preg_match('#/\* ea-countdown-label:start \*/(.*?)/\* ea-countdown-label:end \*/#s', $page, $m));

        $cases = [
            // ms => [withSeconds, expected]
            [1000, true, '0 days, 0 hours, 0 minutes, 1 second to go'],
            [2000, true, '0 days, 0 hours, 0 minutes, 2 seconds to go'],
            [86400000 + 3600000 + 60000 + 1000, true, '1 day, 1 hour, 1 minute, 1 second to go'],
            [2 * 86400000 + 2 * 3600000 + 2 * 60000 + 2000, true, '2 days, 2 hours, 2 minutes, 2 seconds to go'],
            [86400000 + 3600000 + 60000 + 1000, false, '1 day, 1 hour, 1 minute to go'],
            [0, true, 'Early access is open.'],
        ];

        $script = $m[1] . "\nvar cases = " . json_encode($cases) . ";\n"
            . "process.stdout.write(JSON.stringify(cases.map(function (c) { return eaCountdownLabel(c[0], c[1]); })));\n";
        $file = tempnam(sys_get_temp_dir(), 'ea-countdown-') . '.js';
        file_put_contents($file, $script);

        try {
            $out = shell_exec(escapeshellarg($node) . ' ' . escapeshellarg($file));
        } finally {
            @unlink($file);
        }

        $this->assertSame(array_column($cases, 2), json_decode((string) $out, true));
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
        $migration = require database_path('migrations/2026_10_15_093000_create_early_access_requests_table.php');
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
