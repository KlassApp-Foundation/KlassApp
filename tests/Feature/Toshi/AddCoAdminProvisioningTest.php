<?php

namespace Tests\Feature\Toshi;

use App\Mail\CoAdminInviteLinkMail;
use App\Mail\CoAdminInviteMail;
use App\Models\CoAdminInvite;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AddCoAdminProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Co-Admin Prov School',
            'slug' => 'co-admin-prov',
            'email' => 'coadmin-prov@test.sch.ug',
            'phone' => '+256700000099',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'UnlimitedAdmins',
            'display_name' => 'UnlimitedAdmins',
            'cycle' => 30,
            'no_of_students' => 999,
            'no_of_users' => 999,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CurrentPlan::create([
            'school_id' => $this->schoolId,
            'plan_id' => $planId,
            'status' => 'running',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'email' => 'primary-admin@test.sch.ug',
            'password' => bcrypt('primary-admin-secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_add_co_admin_issues_invite_without_creating_user(): void
    {
        $result = ToshiActionService::addCoAdmin($this->admin, [
            'name' => 'Second Admin',
            'email' => 'second-admin@test.sch.ug',
        ]);

        $this->assertTrue($result['success']);
        $this->assertNull(User::where('email', 'second-admin@test.sch.ug')->first());

        $invite = CoAdminInvite::where('email', 'second-admin@test.sch.ug')->first();
        $this->assertNotNull($invite);
        $this->assertSame($this->schoolId, (int) $invite->school_id);
        $this->assertSame('Second Admin', $invite->name);
        $this->assertNull($invite->claimed_at);
        $this->assertTrue($invite->expires_at->greaterThan(now()->addHours(70)));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $invite->token_hash);
    }

    public function test_add_co_admin_refuses_already_used_email(): void
    {
        User::factory()->create([
            'email' => 'taken@test.sch.ug',
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
        ]);

        $result = ToshiActionService::addCoAdmin($this->admin, [
            'name' => 'Duplicate Admin',
            'email' => 'taken@test.sch.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
        $this->assertSame(0, CoAdminInvite::where('email', 'taken@test.sch.ug')->count());
    }

    public function test_add_co_admin_does_not_leak_password_in_chat_message(): void
    {
        $result = ToshiActionService::addCoAdmin($this->admin, [
            'name' => 'Chat Safe Admin',
            'email' => 'chat-safe@test.sch.ug',
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('password: `password`', $result['message']);
        $this->assertStringNotContainsString('/ password:', $result['message']);
        $this->assertStringContainsString('invite email', strtolower($result['message']));
    }

    public function test_add_co_admin_queues_invite_link_mail_without_password(): void
    {
        ToshiActionService::addCoAdmin($this->admin, [
            'name' => 'Mail Admin',
            'email' => 'mail-admin@test.sch.ug',
        ]);

        Mail::assertQueued(CoAdminInviteLinkMail::class, function (CoAdminInviteLinkMail $mail) {
            return str_contains($mail->inviteUrl, '/invite/co-admin/')
                && ! property_exists($mail, 'password');
        });
        Mail::assertNotQueued(CoAdminInviteMail::class);
    }
}
