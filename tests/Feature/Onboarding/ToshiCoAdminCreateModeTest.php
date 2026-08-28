<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Mail\CoAdminInviteMail;
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

    public function test_create_mode_co_admin_gets_own_password_not_primary_admin(): void
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
        $coAdmin = User::where('email', 'secondary@coadmin.sch.ug')->first();

        $this->assertNotNull($primary);
        $this->assertNotNull($coAdmin);
        $this->assertTrue(Hash::check('primary-only-secret', $primary->password));
        $this->assertFalse(Hash::check('primary-only-secret', $coAdmin->password));
        $this->assertNotSame($primary->password, $coAdmin->password);
        $this->assertSame(1, (int) $coAdmin->is_reset);

        Mail::assertQueued(CoAdminInviteMail::class, function (CoAdminInviteMail $mail) use ($coAdmin) {
            return $mail->email === 'secondary@coadmin.sch.ug'
                && $mail->password !== 'primary-only-secret'
                && $mail->password !== 'password'
                && Hash::check($mail->password, $coAdmin->password);
        });
    }
}
