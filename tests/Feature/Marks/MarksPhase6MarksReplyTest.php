<?php

namespace Tests\Feature\Marks;

use App\Models\MarksRelease;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * Phase 6e: a parent whose children have no released results gets ONE reply with
 * one line per child, not one message per child plus an extra line saying no
 * child has results.
 */
class MarksPhase6MarksReplyTest extends TestCase
{
    use RefreshDatabase;
    use ReleaseRulesFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'queue.default' => 'sync',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            // The reply path is under test here, not webhook signatures.
            'services.whatsapp.verify_signature' => false,
            'whatsapp.approved_templates' => [],
        ]);
        DemoSchoolCommsGuard::flushCache();
        Cache::flush();
        $this->relaxDeliveryLog();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->buildSchools();
        $this->approveFollowAlongSittings();
        DemoSchoolCommsGuard::flushCache();
        $this->fakeGraph();
    }

    private function parentMobile(string $which = 'real'): string
    {
        $parent = $this->fx[$which]['students'][0]['parent'];

        return (string) WhatsAppUser::query()
            ->where('school_id', $this->fx[$which]['school']->id)
            ->where('user_id', $parent->id)
            ->value('phone');
    }

    public function test_a_parent_whose_children_have_no_released_results_gets_one_reply_with_a_line_per_child(): void
    {
        $parent = $this->fx['real']['students'][0]['parent'];
        $this->addChild('real', $parent, 'Second Realpupil', false);

        $this->inbound(ltrim($this->parentMobile(), '+'), 'MARKS')->assertOk();

        $sent = $this->outbound();
        $replies = array_values(array_filter($sent, fn (array $m) => str_contains($m['body'], '•')));
        $this->assertCount(1, $replies, 'one reply for the parent, not one per child plus a summary');
        $body = $replies[0]['body'];

        $this->assertStringContainsString('Zed Realpupil', $body);
        $this->assertStringContainsString('Second Realpupil', $body);
        $this->assertSame(2, substr_count($body, '•'), 'one line per child');
        $this->assertStringNotContainsString('No results found for any of your children', $body);
        $this->assertSame(0, MarksRelease::count(), 'nothing is released in this test');
    }

    public function test_one_child_without_results_still_gets_one_line(): void
    {
        $this->inbound(ltrim($this->parentMobile(), '+'), 'MARKS')->assertOk();

        $sent = $this->outbound();
        $replies = array_values(array_filter($sent, fn (array $m) => str_contains($m['body'], '•')));
        $this->assertCount(1, $replies);
        $this->assertStringContainsString('Zed Realpupil', $replies[0]['body']);
        $this->assertSame(1, substr_count($replies[0]['body'], '•'));
    }
}
