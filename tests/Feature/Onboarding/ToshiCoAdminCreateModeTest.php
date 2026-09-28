<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Mail\CoAdminInviteLinkMail;
use App\Mail\CoAdminInviteMail;
use App\Models\CoAdminInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiCoAdminCreateModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_mode_co_admin_gets_invite_link_not_password(): void
    {
        Mail::fake();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $superadmin = User::create([
            'school_id' => null,
            'usergroup_id' => 1,
            'name' => 'Super Admin',
            'email' => 'super@coadmin-create.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->actingAs($superadmin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'create');
        $component->set('schoolName', 'Co Admin Split School');
        $component->set('schoolEmail', 'split-school@coadmin.sch.ug');
        $component->set('schoolPhone', '0700999888');
        $component->set('adminName', 'Primary Admin');
        $component->set('adminEmail', 'primary@coadmin.sch.ug');
        $component->set('adminPassword', 'primary-only-secret');
        $component->set('coAdminName', 'Secondary Admin');
        $component->set('coAdminEmail', 'secondary@coadmin.sch.ug');
        $component->set('schoolType', 'primary');
        $component->set('curriculum', 'uneb');
        $component->set('selectedPlanId', 1);
        $component->set('standards', [['name' => 'P1']]);

        $component->call('confirmOnboarding');

        $schoolId = (int) $component->get('schoolId');
        $this->assertGreaterThan(0, $schoolId);

        $primary = User::where('school_id', $schoolId)->where('email', 'primary@coadmin.sch.ug')->first();
        $this->assertNotNull($primary);
        $this->assertTrue(Hash::check('primary-only-secret', $primary->password));

        $this->assertNull(User::where('email', 'secondary@coadmin.sch.ug')->first());

        $invite = CoAdminInvite::where('email', 'secondary@coadmin.sch.ug')->first();
        $this->assertNotNull($invite);
        $this->assertSame($schoolId, (int) $invite->school_id);
        $this->assertSame('Secondary Admin', $invite->name);
        $this->assertNull($invite->claimed_at);
        $this->assertTrue($invite->expires_at->greaterThan(now()->addHours(70)));

        Mail::assertQueued(CoAdminInviteLinkMail::class, function (CoAdminInviteLinkMail $mail) use ($schoolId) {
            return str_contains($mail->inviteUrl, '/invite/co-admin/')
                && $mail->schoolName === 'Co Admin Split School'
                && ! property_exists($mail, 'password');
        });
        Mail::assertNotQueued(CoAdminInviteMail::class);
    }
}
