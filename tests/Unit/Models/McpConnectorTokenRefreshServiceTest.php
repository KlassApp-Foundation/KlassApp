<?php

namespace Tests\Unit\Models;

use App\Models\McpConnectorTokenRefreshService;
use App\Models\SchoolMcpConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class McpConnectorTokenRefreshServiceTest extends TestCase
{
    use RefreshDatabase;

    private McpConnectorTokenRefreshService $service;
    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new McpConnectorTokenRefreshService;

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Refresh Test School',
            'slug' => 'refresh-test-' . uniqid(),
            'email' => 'refresh-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_refresh_succeeds_with_valid_response(): void
    {
        config(['toshi.mcp_connectors.slack' => [
            'token_url' => 'https://slack.com/api/oauth.v2.access',
            'oauth_client_id' => 'client-id',
            'oauth_secret' => 'client-secret',
        ]]);

        $connector = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => [
                'access_token' => 'old-token',
                'refresh_token' => 'refresh-token',
                'token_type' => 'Bearer',
            ],
            'token_expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        Http::fake([
            'https://slack.com/api/oauth.v2.access' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $result = $this->service->refresh($connector);

        $this->assertTrue($result);

        $connector->refresh();
        $this->assertSame('new-access-token', $connector->accessToken());
        $this->assertSame('new-refresh-token', $connector->refreshToken());
        $this->assertTrue($connector->token_expires_at->isAfter(now()->subMinutes(59)));
    }

    public function test_refresh_fails_without_refresh_token(): void
    {
        $connector = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => [
                'access_token' => 'old-token',
            ],
            'token_expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $result = $this->service->refresh($connector);

        $this->assertFalse($result);
    }

    public function test_refresh_fails_on_http_error(): void
    {
        config(['toshi.mcp_connectors.slack' => [
            'token_url' => 'https://slack.com/api/oauth.v2.access',
            'oauth_client_id' => 'client-id',
            'oauth_secret' => 'client-secret',
        ]]);

        $connector = SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => [
                'access_token' => 'old-token',
                'refresh_token' => 'refresh-token',
            ],
            'token_expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        Http::fake([
            'https://slack.com/api/oauth.v2.access' => Http::response([], 401),
        ]);

        $result = $this->service->refresh($connector);

        $this->assertFalse($result);
    }

    public function test_sweep_expiring_tokens(): void
    {
        config(['toshi.mcp_connectors.slack' => [
            'token_url' => 'https://slack.com/api/oauth.v2.access',
            'oauth_client_id' => 'client-id',
            'oauth_secret' => 'client-secret',
        ]]);

        SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T1',
            'credentials' => [
                'access_token' => 'token1',
                'refresh_token' => 'refresh1',
            ],
            'token_expires_at' => now()->addMinutes(15),
            'status' => 'active',
        ]);

        SchoolMcpConnector::create([
            'school_id' => $this->schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T2',
            'credentials' => [
                'access_token' => 'token2',
                'refresh_token' => 'refresh2',
            ],
            'token_expires_at' => now()->addHours(2),
            'status' => 'active',
        ]);

        Http::fake([
            'https://slack.com/api/oauth.v2.access' => Http::response([
                'access_token' => 'refreshed-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        $refreshed = $this->service->sweepExpiringTokens(30);

        $this->assertSame(1, $refreshed);
    }
}
