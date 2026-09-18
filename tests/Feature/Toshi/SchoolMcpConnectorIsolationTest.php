<?php

namespace Tests\Feature\Toshi;

use App\Models\SchoolMcpConnector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolMcpConnectorIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function createSchool(string $suffix): int
    {
        return DB::table('schools')->insertGetId([
            'name' => "School {$suffix}",
            'slug' => "school-{$suffix}-" . uniqid(),
            'email' => "school-{$suffix}-" . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_school_a_token_resolution_does_not_leak_to_school_b(): void
    {
        $schoolA = $this->createSchool('A');
        $schoolB = $this->createSchool('B');

        $adminA = User::factory()->create(['school_id' => $schoolA, 'usergroup_id' => 3]);
        $adminB = User::factory()->create(['school_id' => $schoolB, 'usergroup_id' => 3]);

        SchoolMcpConnector::create([
            'school_id' => $schoolA,
            'connector_type' => 'slack',
            'external_team_id' => 'TA',
            'credentials' => ['access_token' => 'token-a-secret'],
            'status' => 'active',
        ]);

        SchoolMcpConnector::create([
            'school_id' => $schoolB,
            'connector_type' => 'slack',
            'external_team_id' => 'TB',
            'credentials' => ['access_token' => 'token-b-secret'],
            'status' => 'active',
        ]);

        $this->actingAs($adminA);
        $tokenA = SchoolMcpConnector::resolveTokenForRequest('slack');
        $this->assertSame('token-a-secret', $tokenA);

        $this->actingAs($adminB);
        $tokenB = SchoolMcpConnector::resolveTokenForRequest('slack');
        $this->assertSame('token-b-secret', $tokenB);
    }

    public function test_disabled_connector_does_not_resolve(): void
    {
        $school = $this->createSchool('dis');
        $admin = User::factory()->create(['school_id' => $school, 'usergroup_id' => 3]);

        SchoolMcpConnector::create([
            'school_id' => $school,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => ['access_token' => 'secret'],
            'status' => 'disabled',
        ]);

        $this->actingAs($admin);
        $token = SchoolMcpConnector::resolveTokenForRequest('slack');

        $this->assertNull($token);
    }

    public function test_unconnected_connector_returns_null(): void
    {
        $school = $this->createSchool('none');
        $admin = User::factory()->create(['school_id' => $school, 'usergroup_id' => 3]);

        $this->actingAs($admin);
        $token = SchoolMcpConnector::resolveTokenForRequest('slack');

        $this->assertNull($token);
    }

    public function test_different_connector_types_are_isolated(): void
    {
        $school = $this->createSchool('types');
        $admin = User::factory()->create(['school_id' => $school, 'usergroup_id' => 3]);

        SchoolMcpConnector::create([
            'school_id' => $school,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => ['access_token' => 'slack-token'],
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $slackToken = SchoolMcpConnector::resolveTokenForRequest('slack');
        $this->assertSame('slack-token', $slackToken);

        $notionToken = SchoolMcpConnector::resolveTokenForRequest('notion');
        $this->assertNull($notionToken);
    }
}
