<?php

namespace Tests\Feature\Toshi;

use App\Models\SchoolMcpConnector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PR3 Slack wave-1 — OAuth callback upsert (routes/ai.php live-mode handler).
 *
 * The vendor registers mcp/oauth/slack/callback in live mode; our handler
 * upserts the per-school registry row from the authenticated admin + token
 * set. This test exercises the handler closure logic directly (the same
 * updateOrCreate call the callback performs) because the vendor's callback
 * route requires a real OAuth code exchange with mcp.slack.com.
 */
class SlackOAuthCallbackUpsertTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function callback_upserts_active_registry_row_per_school(): void
    {
        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $schoolId = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'OAuth School',
            'slug' => 'oauth-school-'.uniqid(),
            'email' => 'oauth-'.uniqid().'@test.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);

        // Same upsert the routes/ai.php callback handler performs.
        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $admin->school_id,
                'connector_type' => 'slack',
                'external_team_id' => 'T123ABC',
            ],
            [
                'external_team_name' => 'Kampala Parents Workspace',
                'credentials' => [
                    'access_token' => 'xoxb-callback-123',
                    'refresh_token' => 'xoxr-456',
                    'token_type' => 'Bearer',
                    'scope' => 'channels:read channels:history groups:history search:read.public chat:write',
                ],
                'token_expires_at' => now()->addHour(),
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $admin->id,
            ]
        );

        $row = SchoolMcpConnector::query()
            ->forSchool($schoolId)
            ->forType('slack')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('active', $row->status);
        $this->assertSame('deny', $row->write_mode, 'New connections must default to deny (fail closed)');
        $this->assertSame($admin->id, $row->connected_by);
        $this->assertSame('xoxb-callback-123', $row->accessToken());
        $this->assertSame('xoxr-456', $row->refreshToken());
        $this->assertFalse($row->isTokenExpired());

        // Re-connect (same team) updates, not duplicates.
        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $admin->school_id,
                'connector_type' => 'slack',
                'external_team_id' => 'T123ABC',
            ],
            [
                'external_team_name' => 'Kampala Parents Workspace',
                'credentials' => ['access_token' => 'xoxb-newer-789', 'refresh_token' => null, 'token_type' => 'Bearer', 'scope' => null],
                'token_expires_at' => now()->addHour(),
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $admin->id,
            ]
        );

        $this->assertSame(1, SchoolMcpConnector::query()->forSchool($schoolId)->forType('slack')->count());
        $this->assertSame('xoxb-newer-789', $row->refresh()->accessToken());
    }
}
