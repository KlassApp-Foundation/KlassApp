<?php

namespace Tests\Feature\Demo;

use App\Http\Middleware\PublicDemoLockdown;
use App\Livewire\Admin\WhatsappDashboardCard;
use App\Livewire\Admin\WhatsappLinkRequests;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoBindApproval;
use App\Services\Demo\DemoFollowAlong;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsApp\LinkRequestBoard;
use App\States\Approval\Pending;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * WhatsApp link-request cards (WhatsApp page and the v2 dashboard card).
 * Approve and reject go through ApprovalDecisionService, the same code as
 * the Approvals page. Unlink uses the same column changes as demo:expire-chats.
 */
class WhatsappLinkRequestCardsTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    private const EMPTY = 'No requests yet. Scan the QR on /demo to try it.';

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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_empty_demo_school_shows_the_scan_sentence_and_polls(): void
    {
        $admin = $this->admin(public: true);

        Livewire::actingAs($admin)
            ->test(WhatsappLinkRequests::class)
            ->assertSee(self::EMPTY)
            ->assertSee('Link requests')
            ->assertSee('Linked now')
            ->assertSeeHtml('wire:poll.4s')
            ->assertSeeHtml('data-poll-seconds="4"');

        Livewire::actingAs($admin)
            ->test(WhatsappDashboardCard::class)
            ->assertSee(self::EMPTY)
            ->assertSeeHtml('<span class="wa-dash-card__count-num">0</span>')
            ->assertSeeHtml('wire:poll.4s')
            ->assertSeeHtml('data-testid="dashboard-whatsapp-card"');
    }

    public function test_pending_card_shows_number_student_class_and_time_left(): void
    {
        $admin = $this->admin();
        $this->requestBind('256700000941');

        Livewire::actingAs($admin)
            ->test(WhatsappLinkRequests::class)
            ->assertSee('256700000941')
            ->assertSee('Amina Nakato')
            ->assertSee('P.7 A')
            ->assertSee('44 min left')
            ->assertSee('Approve')
            ->assertSee('Reject');
    }

    public function test_approve_sends_the_confirmation_and_starts_the_ttl_at_approval(): void
    {
        $admin = $this->admin(public: true);
        $request = $this->requestBind('256700000941');
        $this->resetHttp();
        Carbon::setTestNow(now()->addMinutes(10));

        Livewire::actingAs($admin)
            ->test(WhatsappLinkRequests::class)
            ->call('approve', $request->id)
            ->assertSee('now follows Amina Nakato');

        $row = WhatsAppUser::where('phone', '256700000941')->first();
        $this->assertNotNull($row);
        $this->assertSame((int) $this->amina()['user']->id, (int) $row->demo_student_id);
        $this->assertTrue($row->demo_linked_at->equalTo(now()));
        $this->assertSame('approved', $request->fresh()->status);

        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertSame('256700000941', $sent[0]['to']);
        $this->assertStringContainsString('You are now following *Amina Nakato*', $sent[0]['body']);
        $this->assertSame(1, app(LinkRequestBoard::class)->linkedNumberCount((int) $admin->school_id));
    }

    public function test_reject_sends_the_polite_reply_and_links_nothing(): void
    {
        $admin = $this->admin(public: true);
        $request = $this->requestBind('256700000942');
        $this->resetHttp();

        Livewire::actingAs($admin)
            ->test(WhatsappDashboardCard::class)
            ->call('reject', $request->id)
            ->assertSee('not linked');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertNull(WhatsAppUser::where('phone', '256700000942')->first()?->demo_student_id);
        $sent = $this->outbound();
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('Thank you for trying the KlassApp demo', $sent[0]['body']);
    }

    public function test_unlink_clears_the_session_and_sends_nothing(): void
    {
        $admin = $this->admin(public: true);
        $request = $this->requestBind('256700000943');
        Livewire::actingAs($admin)->test(WhatsappLinkRequests::class)->call('approve', $request->id);
        $row = WhatsAppUser::where('phone', '256700000943')->firstOrFail();
        $this->resetHttp();

        Livewire::actingAs($admin)
            ->test(WhatsappLinkRequests::class)
            ->assertSee('Unlink')
            ->call('unlink', $row->id)
            ->assertSee('no longer follows');

        $row->refresh();
        $this->assertNull($row->demo_student_id);
        $this->assertNull($row->user_id);
        $this->assertNull($row->school_id);
        $this->assertSame([], $this->outbound());
    }

    public function test_dashboard_card_shows_only_the_latest_three(): void
    {
        $admin = $this->admin();
        $phones = ['256700000951', '256700000952', '256700000953', '256700000954'];
        foreach ($phones as $phone) {
            $this->requestBind($phone);
        }

        $html = Livewire::actingAs($admin)->test(WhatsappDashboardCard::class)->html();

        $this->assertStringContainsString('<span class="wa-dash-card__count-num">4</span>', $html);
        $this->assertStringNotContainsString('256700000951', $html);
        $this->assertStringContainsString('256700000954', $html);
        $this->assertStringContainsString('256700000953', $html);
        $this->assertStringContainsString('256700000952', $html);
        $this->assertSame(3, substr_count($html, 'data-testid="wa-link-request"'));
    }

    public function test_another_schools_request_cannot_be_approved_from_this_school(): void
    {
        $admin = $this->admin();
        $other = ParentLinkRequest::create([
            'school_id' => $this->fx['real']['school']->id,
            'phone' => '256700000999',
            'parent_name' => 'Other',
            'child_name' => 'Zed Realpupil',
            'child_class' => 'P.7 A',
            'school_name' => 'Hillside Real Primary',
            'suggested_student_id' => $this->fx['real']['students'][0]['user']->id,
            'status' => 'pending',
            'flow_token' => DemoBindApproval::REQUEST_TOKEN,
            'candidate_student_ids' => [$this->fx['real']['students'][0]['user']->id],
        ]);
        Approval::create([
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $other->id,
            'state' => Pending::class,
            'comments' => 'other school',
        ]);

        Livewire::actingAs($admin)
            ->test(WhatsappLinkRequests::class)
            ->call('approve', $other->id)
            ->assertSee('already handled');

        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_public_demo_lockdown_allows_these_components(): void
    {
        $admin = $this->admin(public: true);
        foreach (['admin.whatsapp-link-requests', 'admin.whatsapp-dashboard-card'] as $name) {
            $route = new Route(['POST'], 'livewire/update', fn () => 'ok');
            $route->name('default.livewire.update');
            $request = Request::create('/livewire/update', 'POST', [
                'components' => [['snapshot' => json_encode(['memo' => ['name' => $name]])]],
            ]);
            $route->bind($request);
            $request->setRouteResolver(fn () => $route);

            $this->assertNull(PublicDemoLockdown::refusal($request, $admin), $name);
        }
    }

    public function test_v2_dashboard_mounts_the_card_and_kpi_css_keeps_number_and_label_apart(): void
    {
        $v2 = file_get_contents(resource_path('views/admin/dashboard/dashboard_v2.blade.php'));
        $this->assertStringContainsString('<livewire:admin.whatsapp-dashboard-card />', $v2);

        $css = file_get_contents(public_path('css/dashboard-refresh.css'));
        $this->assertStringNotContainsString('[class*="kpi"] a', $css);

        $v2css = file_get_contents(public_path('css/dashboard-v2.css'));
        $this->assertStringContainsString('.dv2-kpi > a.dv2-kpi-link', $v2css);
        $this->assertStringContainsString('flex-direction:column', str_replace(' ', '', $v2css));
    }

    public function test_seeded_unverified_parents_do_not_count_as_linked_numbers(): void
    {
        $schoolId = (int) $this->fx['demo']['school']->id;
        $this->assertSame(0, app(LinkRequestBoard::class)->linkedNumberCount($schoolId));

        $parent = $this->amina()['parent'];
        WhatsAppUser::where('user_id', $parent->id)->update(['verified_at' => now()]);
        $this->assertSame(1, app(LinkRequestBoard::class)->linkedNumberCount($schoolId));
    }

    public function test_drive_row_is_hidden_unless_the_school_connected_it(): void
    {
        $blade = file_get_contents(resource_path('views/admin/dashboard/dashboard.blade.php'));
        $this->assertStringContainsString("!empty(\$connectedTools['driveConnected'])", $blade);
        $this->assertStringContainsString('dashboard-connected-whatsapp-count', $blade);
        $this->assertMatchesRegularExpression(
            '/@if\(!empty\(\$connectedTools\[\'driveConnected\'\]\)\).*dashboard-connected-drive/s',
            $blade
        );
    }

    private function admin(bool $public = false): User
    {
        $school = $this->fx['demo']['school'];
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Admin demo',
            'email' => 'admin-cards' . ($public ? '-public' : '') . '@example.com',
            'password' => bcrypt('x'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        if ($public) {
            $admin->forceFill(['is_public_demo' => true])->save();
        }
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'Demo',
            'status' => 'active',
        ]);

        return $admin->fresh();
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

    private function requestBind(string $phone): ParentLinkRequest
    {
        return app(DemoBindApproval::class)->request(
            $phone,
            $this->amina()['user'],
            $this->fx['demo']['school'],
            'Guest',
        );
    }
}
