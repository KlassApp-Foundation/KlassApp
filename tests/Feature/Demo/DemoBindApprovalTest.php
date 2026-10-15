<?php

namespace Tests\Feature\Demo;

use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoBindApproval;
use App\Services\DemoSchoolCommsGuard;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use App\States\Approval\Rejected;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * Local café demo, step 3: with DEMO_BIND_REQUIRES_APPROVAL on, BIND <code>
 * records a request on the admin Approvals page; the admin approves it
 * (the number is bound, the TTL starts at the approval, the confirmation
 * goes to that number only) or rejects it (a polite reply, nothing bound);
 * demo:expire-chats closes a request nobody approved within the TTL.
 * Every message goes through the signed webhook and the HTTP fake.
 */
class DemoBindApprovalTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    private const SENDER = '256700000941';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Pacific/Auckland', 'dashboard.v2_enabled' => true]);
        date_default_timezone_set('Pacific/Auckland');
        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', 'Pacific/Auckland'));
        config([
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.privacy_notice_url' => 'https://example.com/privacy',
            'services.whatsapp.demo_parent_user_id' => 0,
            'klassapp.demo.whatsapp_number' => '+1 555 010 0000',
            'klassapp.demo.bind_ttl_minutes' => 45,
            'klassapp.demo.bind_requires_approval' => true,
            // As in the local kit: the demo-only send guard is on, so every reply
            // here must also get past it (a rejection reply once did not).
            'services.whatsapp.demo_only_recipients' => true,
            'app.url' => 'https://demo.example.com',
        ]);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
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

    private function admin(string $which = 'demo'): User
    {
        $school = $this->fx[$which]['school'];
        $admin = User::create(['school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Admin ' . $which, 'email' => 'admin-' . $which . '@example.com', 'password' => bcrypt('x'), 'status' => 'active', 'email_verified' => 1]);
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'Admin', 'lastname' => ucfirst($which), 'status' => 'active']);

        return $admin;
    }

    private function amina(): array
    {
        return $this->fx['demo']['students'][0];
    }

    private function resetHttp(): void
    {
        Http::fake();
        Http::preventStrayRequests();
        $this->fakeGraph();
    }

    private function requestBind(): Approval
    {
        $this->inbound(self::SENDER, 'BIND ' . $this->amina()['code'])->assertOk();

        return Approval::query()->where('approvable_type', ParentLinkRequest::class)->latest('id')->firstOrFail();
    }

    private function approve(Approval $approval, ?User $admin = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($admin ?? $this->admin())
            ->post('/admin/approvals/' . $approval->id . '/approve', ['matched_student_id' => $this->amina()['user']->id, 'comments' => '']);
    }

    private function binding(): ?WhatsAppUser
    {
        return WhatsAppUser::where('phone', '+' . self::SENDER)->first();
    }

    public function test_demo_local_bind_creates_a_pending_request_and_links_nothing(): void
    {
        $approval = $this->requestBind();

        $request = ParentLinkRequest::findOrFail($approval->approvable_id);
        $this->assertSame('pending', $request->status);
        $this->assertSame(DemoBindApproval::REQUEST_TOKEN, $request->flow_token);
        $this->assertSame((int) $this->fx['demo']['school']->id, (int) $request->school_id);
        $this->assertSame([(int) $this->amina()['user']->id], $request->candidate_student_ids);
        $this->assertInstanceOf(Pending::class, $approval->state);

        $row = $this->binding();
        $this->assertTrue($row === null || ($row->demo_student_id === null && $row->user_id === null), 'BIND must not link before approval');

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::SENDER, $sent[0]['to']);
        $this->assertStringContainsString('Request sent', $sent[0]['body']);
        $this->assertStringContainsString('Waiting for the school to approve', $sent[0]['body']);
    }

    public function test_demo_local_pending_request_shows_on_the_admin_approvals_page(): void
    {
        $this->requestBind();

        $this->actingAs($this->admin())->get('/admin/approvals')
            ->assertOk()
            ->assertSee('+' . self::SENDER)
            ->assertSee('Amina Nakato');

        // Another school's admin does not see it.
        $this->actingAs($this->admin('real'))->get('/admin/approvals')
            ->assertOk()
            ->assertDontSee('+' . self::SENDER);
    }

    public function test_demo_local_approval_binds_the_number_and_confirms_to_that_number_only(): void
    {
        $approval = $this->requestBind();
        $this->resetHttp();
        Carbon::setTestNow(now()->addMinutes(10));

        $this->approve($approval)->assertRedirect();

        $row = $this->binding();
        $this->assertNotNull($row);
        $this->assertSame((int) $this->amina()['user']->id, (int) $row->demo_student_id);
        $this->assertSame((int) $this->amina()['parent']->id, (int) $row->user_id);
        $this->assertTrue($row->demo_linked_at->equalTo(now()), 'the TTL counts from the approval');
        $this->assertSame('approved', ParentLinkRequest::findOrFail($approval->approvable_id)->status);
        $this->assertInstanceOf(Approved::class, $approval->fresh()->state);

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::SENDER, $sent[0]['to']);
        $this->assertStringContainsString('You are now following *Amina Nakato*', $sent[0]['body']);
        $this->assertStringContainsString('removed after 45 minutes', $sent[0]['body']);
        // No parent account is created for the demo number.
        $this->assertSame(0, User::where('mobile_no', '+' . self::SENDER)->count());
    }

    public function test_demo_local_ttl_counts_from_the_approval_not_the_request(): void
    {
        $approval = $this->requestBind();
        Carbon::setTestNow(now()->addMinutes(30));
        $this->approve($approval)->assertRedirect();
        $approvedAt = now()->copy();

        // 50 minutes after BIND, 20 after approval: still bound.
        Carbon::setTestNow($approvedAt->copy()->addMinutes(20));
        Artisan::call('demo:expire-chats');
        $this->assertNotNull($this->binding()->demo_student_id);

        Carbon::setTestNow($approvedAt->copy()->addMinutes(43));
        Artisan::call('demo:expire-chats');
        $this->assertNotNull($this->binding()->demo_student_id);

        Carbon::setTestNow($approvedAt->copy()->addMinutes(44));
        Artisan::call('demo:expire-chats');
        $this->assertNull($this->binding()->demo_student_id);
        $this->assertNull($this->binding()->user_id);
    }

    public function test_demo_local_rejection_sends_a_polite_reply_and_links_nothing(): void
    {
        $approval = $this->requestBind();
        $this->resetHttp();

        $this->actingAs($this->admin())
            ->post('/admin/approvals/' . $approval->id . '/reject', ['comments' => 'Not today'])
            ->assertRedirect();

        $this->assertSame('rejected', ParentLinkRequest::findOrFail($approval->approvable_id)->status);
        $this->assertInstanceOf(Rejected::class, $approval->fresh()->state);
        $row = $this->binding();
        $this->assertTrue($row === null || $row->demo_student_id === null);

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame(self::SENDER, $sent[0]['to']);
        $this->assertStringContainsString('Thank you for trying the KlassApp demo', $sent[0]['body']);
        $this->assertStringContainsString('https://demo.example.com/demo', $sent[0]['body']);
    }

    public function test_demo_local_a_pending_request_expires_after_the_ttl(): void
    {
        $approval = $this->requestBind();
        $this->resetHttp();

        Carbon::setTestNow(now()->addMinutes(43));
        Artisan::call('demo:expire-chats');
        $this->assertInstanceOf(Pending::class, $approval->fresh()->state);

        Carbon::setTestNow(now()->addMinute());
        Artisan::call('demo:expire-chats');
        $this->assertStringContainsString('expired pending bind requests=1', Artisan::output());
        $this->assertInstanceOf(Rejected::class, $approval->fresh()->state);
        $this->assertStringStartsWith('Expired', (string) $approval->fresh()->comments);
        $this->assertSame('rejected', ParentLinkRequest::findOrFail($approval->approvable_id)->status);
        $this->assertSame([], $this->outbound(), 'expiry sends nothing');

        // An expired request can no longer be approved.
        $this->approve($approval)->assertRedirect();
        $this->assertNull($this->binding()?->demo_student_id);
    }

    public function test_demo_local_marks_while_pending_says_it_is_waiting(): void
    {
        $this->requestBind();
        $this->resetHttp();

        $this->inbound(self::SENDER, 'MARKS')->assertOk();

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('Waiting for the school to approve', $sent[0]['body']);
        $this->assertStringNotContainsString('Mathematics', $sent[0]['body']);
    }

    public function test_demo_local_a_real_school_code_makes_no_request(): void
    {
        $this->inbound(self::SENDER, 'BIND ' . $this->fx['real']['students'][0]['code'])->assertOk();

        $this->assertSame(0, ParentLinkRequest::count());
        $this->assertStringContainsString('could not find a demo student', $this->outbound()[0]['body']);
    }

    public function test_demo_local_flag_off_keeps_the_immediate_bind(): void
    {
        config(['klassapp.demo.bind_requires_approval' => false]);

        $this->inbound(self::SENDER, 'BIND ' . $this->amina()['code'])->assertOk();

        $this->assertSame(0, ParentLinkRequest::count());
        $this->assertSame((int) $this->amina()['user']->id, (int) $this->binding()->demo_student_id);
        $this->assertStringContainsString('You are now following', $this->outbound()[0]['body']);
    }
}
