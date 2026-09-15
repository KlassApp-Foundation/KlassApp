<?php

namespace Tests\Feature\Nightwatch;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Resources\Teacher as TeacherResource;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Traits\Common;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherFindNullAvatarTest extends TestCase
{
    use RefreshDatabase;
    use Common;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Null Avatar School',
            'email' => 'null-avatar@test.sch.ug',
            'phone' => '+256700000300',
            'slug' => 'null-avatar-'.uniqid(),
            'status' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'email' => 'admin.nullavatar@test.sch.ug',
        ]);
    }

    public function test_get_file_path_returns_empty_string_for_null_without_type_error(): void
    {
        $this->assertSame('', $this->getFilePath(null));
        $this->assertSame('', $this->getFilePath(''));
    }

    public function test_teacher_resource_null_avatar_does_not_throw(): void
    {
        $teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'email' => 'teacher.nullavatar@test.sch.ug',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'No',
            'lastname' => 'Avatar',
            'avatar' => null,
            'status' => 'active',
        ]);

        $payload = (new TeacherResource($teacher->fresh(['userprofile'])))
            ->toArray(Request::create('/'));

        $this->assertNull($payload['avatar']);
    }

    public function test_admin_teachers_find_returns_json_when_teacher_has_null_avatar(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        $teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'email' => 'teacher.find.null@test.sch.ug',
            'name' => 'null.avatar.teacher',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Null',
            'lastname' => 'Avatar',
            'avatar' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/admin/teachers/find');

        $response->assertOk();
        $payload = $response->json();
        $teachers = collect(is_array($payload) && array_is_list($payload) ? $payload : ($payload['data'] ?? $payload));
        $this->assertFalse($teachers->isEmpty(), 'teachers/find returned empty: '.$response->getContent());

        $match = $teachers->first(fn ($row) => (int) ($row['id'] ?? 0) === (int) $teacher->id);
        $this->assertNotNull($match, 'teacher missing from find payload: '.$response->getContent());
        $this->assertTrue(($match['avatar'] ?? null) === null || $match['avatar'] === '');
    }
}
