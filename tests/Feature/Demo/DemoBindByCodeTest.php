<?php

namespace Tests\Feature\Demo;

use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * Task 9 Tier 1: "BIND <code>" on WhatsApp links the sender to one demo
 * student, MARKS / RESULTS answer with that student's latest exam, and the
 * binding is removed by demo:expire-chats. Every message goes through the
 * signed webhook; outbound calls are recorded by the HTTP fake.
 */
class DemoBindByCodeTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    private const SENDER = '256700000104';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Pacific/Auckland']);
        date_default_timezone_set('Pacific/Auckland');
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'Pacific/Auckland'));
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
        DemoSchoolCommsGuard::flushCache();
        $this->relaxDeliveryLog();
        $this->buildSchools();
        DemoSchoolCommsGuard::flushCache();
        $this->fakeGraph();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    private function amina(): array
    {
        return $this->fx['demo']['students'][0];
    }

    public function test_task9_bind_links_the_sender_to_the_demo_student(): void
    {
        $amina = $this->amina();

        $this->inbound(self::SENDER, 'BIND ' . $amina['code'])->assertOk();

        $row = WhatsAppUser::where('phone', '+' . self::SENDER)->firstOrFail();
        $this->assertSame((int) $amina['user']->id, (int) $row->demo_student_id);
        $this->assertSame((int) $amina['parent']->id, (int) $row->user_id);
        $this->assertSame((int) $this->fx['demo']['school']->id, (int) $row->school_id);
        $this->assertTrue($row->opted_in);
        $this->assertTrue($row->demo_linked_at->equalTo(now()));

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::SENDER, $sent[0]['to']);
        $this->assertSame(
            "✅ You are now following *Amina Nakato* in *P.7 A* at Sample Demo Primary.\n\n"
            . "What to expect: when the school releases this student's marks during the demo, they arrive in this chat.\n\n"
            . "Reply MARKS to see marks now.\n\n"
            . "Your number is removed after 45 minutes.\n"
            . 'Privacy: https://example.com/privacy',
            $sent[0]['body'],
        );
    }

    public function test_task9_bind_is_case_insensitive_with_extra_spaces(): void
    {
        $amina = $this->amina();

        $this->inbound(self::SENDER, "   bInD    " . strtolower($amina['code']) . "   ")->assertOk();

        $this->assertSame((int) $amina['user']->id, (int) WhatsAppUser::where('phone', '+' . self::SENDER)->value('demo_student_id'));
        $this->assertStringContainsString('*Amina Nakato*', $this->outbound()[0]['body']);
    }

    public function test_task9_unknown_code_gets_the_follow_along_url(): void
    {
        $this->inbound(self::SENDER, 'BIND ZZZZ')->assertOk();

        $this->assertNull(WhatsAppUser::where('phone', '+' . self::SENDER)->value('demo_student_id'));
        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::SENDER, $sent[0]['to']);
        $this->assertSame(
            "Sorry, we could not find a demo student with the code *ZZZZ*.\n\n"
            . 'Open https://demo.example.com/demo to see the demo class, then tap *Bind* next to a student.',
            $sent[0]['body'],
        );
    }

    public function test_task9_code_from_a_non_demo_school_is_refused(): void
    {
        $real = $this->fx['real']['students'][0];

        $this->inbound(self::SENDER, 'BIND ' . $real['code'])->assertOk();

        $this->assertNull(WhatsAppUser::where('phone', '+' . self::SENDER)->first());
        $this->assertSame(1, WhatsAppUser::where('user_id', $real['parent']->id)->count());
        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::SENDER, $sent[0]['to']);
        $this->assertStringStartsWith('Sorry, we could not find a demo student with the code *' . $real['code'] . '*.', $sent[0]['body']);
        $this->assertStringNotContainsString('Zed', $sent[0]['body']);
        $this->assertStringNotContainsString('Hillside', $sent[0]['body']);
    }

    public function test_task9_number_linked_to_a_real_school_is_not_rebound(): void
    {
        $realParentPhone = (string) WhatsAppUser::where('user_id', $this->fx['real']['students'][0]['parent']->id)->value('phone');
        $amina = $this->amina();
        // The same code binds a number that is not linked to a school.
        $this->inbound(self::SENDER, 'BIND ' . $amina['code'])->assertOk();
        $this->assertSame((int) $amina['user']->id, (int) WhatsAppUser::where('phone', '+' . self::SENDER)->value('demo_student_id'));

        $this->inbound(ltrim($realParentPhone, '+'), 'BIND ' . $amina['code'])->assertOk();

        $row = WhatsAppUser::where('phone', $realParentPhone)->firstOrFail();
        $this->assertNull($row->demo_student_id);
        $this->assertSame((int) $this->fx['real']['school']->id, (int) $row->school_id);
        foreach ($this->outbound() as $message) {
            if ($message['to'] !== self::SENDER) {
                $this->assertStringNotContainsString('You are now following', $message['body']);
            }
        }
    }

    public function test_task9_marks_from_an_unbound_number_gets_a_polite_reply(): void
    {
        $this->inbound(self::SENDER, 'MARKS')->assertOk();
        $this->inbound('256700000105', 'results')->assertOk();

        $sent = $this->outbound();
        $this->assertCount(2, $sent);
        $this->assertSame([self::SENDER, '256700000105'], array_column($sent, 'to'));
        foreach ($sent as $message) {
            $this->assertSame(
                "This number is not following a demo student right now.\n\n"
                . 'Open https://demo.example.com/demo, tap *Bind* next to a student, then reply MARKS.',
                $message['body'],
            );
        }
    }

    public function test_task9_marks_before_release_says_not_released_yet(): void
    {
        $this->inbound(self::SENDER, 'BIND ' . $this->amina()['code'])->assertOk();
        // A scheduled (not yet sent) release does not count as released.
        $this->releaseLatestExam('demo', 'scheduled');
        Http::fake();
        $this->fakeGraph();

        $this->inbound(self::SENDER, 'MARKS')->assertOk();
        $this->inbound(self::SENDER, 'Results')->assertOk();

        $expected = "📊 Results for *Amina Nakato* have not been released yet.\n\n"
            . 'The school will send them here as soon as they release them.';
        $sent = $this->outbound();
        $this->assertCount(2, $sent);
        foreach ($sent as $message) {
            $this->assertSame(self::SENDER, $message['to']);
            $this->assertSame($expected, $message['body']);
            $this->assertStringNotContainsString('Mathematics', $message['body']);
        }
    }

    public function test_task9_marks_after_a_class_release_is_the_exact_table(): void
    {
        $this->inbound(self::SENDER, 'BIND ' . $this->amina()['code'])->assertOk();
        $this->releaseLatestExam('demo', 'released', 'class');
        Http::fake();
        $this->fakeGraph();

        $this->inbound(self::SENDER, 'MARKS')->assertOk();

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertStringContainsString("Mathematics         78  C3", $sent[0]['body']);
    }

    public function test_task9_marks_for_a_bound_number_is_the_exact_table(): void
    {
        $this->inbound(self::SENDER, 'BIND ' . $this->amina()['code'])->assertOk();
        $this->releaseLatestExam('demo');
        Http::fake();
        $this->fakeGraph();

        $this->inbound(self::SENDER, 'MARKS')->assertOk();
        $this->inbound(self::SENDER, 'Results')->assertOk();

        $expected = "📊 *Marks for Amina Nakato* · P.7 A\n"
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
        $sent = $this->outbound();
        $this->assertCount(2, $sent);
        foreach ($sent as $message) {
            $this->assertSame(self::SENDER, $message['to']);
            $this->assertSame($expected, $message['body']);
        }
    }

    public function test_task9_bind_expires_through_demo_expire_chats(): void
    {
        $this->inbound(self::SENDER, 'BIND ' . $this->amina()['code'])->assertOk();
        $row = WhatsAppUser::where('phone', '+' . self::SENDER)->firstOrFail();

        // 43 minutes: still bound. The command runs every minute and removes a
        // binding one minute before the 45-minute limit, never after it.
        Carbon::setTestNow(now()->addMinutes(43));
        Artisan::call('demo:expire-chats');
        $this->assertSame((int) $this->amina()['user']->id, (int) $row->fresh()->demo_student_id);

        Carbon::setTestNow(now()->addMinute());
        Artisan::call('demo:expire-chats');
        $row->refresh();
        $this->assertNull($row->demo_student_id);
        $this->assertNull($row->user_id);
        $this->assertNull($row->school_id);
        $this->assertNull($row->demo_linked_at);
        $this->assertStringContainsString(
            "command('demo:expire-chats')->everyMinute()",
            file_get_contents(app_path('Console/Kernel.php')),
        );

        // The real school's parent row is never touched.
        $this->assertSame(1, WhatsAppUser::where('school_id', $this->fx['real']['school']->id)->count());

        Http::fake();
        $this->fakeGraph();
        $this->inbound(self::SENDER, 'MARKS')->assertOk();
        $this->assertStringStartsWith('This number is not following a demo student', $this->outbound()[0]['body']);
    }

    public function test_task9_replies_only_ever_go_to_the_inbound_sender(): void
    {
        $other = '256700000106';
        // Uganda-format senders, as Meta sends them (digits, no +).
        $steps = [
            [self::SENDER, 'JOIN', null],
            [self::SENDER, 'BIND ' . $this->amina()['code'], 'You are now following *Amina Nakato*'],
            [$other, 'BIND nope', 'could not find a demo student'],
            [self::SENDER, 'MARKS', 'have not been released yet'],
            [$other, 'MARKS', 'This number is not following a demo student'],
            [self::SENDER, 'REPORT', null],
        ];
        foreach ($steps as [$from, $text, $expect]) {
            $before = count($this->outbound());
            $this->inbound($from, $text)->assertOk();
            $new = array_slice($this->outbound(), $before);
            $this->assertNotEmpty($new, "No reply to {$text}");
            if ($expect !== null) {
                $this->assertStringContainsString($expect, $new[0]['body'], "Reply to \"{$text}\"");
            }
            foreach ($new as $message) {
                $this->assertSame($from, $message['to'], "Reply to \"{$text}\" went to another number");
            }
        }
    }

    public function test_task9_kill_switch_stops_every_reply(): void
    {
        // With replies on, BIND answers.
        $this->inbound('256700000105', 'BIND ' . $this->amina()['code'])->assertOk();
        $this->assertCount(1, $this->outbound());
        $this->assertStringContainsString('You are now following', $this->outbound()[0]['body']);

        config(['services.whatsapp.replies_enabled' => false]);

        foreach (['JOIN', 'BIND ' . $this->amina()['code'], 'MARKS', 'BIND ZZZZ', 'RESULTS'] as $text) {
            $this->inbound(self::SENDER, $text)->assertOk();
        }

        $this->assertCount(1, $this->outbound(), 'nothing more is sent once replies are off');
    }
}
