<?php

namespace Tests\Feature\Superadmin;

use App\Livewire\Superadmin\Setting\CountryForm;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CountryCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_country_create_route_is_registered(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('superadmin.setting.countries.create')
        );
    }

    public function test_submit_country_creates_row_and_redirects_to_list(): void
    {
        $admin = User::factory()->create(['usergroup_id' => 1]);

        Livewire::actingAs($admin)
            ->test(CountryForm::class, ['id' => ''])
            ->set('name', 'Testland')
            ->set('short_name', 'TLND')
            ->set('iso_code', 'TL')
            ->set('tel_prefix', '+999')
            ->set('status', 1)
            ->call('submitCountry')
            ->assertRedirect('/superadmin/setting/countries');

        $this->assertDatabaseHas('countries', [
            'name' => 'Testland',
            'short_name' => 'TLND',
            'iso_code' => 'TL',
            'tel_prefix' => '+999',
            'status' => 1,
        ]);
    }

    public function test_submit_country_updates_existing_row(): void
    {
        $admin = User::factory()->create(['usergroup_id' => 1]);

        $country = Country::create([
            'name' => 'Oldland',
            'short_name' => 'OLD',
            'iso_code' => 'OL',
            'tel_prefix' => '+111',
            'status' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(CountryForm::class, ['id' => $country->id])
            ->set('name', 'Newland')
            ->set('short_name', 'NEW')
            ->set('iso_code', 'NW')
            ->set('tel_prefix', '+222')
            ->set('status', 0)
            ->call('submitCountry')
            ->assertRedirect('/superadmin/setting/countries');

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Newland',
            'short_name' => 'NEW',
            'status' => 0,
        ]);
    }
}
