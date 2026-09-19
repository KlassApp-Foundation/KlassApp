<?php

namespace Tests\Feature\Toshi;

use App\Models\SchoolMcpConnector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PR3 Slack wave-1 — Integrations settings page (connect UI).
 *
 * Covers: full-school-admin-only access (subadmin/teacher blocked), connected
 * vs not-connected states, disconnect flips status to disabled (never delete),
 * per-school isolation (school B cannot see or disconnect school A's row),
 * and the OAuth callback upsert path (routes/ai.php live-mode handler).
 */
class IntegrationsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolAId;

    private int $schoolBId;

    private User $adminA;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.mcp_connectors.slack.enabled' => true,
            'services.slack_mcp.mode' => 'live',
            'services.slack_mcp.client_id' => 'test-client-id-123',
            'services.slack_mcp.client_secret' => 'test-secret',
        ]);

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolAId = $this->createSchool('A');
        $this->schoolBId = $this->createSchool('B');

        $this->adminA = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->schoolAId]);
        $this->adminB = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->schoolBId]);
    }

    #[Test]
    public function school_admin_sees_not_connected_state_and_connect_link(): void
    {
        $this->actingAs($this->adminA)
            ->get('/admin/settings/integrations')
            ->assertOk()
            ->assertSee('Integrations')
            ->assertSee('Not connected')
            ->assertSee('mcp/slack/connect');
    }

    #[Test]
    public function connected_school_sees_workspace_details_and_disconnect(): void
    {
        $this->createConnector($this->schoolAId, $this->adminA->id, 'TAMS Team');

        $this->actingAs($this->adminA)
            ->get('/admin/settings/integrations')
            ->assertOk()
            ->assertSee('Connected')
            ->assertSee('TAMS Team')
            ->assertSee('Disconnect');
    }

    #[Test]
    public function subadmin_and_teacher_are_blocked(): void
    {
        $subadmin = User::factory()->create(['usergroup_id' => 4, 'school_id' => $this->schoolAId]);
        $teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->schoolAId]);

        $this->actingAs($subadmin)->get('/admin/settings/integrations')->assertNotFound();
        $this->actingAs($teacher)->get('/admin/settings/integrations')->assertNotFound();
    }

    #[Test]
    public function disconnect_disables_never_deletes(): void
    {
        $connector = $this->createConnector($this->schoolAId, $this->adminA->id, 'TAMS Team');

        $this->actingAs($this->adminA)
            ->post("/admin/settings/integrations/slack/disconnect")
            ->assertRedirect();

        $connector->refresh();
        $this->assertSame('disabled', $connector->status, 'Disconnect must flag inactive, not delete (standing rule #3)');
        $this->assertDatabaseHas('school_mcp_connectors', ['id' => $connector->id]);
    }

    #[Test]
    public function school_b_cannot_disconnect_school_a_connector(): void
    {
        $connector = $this->createConnector($this->schoolAId, $this->adminA->id, 'A Workspace');

        $this->actingAs($this->adminB)
            ->post("/admin/settings/integrations/slack/disconnect")
            ->assertRedirect();

        $connector->refresh();
        $this->assertSame('active', $connector->status, 'Cross-school disconnect must be a no-op (rule #13)');
    }

    #[Test]
    public function school_b_does_not_see_school_a_workspace_on_the_page(): void
    {
        $this->createConnector($this->schoolAId, $this->adminA->id, 'A Workspace');

        $this->actingAs($this->adminB)
            ->get('/admin/settings/integrations')
            ->assertOk()
            ->assertDontSee('A Workspace')
            ->assertSee('Not connected');
    }

    private function createSchool(string $suffix): int
    {
        return \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
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

    private function createConnector(int $schoolId, int $userId, string $teamName): SchoolMcpConnector
    {
        return SchoolMcpConnector::create([
            'school_id' => $schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T'.uniqid(),
            'external_team_name' => $teamName,
            'credentials' => [
                'access_token' => 'xoxb-test-'.uniqid(),
                'refresh_token' => null,
                'token_type' => 'Bearer',
                'scope' => 'mcp:read',
            ],
            'token_expires_at' => null,
            'auth_mode' => 'oauth_remote',
            'status' => 'active',
            'write_mode' => 'deny',
            'connected_by' => $userId,
        ]);
    }
}
