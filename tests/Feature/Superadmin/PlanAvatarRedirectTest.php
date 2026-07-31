<?php

namespace Tests\Feature\Superadmin;

use App\Livewire\Superadmin\ChangeAvatar;
use App\Livewire\Superadmin\Setting\PlanForm;
use App\Models\Plan;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PlanAvatarRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_plan_update_redirects_to_plans_list_not_plans_id_concat(): void
    {
        $admin = User::factory()->create(['usergroup_id' => 1]);

        $plan = Plan::create([
            'cycle' => 'monthly',
            'name' => 'Redirect Plan',
            'display_name' => 'Redirect Plan',
            'order' => 1,
            'is_active' => 1,
            'amount' => 1000,
            'no_of_users' => 10,
            'no_of_students' => 100,
        ]);

        Livewire::actingAs($admin)
            ->test(PlanForm::class, ['id' => $plan->id])
            ->set('cycle', 'monthly')
            ->set('name', 'Redirect Plan Updated')
            ->set('order', 1)
            ->set('status', 1)
            ->set('amount', 2000)
            ->set('no_of_users', 10)
            ->set('no_of_students', 100)
            ->call('submitPlan')
            ->assertRedirect('/superadmin/setting/plans');
    }

    public function test_avatar_upload_redirects_to_superadmin_dashboard(): void
    {
        Storage::fake('uploads');

        $admin = User::factory()->create(['usergroup_id' => 1]);
        Userprofile::create([
            'user_id' => $admin->id,
            'usergroup_id' => 1,
            'firstname' => 'Site',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(ChangeAvatar::class)
            ->set('image', UploadedFile::fake()->image('avatar.png'))
            ->call('submitAvatar')
            ->assertRedirect('/superadmin/dashboard');
    }

    public function test_dashboard_blade_does_not_link_to_dead_superadmin_users(): void
    {
        $contents = file_get_contents(resource_path('views/superadmin/dashboard.blade.php'));

        $this->assertStringNotContainsString(
            "/superadmin/users",
            $contents,
            'Dead /superadmin/users link must stay removed (no platform-wide users route).'
        );
    }
}
