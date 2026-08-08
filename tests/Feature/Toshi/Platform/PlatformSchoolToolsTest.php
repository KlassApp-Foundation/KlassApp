<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Tools\Superadmin\CreateSchoolTool;
use App\Ai\Tools\Superadmin\UpdateSchoolTool;
use App\Models\ActivityLog;
use App\Models\City;
use App\Models\Country;
use App\Models\EmisSchool;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class PlatformSchoolToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    private Country $country;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-school@test.sch.ug',
        ]);

        $this->country = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'iso_code' => 'UG',
            'tel_prefix' => '+256',
            'status' => 1,
        ]);

        $this->city = City::create([
            'country_id' => $this->country->id,
            'name' => 'Kampala',
            'status' => 1,
            'state_id' => 0,
        ]);

        // Uganda schools require a valid EMIS/ministry code (shared #185 rule).
        EmisSchool::create([
            'emis_code' => '4527',
            'school_name' => 'BatchA EMIS School',
            'district' => 'Kampala',
            'status' => 1,
        ]);
    }

    private function schoolArgs(array $overrides = []): array
    {
        return array_merge([
            'name' => 'BatchA Audit School',
            'email' => 'batcha.school.'.uniqid().'@example.com',
            'phone' => '0700123456',
            'address' => 'Plot 1 BatchA Road',
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'pincode' => 25601,
            'registration_country' => 'Uganda',
            'ministry_code' => '4527',
            'curriculum' => 'uneb',
            'status' => 1,
        ], $overrides);
    }

    public function test_create_school_happy_path_db_and_audit(): void
    {
        $this->actingAs($this->siteadmin);

        $args = $this->schoolArgs();

        PlatformOperationsAgent::fake([
            new ToolCall('call_school_1', 'CreateSchoolTool', $args),
            'School created.',
        ]);

        $response = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Create school BatchA Audit School.');

        $this->assertFalse($response->hasPendingApprovals());
        $this->assertDatabaseHas('schools', [
            'name' => 'BatchA Audit School',
            'email' => $args['email'],
            'phone' => '0700123456',
            'address' => 'Plot 1 BatchA Road',
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'CreateSchoolTool')
                ->where('properties->status', 'success')
                ->exists()
        );
    }

    public function test_create_school_validation_rejection(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(CreateSchoolTool::class)->handle(new Request([
            'name' => 'No Email School',
            'email' => 'not-an-email',
            'phone' => '123',
            'address' => 'x',
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'pincode' => 1,
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertDatabaseMissing('schools', ['name' => 'No Email School']);
    }

    public function test_update_school_happy_path(): void
    {
        $this->actingAs($this->siteadmin);

        $createArgs = $this->schoolArgs(['email' => 'update.school.'.uniqid().'@example.com']);
        $create = (string) app(CreateSchoolTool::class)->handle(new Request($createArgs));
        $this->assertStringContainsString('School created', $create);

        $school = School::where('email', $createArgs['email'])->firstOrFail();

        $result = (string) app(UpdateSchoolTool::class)->handle(new Request([
            'id' => $school->id,
            'name' => $school->name,
            'email' => $school->email,
            'phone' => '0700987654',
            'address' => 'Updated BatchA Addr',
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'pincode' => (int) $school->pincode,
            'registration_country' => 'Uganda',
            'ministry_code' => '4527',
            'curriculum' => 'uneb',
            'status' => 1,
        ]));

        $this->assertStringContainsString('School updated', $result);
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'phone' => '0700987654',
            'address' => 'Updated BatchA Addr',
        ]);
    }

    public function test_create_school_denied_when_platform_gate_disabled(): void
    {
        config(['toshi.platform_gate.enabled' => false]);
        $this->actingAs($this->siteadmin);

        $result = (string) app(CreateSchoolTool::class)->handle(new Request($this->schoolArgs([
            'name' => 'Gate Closed School',
            'email' => 'gate.closed.'.uniqid().'@example.com',
        ])));

        $this->assertStringContainsString('Platform Toshi access denied', $result);
        $this->assertDatabaseMissing('schools', ['name' => 'Gate Closed School']);
    }
}
