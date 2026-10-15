<?php

namespace Tests\Feature\Demo;

use App\Http\Middleware\PublicDemoLockdown;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\PortalProfileLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * The live demo admin's password is public. users.is_public_demo turns on
 * PublicDemoLockdown for that one account; every other user is untouched.
 */
class PublicDemoLockdownTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->relaxDeliveryLog();
        $this->buildSchools();
        // A route with an implicitly bound record, to test the school check itself.
        Route::middleware('web')->get('/_lockdown-probe/{user}', fn (User $user) => 'ok:' . $user->id);
    }

    private function admin(string $which = 'demo', bool $public = true): User
    {
        $school = $this->fx[$which]['school'];
        $admin = User::create(['school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Admin ' . $which, 'email' => 'admin-' . $which . ($public ? '' : '-2') . '@example.com', 'password' => bcrypt('x'), 'status' => 'active', 'email_verified' => 1]);
        $admin->forceFill(['is_public_demo' => $public])->save();
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'Admin', 'lastname' => ucfirst($which), 'status' => 'active']);

        return $admin->fresh();
    }

    public function test_public_demo_admin_works_inside_its_own_school(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/approvals')->assertOk();
        $own = $this->fx['demo']['students'][0]['user'];
        $this->actingAs($admin)->get('/_lockdown-probe/' . $own->id)->assertOk()->assertSee('ok:' . $own->id);
    }

    public function test_public_demo_admin_gets_404_for_a_record_of_another_school(): void
    {
        $admin = $this->admin();
        $other = $this->fx['real']['students'][0]['user'];
        $this->actingAs($admin)->get('/_lockdown-probe/' . $other->id)->assertNotFound();
        $status = $this->actingAs($admin)->get('/admin/students/' . $other->id)->getStatusCode();
        $this->assertContains($status, [403, 404], 'another school\'s student profile');
    }

    public function test_public_demo_admin_is_refused_site_admin_and_platform_routes(): void
    {
        $admin = $this->admin();
        foreach (['/superadmin/dashboard', '/superadmin/academics/schools', '/platform/early-access', '/tinker'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertForbidden();
        }
    }

    public function test_public_demo_admin_cannot_change_its_own_password_email_or_name(): void
    {
        $admin = $this->admin();
        foreach (['/admin/changepassword', '/admin/editprofile', '/admin/profile', '/admin/changeavatar', '/password/force-change'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertForbidden();
        }
        $this->actingAs($admin)->post('/admin/changepassword', ['password' => 'new-one-123', 'password_confirmation' => 'new-one-123'])->assertForbidden();
        $this->actingAs($admin)->post('/admin/profile', ['name' => 'Hacked', 'email' => 'x@example.org'])->assertForbidden();
        $this->actingAs($admin)->post('/password/email', ['email' => $admin->email])->assertForbidden();

        $fresh = $admin->fresh();
        $this->assertSame('Admin demo', $fresh->name);
        $this->assertSame('admin-demo@example.com', $fresh->email);

        $links = PortalProfileLinks::forUser($fresh);
        $this->assertNull($links['change_password']);
        $this->assertNull($links['edit_profile']);
        $this->assertNull($links['change_avatar']);
    }

    public function test_public_demo_admin_cannot_invite_reset_others_or_impersonate(): void
    {
        $admin = $this->admin();
        $teacher = $this->fx['demo']['teacher'];
        $this->actingAs($admin)->post('/admin/class-teacher-invite/1/store', ['email' => 'someone@example.org'])->assertForbidden();
        $this->actingAs($admin)->get('/admin/user/resetPassword/' . $teacher->id)->assertForbidden();
        $this->actingAs($admin)->get('/teacher/' . $teacher->id . '/impersonate')->assertForbidden();
    }

    public function test_mail_started_by_the_public_demo_admin_is_refused(): void
    {
        config(['mail.default' => 'array', 'mail.from.address' => 'noreply@example.com', 'mail.from.name' => 'KlassApp']);
        Context::addHidden(PublicDemoLockdown::CONTEXT_KEY, 1);
        Mail::raw('hello', fn ($m) => $m->to('anyone@example.org')->subject('x'));
        $this->assertCount(0, app('mailer')->getSymfonyTransport()->messages());

        Context::forgetHidden(PublicDemoLockdown::CONTEXT_KEY);
        Mail::raw('hello', fn ($m) => $m->to('anyone@example.org')->subject('x'));
        $this->assertCount(1, app('mailer')->getSymfonyTransport()->messages());
    }

    public function test_public_demo_admin_cannot_upload_or_import_files(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->create('logo.png', 10, 'image/png');
        // Any request carrying a file, even to an allowed route.
        $this->actingAs($admin)->post('/admin/marks/releases/preview', ['exam_id' => 1, 'when' => 'now', 'f' => $file])->assertForbidden();
        $this->actingAs($admin)->post('/livewire/upload-file', ['files' => [$file]])->assertForbidden();
        $this->actingAs($admin)->post('/admin/importUsers', [])->assertForbidden();
        $this->actingAs($admin)->get('/admin/import')->assertForbidden();
        $this->actingAs($admin)->post('/admin/document/add/x', [])->assertForbidden();
    }

    public function test_public_demo_admin_cannot_broadcast_remind_or_add_numbers(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/student/sendMessageToAll', ['message' => 'hi'])->assertForbidden();
        $this->actingAs($admin)->post('/admin/teacher/sendMessageToAll', ['message' => 'hi'])->assertForbidden();
        $this->actingAs($admin)->post('/admin/fees/payments/reminders', [])->assertForbidden();
        $this->actingAs($admin)->post('/admin/dashboard/send/reminder/1', [])->assertForbidden();
        $this->actingAs($admin)->post('/admin/phonenumber/add', ['phone' => '+256700000001'])->assertForbidden();
        $this->actingAs($admin)->post('/admin/whatsapp/phone', ['phone' => '+256700000001'])->assertForbidden();
        $this->actingAs($admin)->get('/admin/settings/integrations')->assertForbidden();
        $this->actingAs($admin)->get('/admin/subscriptions')->assertForbidden();
    }

    public function test_public_demo_admin_cannot_use_toshi_or_profile_livewire_components(): void
    {
        $admin = $this->admin();
        $payload = ['components' => [['snapshot' => json_encode(['memo' => ['name' => 'agent-toshi']]), 'updates' => [], 'calls' => []]]];
        $this->actingAs($admin)->postJson('/livewire/update', $payload)->assertForbidden();
    }

    public function test_a_normal_school_admin_is_untouched(): void
    {
        $admin = $this->admin('demo', false);
        $this->assertNotSame(403, $this->actingAs($admin)->get('/admin/changepassword')->getStatusCode());
        $other = $this->fx['real']['students'][0]['user'];
        $this->actingAs($admin)->get('/_lockdown-probe/' . $other->id)->assertOk();
        $this->assertNotNull(PortalProfileLinks::forUser($admin)['change_password']);
    }
}
