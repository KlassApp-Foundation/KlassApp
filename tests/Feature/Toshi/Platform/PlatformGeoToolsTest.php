<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Tools\Superadmin\CreateCountryTool;
use App\Ai\Tools\Superadmin\CreateCityTool;
use App\Ai\Tools\Superadmin\UpdateCountryTool;
use App\Ai\Tools\Superadmin\UpdateCityTool;
use App\Models\ActivityLog;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use App\Services\Toshi\PlatformToolInvoker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class PlatformGeoToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    private User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-geo@test.sch.ug',
        ]);

        $this->schoolAdmin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => null,
            'email' => 'schooladmin-geo@test.sch.ug',
        ]);
    }

    public function test_create_country_happy_path_writes_db_and_audit(): void
    {
        $this->actingAs($this->siteadmin);

        $agent = new PlatformOperationsAgent;
        $tool = app(CreateCountryTool::class);
        $args = [
            'name' => 'Testland',
            'short_name' => 'TLND',
            'iso_code' => 'TL',
            'tel_prefix' => '+999',
            'status' => 1,
        ];

        $outcome = app(PlatformToolInvoker::class)->invoke($tool, $args, null, $agent);

        $this->assertSame('executed', $outcome['status']);
        $this->assertStringContainsString('Country created', $outcome['result']);
        $this->assertDatabaseHas('countries', [
            'name' => 'Testland',
            'short_name' => 'TLND',
            'iso_code' => 'TL',
            'tel_prefix' => '+999',
            'status' => 1,
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('causer_id', $this->siteadmin->id)
                ->where('properties->tool', 'CreateCountryTool')
                ->where('properties->status', 'success')
                ->exists()
        );
    }

    public function test_create_country_validation_rejection(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(CreateCountryTool::class)->handle(new Request([
            'name' => '',
            'short_name' => 'X',
            'iso_code' => 'X',
            'tel_prefix' => '+1',
            'status' => 1,
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertSame(0, Country::where('short_name', 'X')->count());
    }

    public function test_update_country_happy_path(): void
    {
        $this->actingAs($this->siteadmin);

        $country = Country::create([
            'name' => 'Oldland',
            'short_name' => 'OLD',
            'iso_code' => 'OL',
            'tel_prefix' => '+111',
            'status' => 1,
        ]);

        $result = (string) app(UpdateCountryTool::class)->handle(new Request([
            'id' => $country->id,
            'name' => 'Newland',
            'short_name' => 'NEW',
            'iso_code' => 'NW',
            'tel_prefix' => '+222',
            'status' => 0,
        ]));

        $this->assertStringContainsString('Country updated', $result);
        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Newland',
            'status' => 0,
        ]);
    }

    public function test_create_and_update_city_happy_path(): void
    {
        $this->actingAs($this->siteadmin);

        $country = Country::create([
            'name' => 'GeoLand',
            'short_name' => 'GEO',
            'iso_code' => 'GE',
            'tel_prefix' => '+100',
            'status' => 1,
        ]);

        $create = (string) app(CreateCityTool::class)->handle(new Request([
            'country_id' => $country->id,
            'name' => 'BatchB City',
            'status' => 1,
        ]));

        $this->assertStringContainsString('City created', $create);
        $city = City::where('name', 'BatchB City')->first();
        $this->assertNotNull($city);

        $update = (string) app(UpdateCityTool::class)->handle(new Request([
            'id' => $city->id,
            'country_id' => $country->id,
            'name' => 'BatchB City Updated',
            'status' => 1,
        ]));

        $this->assertStringContainsString('City updated', $update);
        $this->assertDatabaseHas('cities', [
            'id' => $city->id,
            'name' => 'BatchB City Updated',
        ]);
    }

    public function test_create_city_validation_rejects_missing_country(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(CreateCityTool::class)->handle(new Request([
            'country_id' => 999999,
            'name' => 'Nowhere',
            'status' => 1,
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertDatabaseMissing('cities', ['name' => 'Nowhere']);
    }

    public function test_school_admin_denied_platform_geo_tools(): void
    {
        $this->actingAs($this->schoolAdmin);

        $result = (string) app(CreateCountryTool::class)->handle(new Request([
            'name' => 'DeniedLand',
            'short_name' => 'DNY',
            'iso_code' => 'DN',
            'tel_prefix' => '+000',
            'status' => 1,
        ]));

        $this->assertStringContainsString('Platform Toshi access denied', $result);
        $this->assertDatabaseMissing('countries', ['name' => 'DeniedLand']);
    }
}
