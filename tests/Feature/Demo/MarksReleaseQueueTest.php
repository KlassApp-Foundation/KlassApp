<?php

namespace Tests\Feature\Demo;

use App\Jobs\SendMarksReleaseMessage;
use App\Models\MarksRelease;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * "Release now" queues one job per number and returns at once; the jobs keep
 * the demo-only send guard, never send twice, retry Meta failures and show a
 * failure on the release page without holding up other parents.
 */
class MarksReleaseQueueTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    private const FAILING = '256700000299';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Pacific/Auckland', 'dashboard.v2_enabled' => true]);
        date_default_timezone_set('Pacific/Auckland');
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'Pacific/Auckland'));
        config([
            'queue.default' => 'database',
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => true,
            'services.whatsapp.privacy_notice_url' => 'https://example.com/privacy',
            'services.whatsapp.demo_parent_user_id' => 0,
            'klassapp.demo.whatsapp_number' => '+1 555 010 0000',
            'klassapp.demo.bind_ttl_minutes' => 45,
            'app.url' => 'https://demo.example.com',
        ]);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DemoSchoolCommsGuard::flushCache();
        $this->relaxDeliveryLog();
        $this->buildSchools();
        DemoSchoolCommsGuard::flushCache();
        Http::preventStrayRequests();
        $this->fakeGraph();
        $this->withServerVariables(['REMOTE_ADDR' => '10.' . random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(1, 254)]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    private function admin(): User
    {
        $school = $this->fx['demo']['school'];
        $admin = User::create(['school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Admin demo', 'email' => 'admin-demo@example.com', 'password' => bcrypt('x'), 'status' => 'active', 'email_verified' => 1]);
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'Admin', 'lastname' => 'Demo', 'status' => 'active']);

        return $admin;
    }

    private function examId(): int
    {
        return (int) \App\Models\Academics\Exam::where('school_id', $this->fx['demo']['school']->id)
            ->whereHas('examType', fn ($q) => $q->where('code', 'EOT'))->min('id');
    }

    /** One real BIND over the webhook, then $count numbers following students (cycled), like a room of 50 phones. */
    private function bindNumbers(int $count): array
    {
        $first = '256700100001';
        $this->inbound($first, 'BIND ' . $this->fx['demo']['students'][0]['code'])->assertOk();
        $template = WhatsAppUser::where('phone', '+' . $first)->firstOrFail();
        $students = WhatsAppUser::query()->whereKey($template->id)->value('demo_student_id');
        $ids = collect($this->fx['demo']['students'])->pluck('id')->filter()->values()->all() ?: [$students];
        $numbers = [$first];
        for ($i = 2; $i <= $count; $i++) {
            $phone = '2567001' . str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $row = $template->replicate();
            $row->phone = '+' . $phone;
            $row->demo_student_id = $ids[($i - 1) % count($ids)];
            $row->save();
            $numbers[] = $phone;
        }
        $this->freshHttp();

        return $numbers;
    }

    /** Forget earlier fakes and recorded requests (Http::fake stubs stack, first match wins). */
    private function freshHttp(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
    }

    private function releaseNow(User $admin): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($admin)->post('/admin/marks/releases', [
            'exam_id' => $this->examId(), 'standard_link_id' => null, 'when' => 'now', 'timezone' => 'Africa/Kampala',
        ]);
    }

    private function work(): void
    {
        $this->artisan('queue:work', ['--stop-when-empty' => true, '--sleep' => 0, '--memory' => 4096])->assertExitCode(0);
    }

    /** Graph fake: FAILING gets a 503 for the first $failures sends, everyone else succeeds. */
    private function graphWithFailures(int $failures): void
    {
        $left = $failures;
        $this->freshHttp();
        Http::fake(['graph.facebook.com/*' => function (HttpRequest $request) use (&$left) {
            if (($request->data()['to'] ?? '') === self::FAILING && $left > 0) {
                $left--;

                return Http::response(['error' => ['message' => 'Service temporarily unavailable']], 503);
            }

            return Http::response(['messages' => [['id' => 'wamid.out']]], 200);
        }]);
        Http::preventStrayRequests();
    }

    private function entryFor(string $phone): array
    {
        $masked = '•••' . substr($phone, -3);

        return collect(MarksRelease::sole()->result['messages'])->firstWhere('to', $masked);
    }

    public function test_release_now_with_50_bound_parents_queues_50_jobs_and_returns_at_once(): void
    {
        $numbers = $this->bindNumbers(50);
        $this->freshHttp();
        $this->fakeGraph();
        $admin = $this->admin();

        $started = microtime(true);
        $response = $this->releaseNow($admin);
        $elapsed = microtime(true) - $started;

        $release = MarksRelease::sole();
        $response->assertRedirect('/admin/marks/releases/' . $release->id)
            ->assertSessionHas('status', 'Queued 50 messages. Refresh this page to see each one sent.');
        $this->assertLessThan(5.0, $elapsed);
        $this->assertSame([], $this->outbound(), 'Nothing is sent inside the request.');
        $this->assertSame(50, DB::table('jobs')->count());
        $this->assertSame(50, $release->result['queued']);
        $this->assertSame(0, $release->result['sent']);

        $this->actingAs($admin)->get('/admin/marks/releases/' . $release->id)
            ->assertSee('data-testid="release-queued">50<', false)
            ->assertSee('50 still sending. Refresh this page to update.')
            ->assertSee('Sending…');

        $this->work();

        $sent = $this->outbound();
        $this->assertCount(50, $sent);
        $this->assertEqualsCanonicalizing($numbers, array_column($sent, 'to'));
        $release->refresh();
        $this->assertSame(50, $release->result['sent']);
        $this->assertSame(0, $release->result['queued']);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertStringContainsString('has released marks.', $sent[0]['body']);

        // Running the same job again (a retry or duplicate) sends nothing more.
        $entry = collect($release->result['messages'])->firstWhere('status', 'sent');
        $binding = WhatsAppUser::where('phone', '+' . $numbers[0])->first();
        dispatch(new SendMarksReleaseMessage($release->id, $binding->id, (int) $binding->demo_student_id, (int) \App\Models\StandardLink::where('school_id', $release->school_id)->value('id'), collect($release->result['messages'])->first(fn ($m) => $m['to'] === '•••' . substr($numbers[0], -3))['key']));
        $this->work();
        $this->assertCount(50, $this->outbound());
        $this->assertNotNull($entry);
    }

    public function test_a_failing_send_is_retried_then_shown_as_failed_and_other_parents_are_unaffected(): void
    {
        $numbers = $this->bindNumbers(3);
        WhatsAppUser::where('phone', '+' . $numbers[2])->update(['phone' => '+' . self::FAILING]);
        $this->graphWithFailures(99);
        $admin = $this->admin();

        $this->releaseNow($admin)->assertSessionHas('status', 'Queued 3 messages. Refresh this page to see each one sent.');

        // Try 1: the two others are sent; the failing one waits to retry.
        $this->work();
        $this->assertSame('sent', $this->entryFor($numbers[0])['status']);
        $this->assertSame('sent', $this->entryFor($numbers[1])['status']);
        $failing = $this->entryFor(self::FAILING);
        $this->assertSame('retrying', $failing['status']);
        $this->assertSame('Try 1 of 3 failed: Service temporarily unavailable', $failing['reason']);
        $this->assertSame(1, DB::table('jobs')->count(), 'the retry waits for its backoff');

        // Try 2 after 30 s, try 3 after 2 more minutes.
        Carbon::setTestNow(now()->addSeconds(31));
        $this->work();
        $this->assertSame('Try 2 of 3 failed: Service temporarily unavailable', $this->entryFor(self::FAILING)['reason']);
        Carbon::setTestNow(now()->addSeconds(121));
        $this->work();

        $release = MarksRelease::sole();
        $failing = $this->entryFor(self::FAILING);
        $this->assertSame('failed', $failing['status']);
        $this->assertSame('Not delivered: Service temporarily unavailable', $failing['reason']);
        $this->assertSame(['sent' => 2, 'queued' => 0, 'failed' => 1, 'skipped' => 2], array_intersect_key($release->result, array_flip(['sent', 'queued', 'failed', 'skipped'])));
        $this->assertEqualsCanonicalizing(['No number is following this student' => 2, 'Not delivered: Service temporarily unavailable' => 1], $release->result['reasons']);
        $this->assertSame(1, DB::table('failed_jobs')->count());
        $this->assertSame(0, DB::table('jobs')->count());

        $to = array_column($this->outbound(), 'to');
        $this->assertSame(1, count(array_keys($to, $numbers[0])), 'other parents get exactly one message');
        $this->assertSame(1, count(array_keys($to, $numbers[1])));
        $this->assertSame(3, count(array_keys($to, self::FAILING)), 'three tries');

        $this->actingAs($admin)->get('/admin/marks/releases/' . $release->id)
            ->assertSee('data-testid="release-failed">1<', false)
            ->assertSee('Failed: Not delivered: Service temporarily unavailable')
            ->assertSee('To try the failed numbers again, release this exam again');

        // Releasing again re-sends only to the failed number.
        $this->graphWithFailures(0);
        $this->releaseNow($admin)->assertSessionHas('status', 'Queued 1 message. Refresh this page to see each one sent.');
        $this->work();
        $this->assertSame([self::FAILING], array_column($this->outbound(), 'to'));
    }

    public function test_a_send_that_fails_once_is_sent_exactly_once_on_retry(): void
    {
        $numbers = $this->bindNumbers(2);
        WhatsAppUser::where('phone', '+' . $numbers[1])->update(['phone' => '+' . self::FAILING]);
        $this->graphWithFailures(1);

        $this->releaseNow($this->admin());
        $this->work();
        Carbon::setTestNow(now()->addSeconds(31));
        $this->work();

        $to = array_column($this->outbound(), 'to');
        $this->assertSame([$numbers[0], self::FAILING, self::FAILING], $to);
        $this->assertSame('sent', $this->entryFor(self::FAILING)['status']);
        $this->assertSame(2, $this->entryFor(self::FAILING)['attempts']);
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_jobs_keep_the_demo_only_guard_and_the_kill_switch(): void
    {
        $numbers = $this->bindNumbers(2);
        $this->fakeGraph();
        $this->releaseNow($this->admin());

        // While queued: one number stops following, then the kill switch goes off for the rest.
        WhatsAppUser::where('phone', '+' . $numbers[0])->update(['demo_student_id' => null, 'demo_linked_at' => null]);
        $this->work();
        $this->assertSame([$numbers[1]], array_column($this->outbound(), 'to'));
        $this->assertSame('The number stopped following this student before the message went out', $this->entryFor($numbers[0])['reason']);

        $this->freshHttp();
        $this->fakeGraph();
        config(['services.whatsapp.replies_enabled' => false]);
        $this->releaseNow($this->admin2());
        $this->assertSame([], $this->outbound());
    }

    private function admin2(): User
    {
        $school = $this->fx['demo']['school'];
        $admin = User::create(['school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Admin two', 'email' => 'admin-two@example.com', 'password' => bcrypt('x'), 'status' => 'active', 'email_verified' => 1]);
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'Admin', 'lastname' => 'Two', 'status' => 'active']);

        return $admin;
    }
}
