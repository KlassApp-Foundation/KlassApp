<?php

namespace Tests\Feature\Toshi;

use App\Models\SchoolMcpConnector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Google Classroom wave-1 — OAuth callback upsert
 * (GoogleClassroomOAuthController::callback).
 *
 * Follows SlackOAuthCallbackUpsertTest pattern. The OAuth controller's
 * callback handler upserts the per-school registry row from the
 * authenticated admin + token response.
 */
class GoogleClassroomOAuthCallbackUpsertTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function callback_upserts_active_registry_row_per_school(): void
    {
        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $schoolId = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'Classroom OAuth School',
            'slug' => 'classroom-oauth-'.uniqid(),
            'email' => 'classroom-'.uniqid().'@test.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);

        // Same upsert the GoogleClassroomOAuthController::callback performs.
        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $admin->school_id,
                'connector_type' => 'google-classroom',
                'external_team_id' => 'google-user:'.$admin->id,
            ],
            [
                'external_team_name' => $admin->name ?? 'Google Classroom',
                'credentials' => [
                    'access_token' => 'ya29.a0AfH6SMC-123',
                    'refresh_token' => '1//0gRefreshToken-456',
                    'token_type' => 'Bearer',
                    'scope' => 'https://www.googleapis.com/auth/classroom.courses.readonly https://www.googleapis.com/auth/classroom.coursework.students.readonly',
                ],
                'token_expires_at' => now()->addSeconds(3599),
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $admin->id,
            ]
        );

        $row = SchoolMcpConnector::query()
            ->forSchool($schoolId)
            ->forType('google-classroom')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('active', $row->status);
        $this->assertSame('deny', $row->write_mode, 'New connections must default to deny (fail closed)');
        $this->assertSame($admin->id, $row->connected_by);
        $this->assertSame('ya29.a0AfH6SMC-123', $row->accessToken());
        $this->assertSame('1//0gRefreshToken-456', $row->refreshToken());
        $this->assertFalse($row->isTokenExpired());

        // Re-connect (same user) updates, not duplicates.
        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $admin->school_id,
                'connector_type' => 'google-classroom',
                'external_team_id' => 'google-user:'.$admin->id,
            ],
            [
                'external_team_name' => $admin->name ?? 'Google Classroom',
                'credentials' => [
                    'access_token' => 'ya29.a0NewerToken-789',
                    'refresh_token' => null,
                    'token_type' => 'Bearer',
                    'scope' => null,
                ],
                'token_expires_at' => now()->addSeconds(3599),
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $admin->id,
            ]
        );

        $this->assertSame(
            1,
            SchoolMcpConnector::query()->forSchool($schoolId)->forType('google-classroom')->count(),
            'Re-connect must not create a duplicate row'
        );
        $this->assertSame('ya29.a0NewerToken-789', $row->refresh()->accessToken());
    }

    #[Test]
    public function write_mode_is_always_deny_regardless_of_input(): void
    {
        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $schoolId = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'Write Mode Test School',
            'slug' => 'write-mode-'.uniqid(),
            'email' => 'writemode-'.uniqid().'@test.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);

        // The OAuth controller ALWAYS sets write_mode = 'deny', regardless of
        // any other logic. Wave-1 has zero write tools — this is defense in depth.
        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $admin->school_id,
                'connector_type' => 'google-classroom',
                'external_team_id' => 'google-user:'.$admin->id,
            ],
            [
                'external_team_name' => $admin->name ?? 'Google Classroom',
                'credentials' => [
                    'access_token' => 'ya29.test-token',
                    'refresh_token' => null,
                    'token_type' => 'Bearer',
                    'scope' => '',
                ],
                'token_expires_at' => now()->addSeconds(3599),
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $admin->id,
            ]
        );

        $row = SchoolMcpConnector::query()
            ->forSchool($schoolId)
            ->forType('google-classroom')
            ->first();

        $this->assertSame('deny', $row->write_mode,
            'write_mode must always be deny — wave-1 has zero write tools and this is defense in depth'
        );
    }

    #[Test]
    public function different_schools_get_separate_connector_rows(): void
    {
        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        // School A
        $schoolA = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'School A',
            'slug' => 'school-a-'.uniqid(),
            'email' => 'schoola-'.uniqid().'@test.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminA = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolA]);

        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $adminA->school_id,
                'connector_type' => 'google-classroom',
                'external_team_id' => 'google-user:'.$adminA->id,
            ],
            [
                'external_team_name' => $adminA->name ?? 'Google Classroom',
                'credentials' => ['access_token' => 'token-a', 'refresh_token' => null, 'token_type' => 'Bearer', 'scope' => ''],
                'token_expires_at' => now()->addSeconds(3599),
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $adminA->id,
            ]
        );

        // School B
        $schoolB = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
            'name' => 'School B',
            'slug' => 'school-b-'.uniqid(),
            'email' => 'schoolb-'.uniqid().'@test.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminB = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolB]);

        SchoolMcpConnector::updateOrCreate(
            [
                'school_id' => $adminB->school_id,
                'connector_type' => 'google-classroom',
                'external_team_id' => 'google-user:'.$adminB->id,
            ],
            [
                'external_team_name' => $adminB->name ?? 'Google Classroom',
                'credentials' => ['access_token' => 'token-b', 'refresh_token' => null, 'token_type' => 'Bearer', 'scope' => ''],
                'token_expires_at' => now()->addSeconds(3599),
                'auth_mode' => 'oauth_remote',
                'status' => 'active',
                'write_mode' => 'deny',
                'connected_by' => $adminB->id,
            ]
        );

        $this->assertSame(1, SchoolMcpConnector::query()->forSchool($schoolA)->forType('google-classroom')->count());
        $this->assertSame(1, SchoolMcpConnector::query()->forSchool($schoolB)->forType('google-classroom')->count());
        $this->assertSame('token-a', SchoolMcpConnector::resolveTokenForRequest('google-classroom', $schoolA));
        $this->assertSame('token-b', SchoolMcpConnector::resolveTokenForRequest('google-classroom', $schoolB));
    }
}
