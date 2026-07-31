<?php

namespace Tests\Feature\Superadmin;

use App\Livewire\Superadmin\Reports\Subscriptions;
use App\Livewire\Superadmin\Setting\Cities;
use App\Livewire\Superadmin\Setting\Countries;
use App\Livewire\Superadmin\Setting\PlanList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Guards against stale published filament-tables views calling Table::hasSummary()
 * with the wrong arity (0 args vs package requiring the summary query).
 */
class FilamentTablesHasSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_subscriptions_list_mounts_without_has_summary_arity_error(): void
    {
        $this->assertFilamentListMounts(Subscriptions::class);
    }

    public function test_countries_list_mounts_without_has_summary_arity_error(): void
    {
        $this->assertFilamentListMounts(Countries::class);
    }

    public function test_cities_list_mounts_without_has_summary_arity_error(): void
    {
        $this->assertFilamentListMounts(Cities::class);
    }

    public function test_plans_list_mounts_without_has_summary_arity_error(): void
    {
        $this->assertFilamentListMounts(PlanList::class);
    }

    /**
     * @param  class-string  $component
     */
    private function assertFilamentListMounts(string $component): void
    {
        $admin = User::factory()->create(['usergroup_id' => 1]);

        Livewire::actingAs($admin)
            ->test($component)
            ->assertSuccessful()
            ->assertDontSee('Too few arguments to function')
            ->assertDontSee('hasSummary');
    }
}
