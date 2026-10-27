<?php

namespace Tests\Feature\Demo;

use App\Models\MarksRelease;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * Task 9 Tier 2: "Release marks". Admins release any class, class teachers
 * their own; on a demo school only numbers with an active BIND get a message,
 * at their own number; real schools and the kill switch send nothing; a
 * release never sends twice.
 */
class MarksReleaseTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    /** Amina's follower (fictional Uganda-format test number). */
    private const BOUND = '256700000201';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Pacific/Auckland', 'dashboard.v2_enabled' => true]);
        date_default_timezone_set('Pacific/Auckland');
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'Pacific/Auckland'));
        // Sends are queued since the queue change; these tests run the jobs
        // inline. MarksReleaseQueueTest covers the real queue.
        config(['queue.default' => 'sync']);
        config([
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.replies_enabled' => true,
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
        $this->approveFollowAlongSittings();
        DemoSchoolCommsGuard::flushCache();
        Http::preventStrayRequests();
        $this->fakeGraph();
        // The suite's cache store is a file store, so throttle counters
        // outlive a test: each test gets its own client address.
        $this->withServerVariables(['REMOTE_ADDR' => '10.' . random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(1, 254)]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    // ── Fixtures ─────────────────────────────────────────────────

    private function admin(string $which = 'demo'): User
    {
        $school = $this->fx[$which]['school'];
        $admin = User::create(['school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Admin ' . $which, 'email' => 'admin-' . $which . '@example.com', 'password' => bcrypt('x'), 'status' => 'active', 'email_verified' => 1]);
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'Admin', 'lastname' => ucfirst($which), 'status' => 'active']);

        return $admin;
    }

    private function teacher(string $which = 'demo', bool $classTeacher = true): User
    {
        $teacher = $this->fx[$which]['teacher'];
        Userprofile::firstOrCreate(['user_id' => $teacher->id], ['school_id' => $teacher->school_id, 'usergroup_id' => 5, 'firstname' => 'Teacher', 'lastname' => ucfirst($which), 'status' => 'active']);
        if ($classTeacher) {
            StandardLink::where('school_id', $teacher->school_id)->update(['class_teacher_id' => $teacher->id]);
        }

        return $teacher->fresh();
    }

    private function link(string $which = 'demo'): StandardLink
    {
        return StandardLink::where('school_id', $this->fx[$which]['school']->id)->firstOrFail();
    }

    private function examId(string $which = 'demo'): int
    {
        return (int) \App\Models\Academics\Exam::where('school_id', $this->fx[$which]['school']->id)
            ->whereHas('examType', fn ($q) => $q->where('code', 'EOT'))->min('id');
    }

    /** BIND by code over the real webhook, then forget the BIND reply. */
    private function bind(string $sender, int $studentIndex = 0): void
    {
        $this->inbound($sender, 'BIND ' . $this->fx['demo']['students'][$studentIndex]['code'])->assertOk();
        Http::fake();
        Http::preventStrayRequests();
        $this->fakeGraph();
    }

    private function releaseNow(User $user, ?int $linkId, string $area = 'admin', ?int $examId = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post('/' . $area . '/marks/releases', [
            'exam_id' => $examId ?? $this->examId(),
            'standard_link_id' => $linkId,
            'when' => 'now',
            'timezone' => 'Africa/Kampala',
        ]);
    }

    /** Phase 6b: let this school's class teachers release their own class. */
    private function allowTeachersToRelease(string $which = 'demo'): void
    {
        $school = $this->fx[$which]['school'];
        \App\Models\SchoolDetail::create([
            'school_id' => $school->id,
            'meta_key' => \App\Helpers\SiteHelper::TEACHERS_MAY_RELEASE_MARKS_KEY,
            'meta_value' => '1',
        ]);
        \App\Helpers\SiteHelper::forgetTeachersMayReleaseMarks((int) $school->id);
    }

    private function amina(): string
    {
        return "📣 *Sample Demo Primary* has released marks.\n\n"
            . "📊 *Marks for Amina Nakato* · P.7 A\n"
            . "End of Term Examination · Term III\n\n"
            . "```\n"
            . "Subject           Mark  Grade\n"
            . "Mathematics         78  C3\n"
            . "Science             64  C5\n"
            . "English             91  D2\n"
            . "Social Studies      55  C6\n"
            . "-----------------------------\n"
            . "Total              288\n"
            . "Average           72.0\n"
            . '```';
    }

    // ── The demo rule ────────────────────────────────────────────

    public function test_task9_release_sends_only_to_the_bound_number_with_the_exact_marks(): void
    {
        $this->bind(self::BOUND);

        $this->releaseNow($this->admin(), null)->assertRedirect();

        $sent = $this->outbound();
        $this->assertCount(1, $sent, 'Only the one active binding gets a message; the seeded parents of the demo school do not.');
        $this->assertSame(self::BOUND, $sent[0]['to']);
        $this->assertSame($this->amina(), $sent[0]['body']);

        $release = MarksRelease::sole();
        $this->assertSame('released', $release->status);
        $this->assertSame(1, $release->result['sent']);
        $this->assertSame(2, $release->result['skipped']);
        $this->assertSame(['No number is following this student' => 2], $release->result['reasons']);
    }

    public function test_task9_each_message_goes_only_to_the_number_bound_to_that_student(): void
    {
        $this->bind(self::BOUND, 0);
        $this->bind('256700000202', 2);

        $this->releaseNow($this->admin(), $this->link()->id)->assertRedirect();

        $sent = $this->outbound();
        $this->assertCount(2, $sent);
        $byTo = array_column($sent, 'body', 'to');
        $this->assertSame([self::BOUND, '256700000202'], array_map('strval', array_keys($byTo)));
        $this->assertStringContainsString('*Marks for Amina Nakato*', $byTo[self::BOUND]);
        $this->assertStringNotContainsString('Clare', $byTo[self::BOUND]);
        $this->assertStringContainsString('*Marks for Clare Auma*', $byTo['256700000202']);
        $this->assertStringNotContainsString('Amina', $byTo['256700000202']);
    }

    public function test_task9_expired_opted_out_or_parentless_bindings_get_nothing(): void
    {
        $this->bind(self::BOUND, 0);
        $this->bind('256700000202', 1);
        $this->bind('256700000203', 2);
        WhatsAppUser::where('phone', '+' . self::BOUND)->update(['demo_linked_at' => now()->subMinutes(44)->subSecond()]);
        WhatsAppUser::where('phone', '+256700000202')->update(['opted_in' => false]);
        WhatsAppUser::where('phone', '+256700000203')->update(['user_id' => null]);

        $this->releaseNow($this->admin(), null)->assertRedirect();

        $this->assertSame([], $this->outbound());
        $this->assertSame(['No number is following this student' => 3], MarksRelease::sole()->result['reasons']);
    }

    public function test_task9_a_binding_inside_the_ttl_still_gets_the_release(): void
    {
        $this->bind(self::BOUND, 0);
        WhatsAppUser::where('phone', '+' . self::BOUND)->update(['demo_linked_at' => now()->subMinutes(43)]);

        $this->releaseNow($this->admin(), null)->assertRedirect();

        $this->assertSame([self::BOUND], array_column($this->outbound(), 'to'));
    }

    public function test_task9_a_real_schools_release_sends_nothing(): void
    {
        // A real school's parent, opted in, with an open 24h window: still nothing,
        // because Phase 6 leaves the per-release switch off unless the admin sets it.
        WhatsAppUser::where('school_id', $this->fx['real']['school']->id)->update(['last_inbound_at' => now()]);

        $this->releaseNow($this->admin('real'), null, 'admin', $this->examId('real'))->assertRedirect();

        $this->assertSame([], $this->outbound());
        $release = MarksRelease::sole();
        $this->assertSame('released', $release->status);
        $this->assertFalse((bool) $release->whatsapp_send);
        $this->assertSame(0, $release->result['sent']);
        $this->assertSame(['WhatsApp was switched off for this release' => 1], $release->result['reasons']);
    }

    public function test_task9_kill_switch_sends_nothing(): void
    {
        $this->bind(self::BOUND);
        config(['services.whatsapp.replies_enabled' => false]);

        $this->releaseNow($this->admin(), null)->assertRedirect();

        $this->assertSame([], $this->outbound());
        $this->assertSame(['WhatsApp messages are switched off' => 3], MarksRelease::sole()->result['reasons']);
    }

    public function test_task9_guard_still_blocks_other_demo_numbers_after_a_release(): void
    {
        $this->bind(self::BOUND);
        $this->releaseNow($this->admin(), null)->assertRedirect();

        $other = WhatsAppUser::where('school_id', $this->fx['demo']['school']->id)->whereNull('demo_linked_at')->value('phone');
        $this->assertTrue(DemoSchoolCommsGuard::blocksWhatsApp((string) $other));
        $this->assertTrue(DemoSchoolCommsGuard::blocksWhatsApp('+' . self::BOUND));
        $this->assertFalse(DemoSchoolCommsGuard::duringBoundDemoRelease('+' . self::BOUND, fn () => DemoSchoolCommsGuard::blocksWhatsApp('+' . self::BOUND)));
        $this->assertTrue(DemoSchoolCommsGuard::duringBoundDemoRelease('+' . self::BOUND, fn () => DemoSchoolCommsGuard::blocksWhatsApp((string) $other)));
    }

    // ── Idempotency ──────────────────────────────────────────────

    public function test_task9_a_release_never_sends_twice(): void
    {
        $this->bind(self::BOUND);
        $this->releaseNow($this->admin(), null)->assertRedirect();
        $release = MarksRelease::sole();

        Artisan::call('marks:release-due');
        Artisan::call('marks:release-due');
        $this->assertFalse(app(\App\Services\MarksRelease\MarksReleaseService::class)->run($release->id));

        $this->assertCount(1, $this->outbound());
        $this->assertSame(1, MarksRelease::sole()->result['sent']);
    }

    public function test_task9_a_second_release_of_the_same_exam_skips_numbers_already_sent(): void
    {
        $this->bind(self::BOUND);
        $admin = $this->admin();
        $this->releaseNow($admin, null)->assertRedirect();
        $this->releaseNow($admin, $this->link()->id)->assertRedirect();

        $this->assertCount(1, $this->outbound());
        $second = MarksRelease::orderByDesc('id')->first();
        $this->assertSame(0, $second->result['sent']);
        $this->assertSame(1, $second->result['reasons']['Already sent to this number']);
    }

    // ── Scheduling and time zone ─────────────────────────────────

    public function test_task9_schedule_is_entered_in_the_users_time_zone_and_stored_in_utc(): void
    {
        $this->bind(self::BOUND);

        $this->actingAs($this->admin())->post('/admin/marks/releases', [
            'exam_id' => $this->examId(),
            'when' => 'schedule',
            // 15 Oct 2026 12:00 in Auckland is 15 Oct 02:00 in Kampala (UTC+3) and 23:00 UTC on the 14th.
            'scheduled_for' => '2026-10-15T02:30',
            'timezone' => 'Africa/Kampala',
        ])->assertRedirect();

        $release = MarksRelease::sole();
        $this->assertSame('2026-10-14 23:30:00', DB::table('marks_releases')->value('scheduled_at'));
        $this->assertSame('Africa/Kampala', $release->timezone);
        $this->assertSame('scheduled', $release->status);

        Carbon::setTestNow(Carbon::parse('2026-10-14 23:29:00', 'UTC'));
        Artisan::call('marks:release-due');
        $this->assertSame([], $this->outbound(), 'Not due yet.');

        Carbon::setTestNow(Carbon::parse('2026-10-14 23:30:00', 'UTC'));
        Artisan::call('marks:release-due');
        Artisan::call('marks:release-due');
        $this->assertSame([self::BOUND], array_column($this->outbound(), 'to'));
        $this->assertSame('released', MarksRelease::sole()->status);
        $this->assertSame('2026-10-14 23:30:00', DB::table('marks_releases')->value('released_at'));
    }

    public function test_task9_a_time_in_the_past_is_refused(): void
    {
        $this->actingAs($this->admin())->post('/admin/marks/releases', [
            'exam_id' => $this->examId(),
            'when' => 'schedule',
            'scheduled_for' => '2026-10-15T01:00',
            'timezone' => 'Africa/Kampala',
        ])->assertSessionHasErrors('scheduled_for');

        $this->assertSame(0, MarksRelease::count());
    }

    public function test_task9_marks_release_due_is_scheduled_every_minute(): void
    {
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'marks:release-due'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    // ── Permissions and school scoping ───────────────────────────

    public function test_task9_class_teacher_releases_their_own_class(): void
    {
        $this->bind(self::BOUND);
        $teacher = $this->teacher();
        // Phase 6b: a class teacher releases only once the school allows it.
        $this->allowTeachersToRelease('demo');

        $this->actingAs($teacher)->get('/teacher/marks/releases/new')->assertOk()->assertSee('Release marks');
        $this->releaseNow($teacher, $this->link()->id, 'teacher')->assertRedirect();

        $this->assertSame([self::BOUND], array_column($this->outbound(), 'to'));
        $this->assertSame('class', MarksRelease::sole()->scope);
    }

    public function test_task9_class_teacher_cannot_release_the_whole_school_or_another_class(): void
    {
        $teacher = $this->teacher();
        $this->releaseNow($teacher, null, 'teacher')->assertForbidden();

        // Phase 6: a class head releases every stream of their class.
        $streamB = StandardLink::create(['school_id' => $teacher->school_id, 'academic_year_id' => $this->link()->academic_year_id, 'standard_id' => $this->link()->standard_id, 'section_id' => $this->link()->section_id, 'stream' => 'B', 'status' => 1]);
        $this->releaseNow($teacher, $streamB->id, 'teacher')->assertRedirect();
        $this->assertSame(1, MarksRelease::count());

        // Another class (another section) is still not theirs.
        $section = \App\Models\Section::create(['school_id' => $teacher->school_id, 'name' => 'P.6', 'status' => 1]);
        $otherLink = StandardLink::create(['school_id' => $teacher->school_id, 'academic_year_id' => $this->link()->academic_year_id, 'standard_id' => $this->link()->standard_id, 'section_id' => $section->id, 'stream' => 'A', 'status' => 1]);
        $this->releaseNow($teacher, $otherLink->id, 'teacher')->assertForbidden();

        $this->assertSame(1, MarksRelease::count());
    }

    public function test_task9_teacher_who_is_not_a_class_teacher_cannot_release(): void
    {
        $teacher = $this->teacher('demo', false);

        $this->actingAs($teacher)->get('/teacher/marks/releases/new')->assertForbidden();
        $this->releaseNow($teacher, $this->link()->id, 'teacher')->assertForbidden();
        $this->assertSame(0, MarksRelease::count());
    }

    public function test_task9_parents_never_release(): void
    {
        $parent = $this->fx['demo']['students'][0]['parent'];

        $this->actingAs($parent)->post('/admin/marks/releases', ['exam_id' => $this->examId(), 'when' => 'now']);
        $this->actingAs($parent)->post('/teacher/marks/releases', ['exam_id' => $this->examId(), 'when' => 'now']);

        $this->assertSame(0, MarksRelease::count());
        $this->assertSame([], $this->outbound());
    }

    public function test_task9_admin_cannot_release_another_schools_exam_or_see_its_releases(): void
    {
        $realAdmin = $this->admin('real');
        $this->releaseNow($realAdmin, null, 'admin', $this->examId('real'))->assertRedirect();
        $realRelease = MarksRelease::sole();

        $demoAdmin = $this->admin('demo');
        $this->releaseNow($demoAdmin, null, 'admin', $this->examId('real'))->assertNotFound();
        $this->releaseNow($demoAdmin, $this->link('real')->id, 'admin')->assertForbidden();
        $this->actingAs($demoAdmin)->get('/admin/marks/releases/' . $realRelease->id)->assertNotFound();
        $this->actingAs($demoAdmin)->get('/admin/marks/releases')->assertOk()->assertDontSee('/admin/marks/releases/' . $realRelease->id);

        $this->assertSame(1, MarksRelease::count());
    }

    // ── Dialog, result view, audit log, buttons ──────────────────

    public function test_task9_preview_shows_the_message_and_saves_nothing(): void
    {
        $this->bind(self::BOUND);

        $response = $this->actingAs($this->admin())->post('/admin/marks/releases/preview', [
            'exam_id' => $this->examId(),
            'when' => 'schedule',
            'scheduled_for' => '2026-10-16T21:00',
            'timezone' => 'Africa/Kampala',
        ]);

        $response->assertOk()
            ->assertSee('Send these marks?')
            ->assertSee('Fri 16 Oct 2026, 21:00 (Africa/Kampala)')
            ->assertSee('Marks for Amina Nakato')
            ->assertSee('What parents receive')
            ->assertSee('Amina Nakato')
            ->assertSee('Mathematics')
            ->assertDontSee('```', false)
            ->assertSee('data-testid="release-marks-confirm"', false)
            ->assertSee('Choose the exam and who receives it. The sheet shows the marks that will be sent.')
            ->assertDontSee('data-testid="sheet-filter-class"', false);
        $this->assertSame(0, MarksRelease::count());
        $this->assertSame([], $this->outbound());
    }

    public function test_release_page_one_class_and_stream_sets_the_button(): void
    {
        $admin = $this->admin();
        $link = $this->link()->load('section');
        $class = trim((string) ($link->section?->name ?? ''));
        $stream = trim((string) $link->stream);

        $this->actingAs($admin)->get('/admin/marks/releases/new')
            ->assertOk()
            ->assertSee('Release to all classes (', false)
            ->assertSee('data-testid="release-class"', false)
            ->assertSee('data-testid="release-stream"', false)
            ->assertDontSee('data-testid="sheet-filter-class"', false);

        $this->actingAs($admin)->get('/admin/marks/releases/new?class='.urlencode($class).'&stream='.urlencode($stream))
            ->assertOk()
            ->assertSee('Release to '.trim($class.' '.$stream).' (', false);
    }

    public function test_release_controls_keep_the_exam_select_on_one_compact_row(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin)->get('/admin/marks/releases/new')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-testid="marks-release-controls"', $html);
        $this->assertStringContainsString('.mr-bar { display: flex; flex-wrap: nowrap;', $html);
        $this->assertStringContainsString('.mr-control:has(select[name="exam_id"]) { flex: 2 1 12rem; min-width: 10rem; max-width: none; }', $html);
        $this->assertStringContainsString('.mr-when-at { flex: 0 1 12.5rem; min-width: 11.5rem; max-width: 14rem; }', $html);
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 1100px\) \{ \.mr-bar \{ flex-wrap: wrap; \} \}/',
            $html,
            'Only the compact breakpoint should wrap the release bar',
        );
    }

    public function test_task9_result_view_lists_sent_and_skipped_with_reasons_and_masked_numbers(): void
    {
        $this->bind(self::BOUND);
        $admin = $this->admin();
        $this->releaseNow($admin, null)->assertRedirect('/admin/marks/releases/' . 1);

        $response = $this->actingAs($admin)->get('/admin/marks/releases/1');
        $response->assertOk()
            ->assertSee('data-testid="release-sent">1<', false)
            ->assertSee('data-testid="release-skipped">2<', false)
            ->assertSee('No number is following this student: 2')
            ->assertSee('•••201')
            ->assertSee('data-testid="release-student-name"', false)
            ->assertSee('Amina Nakato')
            ->assertSee('data-testid="release-student-class"', false)
            ->assertSee('P.7 A')
            ->assertDontSee('256700000201');
    }

    public function test_task9_release_is_audited(): void
    {
        $this->bind(self::BOUND);
        $admin = $this->admin();
        $this->releaseNow($admin, null)->assertRedirect();

        $logs = DB::table('activity_log')->where('log_name', 'marks_release')->orderBy('id')->get();
        $this->assertSame(['Marks release created (now)', 'Marks released'], $logs->pluck('description')->all());
        $this->assertSame((int) $admin->id, (int) $logs[0]->causer_id);
        $this->assertStringNotContainsString('256700000201', $logs->pluck('properties')->implode(' '));
    }

    public function test_task9_release_marks_button_is_on_the_dashboard_and_marks_pages(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('data-testid="release-marks-button"', false);
        $this->actingAs($admin)->get('/admin/marks/classes')->assertOk()->assertSee('data-testid="release-marks-button"', false);

        $teacher = $this->teacher();
        // Phase 6b: the button follows the same setting as the release itself.
        $this->allowTeachersToRelease('demo');
        $this->actingAs($teacher)->get('/teacher/exam/marks')->assertOk()->assertSee('data-testid="release-marks-button"', false);

        $notClassTeacher = $this->teacher('real', false);
        $this->actingAs($notClassTeacher)->get('/teacher/exam/marks')->assertOk()->assertDontSee('data-testid="release-marks-button"', false);
    }

    // ── Tier 3: the signed webhook ───────────────────────────────

    private function webhookCalls(): array
    {
        return collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'hooks.example.com'))->values()->all();
    }

    private function configureWebhook(): void
    {
        config([
            'klassapp.demo.release_webhook_url' => 'https://hooks.example.com/n8n/release',
            'klassapp.demo.release_webhook_secret' => 'test-only-webhook-secret',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
            'hooks.example.com/*' => Http::response(['ok' => true], 200),
        ]);
        Http::preventStrayRequests();
    }

    public function test_task9_demo_release_posts_a_signed_webhook_with_only_code_class_and_exam(): void
    {
        $this->bind(self::BOUND);
        $this->configureWebhook();

        $this->releaseNow($this->admin(), null)->assertRedirect();

        $calls = $this->webhookCalls();
        $this->assertCount(1, $calls);
        $request = $calls[0][0];
        $body = $request->body();
        $this->assertSame('sha256=' . hash_hmac('sha256', $body, 'test-only-webhook-secret'), $request->header('X-KlassApp-Signature')[0]);
        $students = $this->fx['demo']['students'];
        $this->assertSame([
            'event' => 'marks.released',
            'release_id' => MarksRelease::sole()->id,
            'exam' => 'End of Term Examination · Term III',
            'students' => array_map(fn ($s) => ['code' => $s['code'], 'class' => 'P.7 A'], $students),
        ], json_decode($body, true));
        foreach (['Amina', 'Nakato', '256700', 'Mathematics', '78'] as $private) {
            $this->assertStringNotContainsString($private, $body);
        }
        $this->assertSame('sent', MarksRelease::sole()->result['webhook']);
    }

    public function test_task9_webhook_is_not_posted_for_a_real_school_or_with_the_kill_switch(): void
    {
        $this->configureWebhook();
        $this->releaseNow($this->admin('real'), null, 'admin', $this->examId('real'))->assertRedirect();

        config(['services.whatsapp.replies_enabled' => false]);
        $this->releaseNow($this->admin('demo'), null)->assertRedirect();

        $this->assertSame([], $this->webhookCalls());
        $this->assertSame(['not sent', 'not sent'], MarksRelease::orderBy('id')->get()->map(fn ($r) => $r->result['webhook'])->all());
    }

    public function test_task9_webhook_needs_both_url_and_secret(): void
    {
        $this->bind(self::BOUND);
        $this->configureWebhook();
        config(['klassapp.demo.release_webhook_secret' => '']);

        $this->releaseNow($this->admin(), null)->assertRedirect();

        $this->assertSame([], $this->webhookCalls());
        $this->assertSame('not configured', MarksRelease::sole()->result['webhook']);
    }
}
