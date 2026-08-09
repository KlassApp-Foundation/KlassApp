<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ManualUiWave3ToshiMaximizeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Maximize Test School',
            'email' => 'max@test.sch.ug',
            'phone' => '0700000055',
            'slug' => 'maximize-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Max Admin',
            'email' => 'admin@max.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Max',
            'lastname' => 'Admin',
        ]);
    }

    public function test_maximize_opens_modal_and_hides_narrow_panel(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AgentToshi::class)
            ->call('show')
            ->assertSet('visible', true)
            ->assertSet('maximized', false)
            ->call('maximize')
            ->assertSet('maximized', true)
            ->assertSet('visible', false)
            ->assertSeeHtml('id="toshi-modal"')
            ->call('restore')
            ->assertSet('maximized', false)
            ->assertSet('visible', true);
    }

    public function test_panel_header_exposes_expand_control(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AgentToshi::class)
            ->call('show')
            ->assertSeeHtml('data-testid="toshi-expand"')
            ->assertSeeHtml('wire:click="maximize"')
            ->assertSeeHtml('x-on:toshi-maximize.window');
    }

    public function test_dashboard_banner_dispatches_maximize_for_toshi_cta(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('toshi-maximize', false);
        $response->assertSee('data-testid="setup-banner-toshi"', false);
    }
}
