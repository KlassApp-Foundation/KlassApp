<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Resources\UserDetail as UserDetailResource;
use App\Http\Resources\UserSibling as UserSiblingResource;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Profile-page bug sweep:
 *  1. header shows the admission number, never the database id
 *  2. missing date of birth reads "Not recorded", never 01-01-1970
 *  3. a user with no userprofile row never crashes a profile page
 *  4. Delete on student / teacher / parent / staff profiles asks first
 *  5. no stray markup text beside the parent Delete button
 */
class ProfilePagesBugsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([VerifyCsrfToken::class, MustBeSchoolAdmin::class, MustBePrivilege::class]);

        foreach ([3 => 'schooladmin', 4 => 'schoolsubadmin', 5 => 'teacher', 6 => 'student', 7 => 'parent', 8 => 'librarian'] as $id => $name) {
            DB::table('usergroups')->upsert([
                ['id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()],
            ], 'id');
        }

        $this->school = School::create([
            'name' => 'Profile Bugs School',
            'slug' => 'profile-bugs-'.uniqid(),
            'email' => 'profile-bugs-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'name' => 'profile-bugs-admin',
            'email' => 'profile.bugs.admin@t.sch.ug',
            'status' => 'active',
        ]);
    }

    /**
     * Production masks PHP warnings (AppServiceProvider: E_ALL ^ E_NOTICE ^ E_WARNING), so
     * "property of null" reads render blank instead of throwing today. Escalate them for the
     * no-userprofile tests so those reads are proven gone rather than silently tolerated,
     * and so a config change can never turn them into 500s.
     */
    private function escalateAppWarnings(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        set_error_handler(function (int $level, string $message, string $file, int $line) {
            if (! in_array($level, [E_WARNING, E_NOTICE], true)) {
                return false;
            }
            if (str_contains($file, '/vendor/')) {
                return false;
            }
            throw new \ErrorException($message, 0, $level, $file, $line);
        });
        $this->beforeApplicationDestroyed(static fn () => restore_error_handler());
    }

    private function makeUser(int $usergroup, string $name, bool $withProfile = true, array $profile = [], array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'usergroup_id' => $usergroup,
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => $name.'@t.sch.ug',
            'status' => 'active',
        ], $attrs));

        if ($withProfile) {
            Userprofile::create(array_merge([
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'usergroup_id' => $usergroup,
                'firstname' => ucfirst($name),
                'lastname' => 'Tester',
                'status' => 'active',
            ], $profile));
        }

        if ($usergroup === 6) {
            StudentAcademic::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'user_id' => $user->id,
                'academic_status' => 'pass',
            ]);
        }

        return $user;
    }

    private function student(bool $withProfile = true, array $profile = [], array $attrs = []): User
    {
        // Push the DB id far away from the admission number so a leak of either is unambiguous.
        return $this->makeUser(6, 'pupil-'.uniqid(), $withProfile, $profile, array_merge(['registration_number' => 'KLS0004242'], $attrs));
    }

    // 1 ---------------------------------------------------------------------

    public function test_student_header_shows_admission_number_not_database_id(): void
    {
        $student = $this->student();

        $html = $this->actingAs($this->admin)
            ->get('/admin/student/show/'.$student->name)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('KLS0004242', $html);
        $this->assertStringNotContainsString('ID: '.$student->id.'<', str_replace(["\n", '  '], '', $html));
        $this->assertDoesNotMatchRegularExpression('/ID:\s*'.$student->id.'\s*</', $html);
    }

    public function test_student_without_admission_number_says_not_recorded_in_header(): void
    {
        $student = $this->student(true, [], ['registration_number' => null]);

        $html = $this->actingAs($this->admin)
            ->get('/admin/student/show/'.$student->name)
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/Admission No:\s*Not recorded/', $html);
        $this->assertDoesNotMatchRegularExpression('/ID:\s*'.$student->id.'\s*</', $html);
    }

    public function test_teacher_portal_student_header_shows_admission_number(): void
    {
        $student = $this->student();
        $student->load('studentAcademicLatest');

        $html = view('teacher.student.show', ['user' => $student, 'parents' => collect()])->render();

        $this->assertStringContainsString('KLS0004242', $html);
        $this->assertDoesNotMatchRegularExpression('/ID:\s*'.$student->id.'\s*</', $html);
    }

    public function test_teacher_profile_header_never_shows_database_id(): void
    {
        $teacher = $this->makeUser(5, 'teach-'.uniqid());

        $html = $this->actingAs($this->admin)
            ->get('/admin/teacher/show/'.$teacher->name)
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/ID:\s*'.$teacher->id.'\s*</', $html);
    }

    public function test_parent_profile_header_never_shows_database_id(): void
    {
        $parent = $this->makeUser(7, 'guardian-'.uniqid());

        $html = $this->actingAs($this->admin)
            ->get('/admin/parent/show/'.$parent->name)
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/ID:\s*'.$parent->id.'\s*</', $html);
    }

    // 2 ---------------------------------------------------------------------

    public function test_student_with_null_dob_shows_not_recorded(): void
    {
        $student = $this->student(true, ['date_of_birth' => null]);

        $html = $this->actingAs($this->admin)->get('/admin/student/show/'.$student->name)->assertOk()->getContent();

        $this->assertStringContainsString('Not recorded', $html);
        $this->assertStringNotContainsString('01-01-1970', $html);
    }

    public function test_student_with_epoch_dob_shows_not_recorded(): void
    {
        $student = $this->student(true, ['date_of_birth' => '1970-01-01']);

        $html = $this->actingAs($this->admin)->get('/admin/student/show/'.$student->name)->assertOk()->getContent();

        $this->assertStringContainsString('Not recorded', $html);
        $this->assertStringNotContainsString('01-01-1970', $html);
    }

    public function test_student_with_real_dob_still_formats_it(): void
    {
        $student = $this->student(true, ['date_of_birth' => '2014-03-09']);

        $html = $this->actingAs($this->admin)->get('/admin/student/show/'.$student->name)->assertOk()->getContent();

        $this->assertStringContainsString('09-03-2014', $html);
    }

    public function test_teacher_profile_with_null_dob_shows_not_recorded(): void
    {
        $teacher = $this->makeUser(5, 'teach-'.uniqid(), true, ['date_of_birth' => null]);

        $html = $this->actingAs($this->admin)->get('/admin/teacher/show/'.$teacher->name)->assertOk()->getContent();

        $this->assertStringContainsString('Not recorded', $html);
        $this->assertStringNotContainsString('01-01-1970', $html);
    }

    public function test_staff_profile_with_null_dob_shows_not_recorded(): void
    {
        $staff = $this->makeUser(8, 'libra-'.uniqid(), true, ['date_of_birth' => null]);

        $html = $this->actingAs($this->admin)->get('/admin/staff/show/'.$staff->name)->assertOk()->getContent();

        $this->assertStringContainsString('Not recorded', $html);
        $this->assertStringNotContainsString('01-01-1970', $html);
    }

    public function test_teacher_portal_student_view_with_null_dob_shows_not_recorded(): void
    {
        $student = $this->student(true, ['date_of_birth' => null]);
        $student->load('studentAcademicLatest');

        $html = view('teacher.student.show', ['user' => $student, 'parents' => collect()])->render();

        $this->assertStringContainsString('Not recorded', $html);
        $this->assertStringNotContainsString('01-01-1970', $html);
    }

    public function test_user_detail_resource_never_emits_epoch_dob_or_age(): void
    {
        $student = $this->student(true, ['date_of_birth' => '1970-01-01']);
        $student->load(['userprofile', 'studentAcademicLatest']);

        $payload = (new UserDetailResource($student))->toArray(request());

        $this->assertNull($payload['date_of_birth']);
        $this->assertNull($payload['age']);
    }

    public function test_sibling_resource_with_missing_dob_is_null_not_epoch(): void
    {
        $student = $this->student();
        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

        $std = \App\Models\Standard::create(['school_id' => $this->school->id, 'name' => 'P1', 'display_name' => 'P1', 'order' => 1, 'status' => 1]);
        $link = \App\Models\StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $std->id,
            'section_id' => \App\Models\Section::create(['school_id' => $this->school->id, 'name' => 'A', 'status' => 1])->id,
            'status' => 1,
        ]);
        $academic->sibling_details = [[
            'sibling_name' => 'Kid',
            'sibling_relation' => 'brother',
            'sibling_date_of_birth' => '',
            'sibling_standard' => $link->id,
        ]];
        $academic->save();

        $this->actingAs($this->admin);
        $student->refresh()->load('studentAcademicLatest');

        $payload = (new UserSiblingResource($student))->toArray(request());

        $this->assertNull($payload[0]['date_of_birth']);
    }

    // 3 ---------------------------------------------------------------------

    public function test_student_without_userprofile_does_not_crash_profile_page(): void
    {
        $this->escalateAppWarnings();
        $student = $this->student(false);

        $this->actingAs($this->admin)
            ->get('/admin/student/show/'.$student->name)
            ->assertOk()
            ->assertSee('Not recorded');
    }

    public function test_parent_without_userprofile_does_not_crash_profile_page(): void
    {
        $this->escalateAppWarnings();
        $parent = $this->makeUser(7, 'guardian-'.uniqid(), false);

        $this->actingAs($this->admin)
            ->get('/admin/parent/show/'.$parent->name)
            ->assertOk();
    }

    public function test_teacher_without_userprofile_does_not_crash_profile_page(): void
    {
        $this->escalateAppWarnings();
        $teacher = $this->makeUser(5, 'teach-'.uniqid(), false);

        $this->actingAs($this->admin)
            ->get('/admin/teacher/show/'.$teacher->name)
            ->assertOk();
    }

    public function test_staff_without_userprofile_does_not_crash_profile_page(): void
    {
        $this->escalateAppWarnings();
        $staff = $this->makeUser(8, 'libra-'.uniqid(), false);

        $this->actingAs($this->admin)
            ->get('/admin/staff/show/'.$staff->name)
            ->assertOk();
    }

    public function test_teacher_portal_student_view_without_userprofile_does_not_crash(): void
    {
        $this->escalateAppWarnings();
        $student = $this->student(false);
        $student->load('studentAcademicLatest');

        $html = view('teacher.student.show', ['user' => $student, 'parents' => collect()])->render();

        $this->assertStringContainsString('Not recorded', $html);
    }

    public function test_user_detail_resource_without_userprofile_does_not_crash(): void
    {
        $this->escalateAppWarnings();
        $student = $this->student(false);
        $student->load(['userprofile', 'studentAcademicLatest']);

        $payload = (new UserDetailResource($student))->toArray(request());

        $this->assertNull($payload['date_of_birth']);
    }

    /**
     * Every JSON tab the profile pages fetch must survive a user with no userprofile row.
     */
    // 'fees' is omitted on purpose: it needs the optional Fee module (App\Models\Fee) that this app build does not ship.
    public function test_profile_tab_endpoints_survive_user_without_userprofile(): void
    {
        $this->escalateAppWarnings();
        $student = $this->student(false);
        $parent = $this->makeUser(7, 'guardian-'.uniqid(), false);
        $teacher = $this->makeUser(5, 'teach-'.uniqid(), false);

        $urls = [];
        foreach (['details', 'relations', 'siblings', 'activity', 'discipline', 'attendance', 'libraryactivity', 'medicalHistory'] as $tab) {
            $urls[] = "/admin/student/show/{$tab}/{$student->name}";
        }
        foreach (['children', 'activity', 'feedback'] as $tab) {
            $urls[] = "/admin/parent/show/{$tab}/{$parent->name}";
        }
        foreach (['details', 'timetable', 'classes', 'classteacher', 'leave', 'activity', 'logactivity'] as $tab) {
            $urls[] = "/admin/teacher/show/{$tab}/{$teacher->name}";
        }

        $broken = [];
        foreach ($urls as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            if ($response->getStatusCode() >= 500) {
                $broken[] = $response->getStatusCode().' '.$url.' :: '.($response->exception?->getMessage() ?? 'no exception captured');
            }
        }

        $this->assertSame([], $broken, "Tabs that 500 for a profile-less user:\n".implode("\n", $broken));
    }

    // 4 + 5 -----------------------------------------------------------------

    public function test_student_teacher_parent_and_staff_delete_forms_ask_for_confirmation(): void
    {
        $surfaces = [
            'student' => ['/admin/student/show/', $this->student(), '/admin/student/delete/'],
            'teacher' => ['/admin/teacher/show/', $this->makeUser(5, 'teach-'.uniqid()), '/admin/teacher/delete/'],
            'parent' => ['/admin/parent/show/', $this->makeUser(7, 'guardian-'.uniqid()), '/admin/parent/delete/'],
            'staff' => ['/admin/staff/show/', $this->makeUser(8, 'libra-'.uniqid()), '/admin/staff/delete/'],
        ];

        foreach ($surfaces as $label => [$showPath, $user, $deletePath]) {
            $html = $this->actingAs($this->admin)->get($showPath.$user->name)->assertOk()->getContent();

            // The form that posts the DELETE must carry the confirmation hook...
            $this->assertMatchesRegularExpression(
                '#<form[^>]*action="[^"]*'.preg_quote($deletePath, '#').'[^"]*"[^>]*data-confirm-delete="[^"]+"#s',
                $html,
                "{$label} Delete form has no data-confirm-delete hook"
            );
            // ...and the page must ship the script that acts on it.
            $this->assertStringContainsString('form[data-confirm-delete]', $html, "{$label} page lacks the confirm script");
        }
    }

    public function test_parent_page_has_no_stray_markup_text_next_to_delete(): void
    {
        $parent = $this->makeUser(7, 'guardian-'.uniqid());

        $html = $this->actingAs($this->admin)->get('/admin/parent/show/'.$parent->name)->assertOk()->getContent();

        $this->assertStringNotContainsString('x items-center mr-2" id="delete">', $html);
        // Visible text directly after the opening <form ...> tag must be tag/whitespace, never loose words.
        $this->assertDoesNotMatchRegularExpression('#<form[^>]*parent_delete[^>]*>\s*[^<\s]#', $html);
    }
}
