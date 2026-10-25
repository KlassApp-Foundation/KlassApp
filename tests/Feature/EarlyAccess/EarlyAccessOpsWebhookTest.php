<?php

namespace Tests\Feature\EarlyAccess;

use App\Mail\SchoolInviteMail;
use App\Models\EarlyAccessRequest;
use App\Models\Plan;
use App\Models\SchoolInvite;
use App\Models\User;
use App\Services\EarlyAccessOpsWebhook;
use App\Services\SchoolInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Ops webhook for early-access created, approved, invited, and claimed.
 * The body is signed. Personal data stays out of the log and the job row.
 */
class EarlyAccessOpsWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://hooks.example.test/klassapp/ops?token=n8n-query-token-not-for-logs';

    private const SECRET = 'ops-webhook-test-secret';

    private const EMAIL = 'pii-contact@example.test';

    private const SCHOOL = 'PiiSchoolName';

    private const CONTACT = 'PiiContactName';

    private const PHONE = '0701234567';

    /** @var list<string> */
    private array $logLines = [];

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        Cache::flush();
        config(['app.timezone' => 'Africa/Kampala', 'queue.default' => 'database']);
        date_default_timezone_set('Africa/Kampala');
        Carbon::setTestNow(Carbon::parse('2026-10-25 15:04:05', 'Africa/Kampala'));

        Plan::create([
            'name' => 'growth',
            'display_name' => 'Growth',
            'cycle' => 30,
            'amount' => 35,
            'is_active' => 1,
            'order' => 2,
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        config(['klassapp.signup_enabled' => false]);
        $this->configureWebhook();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_each_transition_posts_the_request_with_a_signature_that_verifies(): void
    {
        Mail::fake();
        $this->fakeOk();

        $this->post('/early-access', $this->payload())->assertRedirect(route('early-access.show'));
        Http::assertNothingSent();
        $row = EarlyAccessRequest::sole();
        $this->assertSame(1, $this->opsJobCount());
        $this->assertJobRowHasNoPersonalData();
        $this->workOps();
        $this->assertSentEvent('created', $row);

        $this->freshHttp();
        $this->actingAs($this->platformAdmin())
            ->post(route('platform.early-access.invite', $row))
            ->assertRedirect()
            ->assertSessionHas('success');
        Http::assertNothingSent();
        $this->assertSame(2, $this->opsJobCount());
        $this->assertSame($row->id, (int) SchoolInvite::sole()->early_access_request_id);
        $this->workOps();
        $this->assertSentEvent('approved', $row);
        $this->assertSentEvent('invited', $row);

        $mail = Mail::queued(SchoolInviteMail::class)->first();
        $this->assertNotNull($mail);
        $token = basename((string) parse_url($mail->inviteUrl, PHP_URL_PATH));

        $this->freshHttp();
        auth()->logout();
        $this->post(route('school-invite.claim', $token), [
            'name' => 'Grace Nakato',
            'phone' => self::PHONE,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('onboarding.choice'));
        Http::assertNothingSent();
        $this->assertSame(1, $this->opsJobCount());
        $this->workOps();
        $this->assertSentEvent('claimed', $row);
        Http::assertSentCount(1);
    }

    public function test_a_resend_sends_invited_again_and_does_not_send_approved_again(): void
    {
        Mail::fake();
        $this->fakeOk();
        $row = $this->storeRequest();
        $this->workOps();

        $this->freshHttp();
        $this->actingAs($this->platformAdmin())
            ->post(route('platform.early-access.invite', $row))
            ->assertRedirect();
        $this->workOps();

        $invite = SchoolInvite::sole();
        $this->freshHttp();
        $this->actingAs($this->platformAdmin())
            ->post(route('platform.school-invites.resend', $invite))
            ->assertRedirect();

        $this->assertSame(1, $this->opsJobCount());
        $this->workOps();
        $this->assertSentEvent('invited', $row);
        Http::assertSentCount(1);
    }

    public function test_nothing_is_queued_when_the_url_is_unset(): void
    {
        $this->listenToLogs();
        config([
            'services.ops_webhook.url' => null,
            'services.ops_webhook.secret' => self::SECRET,
        ]);

        $this->post('/early-access', $this->payload())->assertRedirect(route('early-access.show'));

        $this->assertSame(1, EarlyAccessRequest::count());
        $this->assertSame(0, $this->opsJobCount());
        Http::assertNothingSent();
        $this->assertStringNotContainsString('ops webhook', implode("\n", $this->logLines));
    }

    public function test_a_missing_secret_skips_the_send_and_logs_without_personal_data(): void
    {
        $this->listenToLogs();
        config([
            'services.ops_webhook.url' => self::URL,
            'services.ops_webhook.secret' => '   ',
        ]);

        $this->post('/early-access', $this->payload())->assertRedirect(route('early-access.show'));

        $this->assertSame(0, $this->opsJobCount());
        Http::assertNothingSent();
        $this->assertStringContainsString('secret is not set', implode("\n", $this->logLines));
        $this->assertLogsHaveNoPersonalData();
    }

    public function test_an_http_url_is_not_sent_and_the_url_is_not_logged(): void
    {
        $this->listenToLogs();
        config([
            'services.ops_webhook.url' => 'http://hooks.example.test/ops?token=n8n-query-token-not-for-logs',
            'services.ops_webhook.secret' => self::SECRET,
        ]);

        $this->post('/early-access', $this->payload())->assertRedirect();

        $this->assertSame(0, $this->opsJobCount());
        Http::assertNothingSent();
        $this->assertStringContainsString('URL is not https', implode("\n", $this->logLines));
        $this->assertLogsHaveNoPersonalData();
    }

    public function test_a_failing_endpoint_retries_and_does_not_break_the_request(): void
    {
        $this->listenToLogs();
        Http::fake([
            'hooks.example.test/*' => Http::response('leaked '.self::EMAIL.' '.self::SECRET, 500),
        ]);

        $response = $this->post('/early-access', $this->payload());

        $response->assertRedirect(route('early-access.show'));
        $this->assertSame(1, EarlyAccessRequest::count());
        Http::assertNothingSent();
        $this->assertSame(1, $this->opsJobCount());
        $this->assertJobRowHasNoPersonalData();

        $this->workOps();
        $this->assertReleasedFor(10);
        Carbon::setTestNow(now()->addSeconds(10));
        $this->workOps();
        $this->assertReleasedFor(30);
        Carbon::setTestNow(now()->addSeconds(30));
        $this->workOps();
        $this->assertReleasedFor(60);
        Carbon::setTestNow(now()->addSeconds(60));
        $this->workOps();
        $this->assertReleasedFor(120);
        Carbon::setTestNow(now()->addSeconds(120));
        $this->workOps();

        $this->assertSame(0, $this->opsJobCount());
        $this->assertSame(1, DB::table('failed_jobs')->count());
        $this->assertCount(5, Http::recorded());
        $bodies = [];
        foreach (Http::recorded() as [$request]) {
            $bodies[] = $request->body();
        }
        $this->assertCount(1, array_unique($bodies));

        $text = implode("\n", $this->logLines);
        $this->assertStringContainsString('http_status', $text);
        $this->assertStringContainsString('500', $text);
        $this->assertStringContainsString('exhausted retries', $text);
        $this->assertLogsHaveNoPersonalData();
        $this->assertSame(1, EarlyAccessRequest::count());
    }

    public function test_a_redirect_is_not_followed(): void
    {
        Http::fake([
            'hooks.example.test/*' => Http::response('no', 302, ['Location' => 'http://evil.example/steal']),
        ]);
        $row = $this->makeRequest(self::EMAIL);

        try {
            app(EarlyAccessOpsWebhook::class)->deliver($row->id, 'created', now()->utc()->getTimestamp());
            $this->fail('A redirect must not count as delivered.');
        } catch (RuntimeException $e) {
            $this->assertSame('Early access ops webhook failed with HTTP 302', $e->getMessage());
        }

        $this->assertCount(1, Http::recorded());
        $this->assertSame(self::URL, Http::recorded()[0][0]->url());
    }

    public function test_a_connection_failure_retries_and_logs_the_exception_class_only(): void
    {
        $this->listenToLogs();
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException(
                'cURL error 28 for '.self::URL.' secret '.self::SECRET.' email '.self::EMAIL
            );
        });

        $this->post('/early-access', $this->payload())->assertRedirect();
        Http::assertNothingSent();

        $this->workOps();
        Carbon::setTestNow(now()->addSeconds(10));
        $this->workOps();
        Carbon::setTestNow(now()->addSeconds(30));
        $this->workOps();
        Carbon::setTestNow(now()->addSeconds(60));
        $this->workOps();
        Carbon::setTestNow(now()->addSeconds(120));
        $this->workOps();

        $this->assertSame(5, $attempts);
        $this->assertSame(1, DB::table('failed_jobs')->count());
        $text = implode("\n", $this->logLines);
        $this->assertStringContainsString(ConnectionException::class, $text);
        $this->assertLogsHaveNoPersonalData();
    }

    public function test_dispatch_throwing_does_not_fail_the_request(): void
    {
        $this->listenToLogs();
        Schema::drop('jobs');

        $this->post('/early-access', $this->payload())
            ->assertRedirect(route('early-access.show'));

        $this->assertSame(1, EarlyAccessRequest::count());
        Http::assertNothingSent();
        $text = implode("\n", $this->logLines);
        $this->assertStringContainsString('dispatch failed', $text);
        $this->assertStringContainsString('QueryException', $text);
        $this->assertLogsHaveNoPersonalData();
    }

    public function test_a_rolled_back_claim_does_not_queue_claimed(): void
    {
        Mail::fake();
        $this->fakeOk();
        $row = $this->storeRequest();
        $this->workOps();
        $this->actingAs($this->platformAdmin())
            ->post(route('platform.early-access.invite', $row))
            ->assertRedirect();
        $this->workOps();

        // The webhook is queued, then the offer runs. Without Growth the offer
        // throws after that dispatch, and the claim transaction rolls back.
        Plan::query()->whereRaw('LOWER(name) = ?', ['growth'])->delete();

        $mail = Mail::queued(SchoolInviteMail::class)->first();
        $token = basename((string) parse_url($mail->inviteUrl, PHP_URL_PATH));
        $this->freshHttp();
        auth()->logout();

        $sql = $this->captureSql(function () use ($token) {
            $this->post(route('school-invite.claim', $token), [
                'name' => 'Grace Nakato',
                'phone' => self::PHONE,
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])->assertRedirect()
                ->assertSessionHasErrors('invite');
        });
        $this->assertTrue(
            collect($sql)->contains(fn (string $statement) => str_contains(strtolower($statement), 'early_access_requests')),
            'The claim should look the request up before the later failure rolls the transaction back.'
        );

        $this->assertNull(SchoolInvite::sole()->claimed_at);
        $this->assertNull(User::where('email', self::EMAIL)->first());
        $this->assertSame(0, $this->opsJobCount());
        Http::assertNothingSent();
    }

    public function test_a_lookup_failure_during_claim_still_commits(): void
    {
        Mail::fake();
        $this->fakeOk();
        $row = $this->storeRequest();
        $this->workOps();
        $this->actingAs($this->platformAdmin())
            ->post(route('platform.early-access.invite', $row))
            ->assertRedirect();
        $this->workOps();

        $mail = Mail::queued(SchoolInviteMail::class)->first();
        $token = basename((string) parse_url($mail->inviteUrl, PHP_URL_PATH));
        $this->freshHttp();
        auth()->logout();
        $this->listenToLogs();

        EarlyAccessRequest::addGlobalScope('broken-lookup', function () {
            throw new \RuntimeException('lookup failed '.self::EMAIL.' '.self::SECRET);
        });

        try {
            $this->post(route('school-invite.claim', $token), [
                'name' => 'Grace Nakato',
                'phone' => self::PHONE,
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])->assertRedirect(route('onboarding.choice'));
        } finally {
            $this->removeBrokenLookupScope();
        }

        $this->assertNotNull(SchoolInvite::sole()->claimed_at);
        $this->assertNotNull(User::where('email', self::EMAIL)->first());
        $this->assertSame(0, $this->opsJobCount());
        Http::assertNothingSent();

        $webhookLines = array_values(array_filter(
            $this->logLines,
            fn (string $line) => str_contains($line, 'ops webhook')
        ));
        $text = implode("\n", $webhookLines);
        $this->assertStringContainsString('Early access ops webhook skipped', $text);
        $this->assertStringContainsString('RuntimeException', $text);
        $this->assertStringContainsString('event=claimed', $text);
        foreach ($this->personalMarkers() as $marker) {
            $this->assertStringNotContainsString($marker, $text);
        }
    }

    public function test_a_disabled_webhook_does_not_look_up_the_request(): void
    {
        config([
            'services.ops_webhook.url' => null,
            'services.ops_webhook.secret' => self::SECRET,
        ]);
        Mail::fake();

        $row = $this->makeRequest(self::EMAIL);
        $issued = SchoolInviteService::issue($row, $this->platformAdmin());

        $duringEmail = $this->captureSql(function () use ($issued) {
            $this->assertTrue(SchoolInviteService::sendEmail($issued['invite'], $issued['token']));
        });
        $this->assertNotContainsEarlyAccessLookup($duringEmail);

        $duringClaim = $this->captureSql(function () use ($issued) {
            $claim = SchoolInviteService::claim($issued['invite']->fresh(), [
                'name' => 'Grace Nakato',
                'phone' => '+256701234567',
                'password' => 'StrongPass123!',
            ]);
            $this->assertTrue($claim['success']);
        });
        $this->assertNotContainsEarlyAccessLookup($duringClaim);
        $this->assertNotNull($issued['invite']->fresh()->claimed_at);
        $this->assertSame(0, $this->opsJobCount());
    }

    public function test_an_old_invite_with_two_requests_for_the_email_does_not_guess(): void
    {
        $this->listenToLogs();
        Mail::fake();
        $this->fakeOk();
        $first = $this->makeRequest('first@example.test');
        $this->makeRequest(self::EMAIL);
        $this->makeRequest(self::EMAIL);

        $admin = $this->platformAdmin();
        $issued = SchoolInviteService::issue($first, $admin);
        $issued['invite']->forceFill(['early_access_request_id' => null, 'email' => self::EMAIL])->save();
        $this->workOps();
        $this->freshHttp();

        $this->assertTrue(SchoolInviteService::sendEmail($issued['invite']->fresh(), $issued['token']));
        $this->assertSame(0, $this->opsJobCount());
        $this->assertStringContainsString('does not match one request', implode("\n", $this->logLines));
        $this->assertLogsHaveNoPersonalData();
    }

    public function test_an_old_invite_with_one_request_for_the_email_uses_that_request(): void
    {
        Mail::fake();
        $this->fakeOk();
        $row = $this->makeRequest(self::EMAIL);
        $admin = $this->platformAdmin();
        $issued = SchoolInviteService::issue($row, $admin);
        $issued['invite']->forceFill(['early_access_request_id' => null])->save();
        $this->workOps();
        $this->freshHttp();

        $this->assertTrue(SchoolInviteService::sendEmail($issued['invite']->fresh(), $issued['token']));
        $this->assertSame(1, $this->opsJobCount());
        $this->workOps();
        $this->assertSentEvent('invited', $row);
    }

    public function test_the_request_link_migration_is_safe_to_run_twice(): void
    {
        $migration = require database_path('migrations/2026_10_25_100000_add_early_access_request_id_to_school_invites_table.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('school_invites', 'early_access_request_id'));
    }

    private function removeBrokenLookupScope(): void
    {
        (function () {
            unset(static::$globalScopes[EarlyAccessRequest::class]['broken-lookup']);
        })->call(new EarlyAccessRequest);
    }

    /**
     * @param  callable(): void  $callback
     * @return list<string>
     */
    private function captureSql(callable $callback): array
    {
        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });
        $callback();

        return $sql;
    }

    /**
     * @param  list<string>  $sql
     */
    private function assertNotContainsEarlyAccessLookup(array $sql): void
    {
        foreach ($sql as $statement) {
            $this->assertStringNotContainsString('early_access_requests', strtolower($statement));
        }
    }

    private function configureWebhook(): void
    {
        config([
            'services.ops_webhook.url' => self::URL,
            'services.ops_webhook.secret' => self::SECRET,
        ]);
    }

    private function fakeOk(): void
    {
        $this->freshHttp();
        Http::fake(['hooks.example.test/*' => Http::response('ok', 200)]);
    }

    private function freshHttp(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['hooks.example.test/*' => Http::response('ok', 200)]);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'school_name' => self::SCHOOL,
            'contact_name' => self::CONTACT,
            'contact_role' => 'Head teacher',
            'email' => self::EMAIL,
            'phone' => self::PHONE,
            'country' => 'Uganda',
            'approx_students' => '420',
            'consent' => '1',
        ], $overrides);
    }

    private function storeRequest(): EarlyAccessRequest
    {
        $this->post('/early-access', $this->payload())->assertRedirect();

        return EarlyAccessRequest::sole();
    }

    private function makeRequest(string $email): EarlyAccessRequest
    {
        return EarlyAccessRequest::create([
            'school_name' => self::SCHOOL,
            'contact_name' => self::CONTACT,
            'contact_role' => 'Head teacher',
            'email' => $email,
            'phone' => '+256701234567',
            'country' => 'Uganda',
            'approx_students' => 420,
            'consented_at' => now(),
        ]);
    }

    private function platformAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'platform-ops@klassapp.test'],
            [
                'name' => 'Platform Admin',
                'password' => bcrypt('not-a-public-password'),
                'usergroup_id' => 1,
                'status' => 'active',
            ]
        );
    }

    private function opsJobCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return DB::table('jobs')->get()->filter(
            fn ($row) => str_contains((string) $row->payload, 'SendEarlyAccessOpsWebhook')
        )->count();
    }

    private function workOps(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        DB::table('jobs')->get()->each(function ($row) {
            if (! str_contains((string) $row->payload, 'SendEarlyAccessOpsWebhook')) {
                DB::table('jobs')->where('id', $row->id)->delete();
            }
        });

        $this->artisan('queue:work', [
            '--stop-when-empty' => true,
            '--sleep' => 0,
        ])->assertExitCode(0);
    }

    private function assertReleasedFor(int $seconds): void
    {
        $this->assertSame(1, $this->opsJobCount());
        $available = (int) DB::table('jobs')
            ->where('payload', 'like', '%SendEarlyAccessOpsWebhook%')
            ->value('available_at');
        $this->assertSame($seconds, $available - now()->getTimestamp());
    }

    private function assertSentEvent(string $event, EarlyAccessRequest $row): void
    {
        $matched = null;
        foreach (Http::recorded() as [$request]) {
            $payload = json_decode($request->body(), true);
            if (($payload['event'] ?? null) === $event) {
                $matched = [$request, $payload];
            }
        }

        $this->assertNotNull($matched, 'No webhook was sent for '.$event);
        /** @var HttpRequest $request */
        [$request, $payload] = $matched;

        $raw = $request->body();
        $signature = $request->header('X-KlassApp-Signature')[0] ?? '';
        $expected = 'sha256='.hash_hmac('sha256', $raw, self::SECRET);
        $this->assertTrue(hash_equals($expected, $signature));
        $this->assertSame(
            Carbon::parse('2026-10-25 15:04:05', 'Africa/Kampala')->utc()->getTimestamp(),
            (int) ($request->header('X-KlassApp-Timestamp')[0] ?? 0)
        );
        $this->assertSame('application/json', $request->header('Content-Type')[0] ?? '');

        $this->assertSame([
            'event' => $event,
            'id' => $row->id,
            'school_name' => $row->school_name,
            'contact_name' => $row->contact_name,
            'email' => $row->email,
            'phone' => $row->phone,
            'country' => $row->country,
            'approximate_students' => (int) $row->approx_students,
            'status' => $event,
            'timestamp' => '2026-10-25T12:04:05Z',
        ], $payload);
        $this->assertSame(self::URL, (string) $request->url());
    }

    private function listenToLogs(): void
    {
        $this->logLines = [];
        Log::listen(function ($event) {
            $context = $event->context;
            $flat = [];
            foreach ((array) $context as $key => $value) {
                if (is_scalar($value) || $value === null) {
                    $flat[] = $key.'='.$value;
                } else {
                    $flat[] = $key.'='.(is_object($value) ? $value::class : gettype($value));
                }
            }
            $this->logLines[] = $event->level.' '.$event->message.' '.implode(' ', $flat);
        });
    }

    private function assertJobRowHasNoPersonalData(): void
    {
        $payload = DB::table('jobs')->where('payload', 'like', '%SendEarlyAccessOpsWebhook%')->value('payload');
        $this->assertIsString($payload);
        foreach ($this->personalMarkers() as $marker) {
            $this->assertStringNotContainsString($marker, $payload);
        }
    }

    private function assertLogsHaveNoPersonalData(): void
    {
        $text = implode("\n", $this->logLines);
        if (Schema::hasTable('failed_jobs')) {
            foreach (DB::table('failed_jobs')->get() as $row) {
                $text .= "\n".$row->payload."\n".$row->exception;
            }
        }
        if (Schema::hasTable('jobs')) {
            $text .= "\n".DB::table('jobs')->pluck('payload')->implode("\n");
        }

        foreach ($this->personalMarkers() as $marker) {
            $this->assertStringNotContainsString($marker, $text);
        }
    }

    /**
     * Markers plus the stored phone, which the form may rewrite.
     *
     * @return list<string>
     */
    private function personalMarkers(): array
    {
        $storedPhone = (string) EarlyAccessRequest::query()->value('phone');
        $markers = [
            self::EMAIL,
            self::SCHOOL,
            self::CONTACT,
            self::PHONE,
            self::SECRET,
            'n8n-query-token-not-for-logs',
            'hooks.example.test',
        ];
        if ($storedPhone !== '' && ! in_array($storedPhone, $markers, true)) {
            $markers[] = $storedPhone;
        }

        return $markers;
    }
}
