<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\TeacherProfile;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR0: the teachers list linked Edit/Manage by numeric id while the edit route
 * resolves by exact display name scoped to the school (`TeacherEditController@edit`
 * → `User::findByExactNameInSchool`), so every link 404'd. The list must link by
 * name — the same convention the student and parent lists already use — and the
 * name lookup must stay school-scoped (another school's teacher → 404).
 */
class TeacherEditLinkTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $adminA;

    /** @var list<User> */
    private array $teachersA = [];

    private User $teacherB;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = School::create([
            'name' => 'Teacher Link School A',
            'slug' => 'teacher-link-a-' . uniqid(),
            'email' => 'teacher-link-a-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);
        $this->schoolB = School::create([
            'name' => 'Teacher Link School B',
            'slug' => 'teacher-link-b-' . uniqid(),
            'email' => 'teacher-link-b-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $this->schoolA->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Standard::create([
            'school_id' => $this->schoolA->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'teacher-link-admin-a@t.sch.ug',
        ]);

        foreach (['Grace Nabirye', 'Peter Okello', 'Rita Auma'] as $n => $name) {
            $teacher = User::factory()->create([
                'school_id' => $this->schoolA->id,
                'usergroup_id' => 5,
                'name' => $name,
                'status' => 'active',
                'email' => 'teacher-link-' . $n . '@t.sch.ug',
            ]);
            Userprofile::create([
                'user_id' => $teacher->id,
                'school_id' => $this->schoolA->id,
                'usergroup_id' => 5,
                'firstname' => explode(' ', $name)[0],
                'lastname' => explode(' ', $name)[1],
            ]);
            $this->teachersA[] = $teacher;
        }

        $this->teacherB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'usergroup_id' => 5,
            'name' => 'Zula Ssenyonga',
            'status' => 'active',
            'email' => 'teacher-link-b1@t.sch.ug',
        ]);
        Userprofile::create([
            'user_id' => $this->teacherB->id,
            'school_id' => $this->schoolB->id,
            'usergroup_id' => 5,
            'firstname' => 'Zula',
            'lastname' => 'Ssenyonga',
        ]);
    }

    public function test_profile_validation_route_persists_a_changed_designation(): void
    {
        $teacher = $this->teachersA[0];
        TeacherProfile::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $teacher->id,
            'academic_year_id' => AcademicYear::where('school_id', $this->schoolA->id)->value('id'),
            'employee_id' => 'EMP100',
            'designation' => 'teacher',
            'status' => 1,
        ]);

        $this->actingAs($this->adminA)->postJson('/admin/teacher/edit/validationProfile/'.$teacher->name, [
            'teacher_name' => $teacher->name,
            'firstname' => 'Grace',
            'lastname' => 'Nabirye',
            'gender' => 'female',
            'date_of_birth' => '1990-04-12',
            'joining_date' => '2020-01-15',
            'employee_id' => 'EMP100',
            'designation' => 'principal',
            'job_type' => 'full_time',
        ])->assertOk();

        $this->assertTrue(
            TeacherProfile::query()
                ->where('school_id', $this->schoolA->id)
                ->where('user_id', $teacher->id)
                ->where('designation', 'principal')
                ->where('employee_id', 'EMP100')
                ->exists()
        );
    }

    public function test_edit_shows_profile_fields_and_keeps_a_changed_designation(): void
    {
        $teacher = $this->teachersA[0];
        Userprofile::where('user_id', $teacher->id)->update([
            'date_of_birth' => '1991-04-04',
            'gender' => 'female',
            'joining_date' => '2022-02-07',
        ]);
        TeacherProfile::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $teacher->id,
            'academic_year_id' => AcademicYear::where('school_id', $this->schoolA->id)->value('id'),
            'designation' => 'teacher',
            'employee_id' => 'EMP100',
            'job_type' => 'full_time',
            'status' => 1,
        ]);

        $shown = $this->actingAs($this->adminA)->getJson('/admin/teacher/editTeacher/'.$teacher->name);
        $shown->assertOk();
        $shown->assertJsonPath('date_of_birth', '1991-04-04');
        $shown->assertJsonPath('employee_id', 'EMP100');
        $shown->assertJsonPath('designation', 'teacher');
        $shown->assertJsonPath('gender', 'female');
        $shown->assertJsonPath('joining_date', '2022-02-07');
        $shown->assertJsonPath('job_type', 'full_time');
        $this->assertStringStartsWith('data:image/svg+xml', (string) $shown->json('avatar'));

        $this->actingAs($this->adminA)->postJson('/admin/teacher/edit/validationProfile/'.$teacher->name, [
            'teacher_name' => $teacher->name,
            'firstname' => $teacher->userprofile->firstname,
            'lastname' => $teacher->userprofile->lastname,
            'date_of_birth' => '1991-04-04',
            'gender' => 'female',
            'joining_date' => '2022-02-07',
            'employee_id' => 'EMP100',
            'designation' => 'principal',
            'job_type' => 'full_time',
        ])->assertOk();

        $this->actingAs($this->adminA)
            ->getJson('/admin/teacher/editTeacher/'.$teacher->name)
            ->assertJsonPath('designation', 'principal');
    }

    public function test_teachers_list_links_edit_by_name_not_id(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/teachers');

        $response->assertOk();
        foreach ($this->teachersA as $teacher) {
            $response->assertSee(url('/admin/teacher/edit/' . $teacher->name), false);
            $response->assertDontSee(url('/admin/teacher/edit/' . $teacher->id));
        }
    }

    public function test_admin_can_open_edit_for_every_teacher_by_name(): void
    {
        foreach ($this->teachersA as $teacher) {
            $this->actingAs($this->adminA)
                ->get('/admin/teacher/edit/' . $teacher->name)
                ->assertOk();
        }
    }

    public function test_edit_for_another_schools_teacher_is_a_404(): void
    {
        $this->actingAs($this->adminA)
            ->get('/admin/teacher/edit/' . $this->teacherB->name)
            ->assertNotFound();
    }
}
