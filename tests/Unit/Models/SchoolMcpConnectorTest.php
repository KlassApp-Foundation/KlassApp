<?php

namespace Tests\Unit\Models;

use App\Models\School;
use App\Models\SchoolMcpConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolMcpConnectorTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Test School',
            'slug' => 'test-school-' . uniqid(),
            'email' => 'test-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_credentials_are_encrypted(): void
    {
        $connector = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T12345',
            'credentials' => [
                'access_token' => 'xoxb-real-token',
                'refresh_token' => 'xoxe-real-refresh',
                'token_type' => 'Bearer',
            ],
            'status' => 'active',
        ]);

        $raw = \DB::table('school_mcp_connectors')
            ->where('id', $connector->id)
            ->value('credentials');

        $this->assertStringNotContainsString('xoxb-real-token', $raw);
        $this->assertStringNotContainsString('xoxe-real-refresh', $raw);

        $decrypted = $connector->fresh()->credentials;
        $this->assertSame('xoxb-real-token', $decrypted['access_token']);
        $this->assertSame('xoxe-real-refresh', $decrypted['refresh_token']);
    }

    public function test_credentials_are_hidden_from_json(): void
    {
        $connector = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T12345',
            'credentials' => [
                'access_token' => 'secret',
                'refresh_token' => 'secret',
            ],
            'status' => 'active',
        ]);

        $json = $connector->toArray();

        $this->assertArrayNotHasKey('credentials', $json);
    }

    public function test_is_active(): void
    {
        $active = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => ['access_token' => 'tok'],
            'status' => 'active',
        ]);

        $disabled = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T2',
            'credentials' => ['access_token' => 'tok'],
            'status' => 'disabled',
        ]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($disabled->isActive());
    }

    public function test_is_token_expired(): void
    {
        $expired = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => ['access_token' => 'tok'],
            'token_expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $valid = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T2',
            'credentials' => ['access_token' => 'tok'],
            'token_expires_at' => now()->addHour(),
            'status' => 'active',
        ]);

        $this->assertTrue($expired->isTokenExpired());
        $this->assertFalse($valid->isTokenExpired());
    }

    public function test_scopes(): void
    {
        SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => ['access_token' => 'tok'],
            'status' => 'active',
        ]);

        SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'notion',
            'external_team_id' => 'N1',
            'credentials' => ['access_token' => 'tok'],
            'status' => 'disabled',
        ]);

        $active = SchoolMcpConnector::active()->get();
        $this->assertCount(1, $active);

        $slack = SchoolMcpConnector::forType('slack')->get();
        $this->assertCount(1, $slack);

        $forSchool = SchoolMcpConnector::forSchool($this->schoolId)->get();
        $this->assertCount(2, $forSchool);
    }

    public function test_unique_constraint_per_school_type_team(): void
    {
        SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => ['access_token' => 'tok'],
            'status' => 'active',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => ['access_token' => 'tok2'],
            'status' => 'active',
        ]);
    }
}
