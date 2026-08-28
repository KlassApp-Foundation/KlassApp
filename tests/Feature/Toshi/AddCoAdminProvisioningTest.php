<?php

namespace Tests\Feature\Toshi;

use App\Mail\CoAdminInviteMail;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function test_add_co_admin_uses_unique_password_and_is_reset(): void
    {
        $result = ToshiActionService::addCoAdmin($this->admin, [
            'name' => 'Second Admin',
            'email' => 'second-admin@test.sch.ug',
        ]);

        $this->assertTrue($result['success']);

        $coAdmin = User::where('email', 'second-admin@test.sch.ug')->first();
        $this->assertNotNull($coAdmin);
        $this->assertSame(1, (int) $coAdmin->is_reset);
        $this->assertFalse(Hash::check('password', $coAdmin->password));
        $this->assertFalse(Hash::check('primary-admin-secret', $coAdmin->password));
        $this->assertNotSame($this->admin->password, $coAdmin->password);
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

    public function test_add_co_admin_queues_invite_mail_with_co_admin_password(): void
    {
        ToshiActionService::addCoAdmin($this->admin, [
            'name' => 'Mail Admin',
            'email' => 'mail-admin@test.sch.ug',
        ]);

        Mail::assertQueued(CoAdminInviteMail::class, function (CoAdminInviteMail $mail) {
            $coAdmin = User::where('email', 'mail-admin@test.sch.ug')->first();

            return $mail->email === 'mail-admin@test.sch.ug'
                && $mail->password !== null
                && $mail->password !== 'password'
                && $mail->password !== 'primary-admin-secret'
                && Hash::check($mail->password, $coAdmin->password);
        });
    }
}
