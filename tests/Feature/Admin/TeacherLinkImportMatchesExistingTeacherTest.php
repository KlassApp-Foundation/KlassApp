<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\TeacherLinkImportController;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\UserProvisioning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherLinkImportMatchesExistingTeacherTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private Section $section;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Link Match Primary',
            'email' => 'linkmatch@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'link-match-primary',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Link Admin',
            'email' => 'admin@linkmatch.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Link',
            'lastname' => 'Admin',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'status' => '1',
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => 'MTC',
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function runImport(string $data)
    {
        $this->actingAs($this->admin);

        $request = Request::create('/admin/teacher-links/import', 'POST', [
            'data' => $data,
        ]);
        $request->setLaravelSession($this->app['session']->driver());

        return app(TeacherLinkImportController::class)->import($request);
    }

    public function test_import_links_existing_csv_teacher_by_phone_without_duplicate_user(): void
    {
        $schoolId = $this->school->id;
        $provisioning = UserProvisioning::randomPasswordAttributes();
        $teacher = User::create([
            'school_id' => $schoolId,
            'usergroup_id' => 5,
            'name' => 'Sarah Okello',
            'email' => Str::slug('Sarah Okello').'.'.$schoolId.'@school.edu',
            'password' => $provisioning['password'],
            'is_reset' => $provisioning['is_reset'],
            'status' => 'active',
            'email_verified' => 1,
            'mobile_no' => '+256700111222',
        ]);

        Userprofile::create([
            'school_id' => $schoolId,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Sarah Okello',
            'lastname' => '',
        ]);

        $teacher->refresh();
        $this->assertSame('Sarah Okello', $teacher->name);
        $this->assertDoesNotMatchRegularExpression('/\d/', $teacher->name);

        $beforeUsers = User::where('school_id', $schoolId)->where('usergroup_id', 5)->count();

        $response = $this->runImport("Sarah Okello | Mathematics | P.1 | +256700111222\n");

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session()->has('success'), 'Expected success flash; got: '.json_encode(session()->all()));
        $this->assertFalse(session()->has('error'), 'Unexpected error: '.session('error'));

        $this->assertSame(
            $beforeUsers,
            User::where('school_id', $schoolId)->where('usergroup_id', 5)->count(),
            'Must link existing teacher, not insert a duplicate user'
        );
        $this->assertSame(1, Teacherlink::where('school_id', $schoolId)->where('teacher_id', $teacher->id)->count());
    }

    public function test_import_falls_back_to_profile_name_when_phone_missing(): void
    {
        $schoolId = $this->school->id;
        $provisioning = UserProvisioning::randomPasswordAttributes();
        $teacher = User::create([
            'school_id' => $schoolId,
            'usergroup_id' => 5,
            'name' => 'Grace Nakamya',
            'email' => 'grace.'.$schoolId.'@school.edu',
            'password' => $provisioning['password'],
            'is_reset' => $provisioning['is_reset'],
            'status' => 'active',
            'email_verified' => 1,
            'mobile_no' => null,
        ]);

        Userprofile::create([
            'school_id' => $schoolId,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Grace Nakamya',
            'lastname' => '',
        ]);

        $response = $this->runImport("Grace Nakamya | Mathematics | P.1 |\n");

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session()->has('success'));
        $this->assertSame(1, User::where('school_id', $schoolId)->where('usergroup_id', 5)->count());
        $this->assertSame(1, Teacherlink::where('teacher_id', $teacher->id)->count());
    }

    public function test_import_creates_new_teacher_without_digit_suffix_username(): void
    {
        $schoolId = $this->school->id;

        $response = $this->runImport("Amina Nabukeera | Mathematics | P.1 | +256700333444\n");

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session()->has('success'), 'Expected success; got: '.json_encode(session()->all()));

        $teacher = User::where('school_id', $schoolId)->where('usergroup_id', 5)->first();
        $this->assertNotNull($teacher);
        $this->assertSame('Amina Nabukeera', $teacher->name);
        $this->assertDoesNotMatchRegularExpression('/\d/', $teacher->name);
        $this->assertSame(1, Teacherlink::where('teacher_id', $teacher->id)->count());
    }
}
