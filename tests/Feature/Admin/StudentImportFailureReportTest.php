<?php

namespace Tests\Feature\Admin;

use App\Imports\UsersImport;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentImportFailureReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_row_is_counted_only_when_the_student_is_saved_and_the_failure_is_shown(): void
    {
        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Import Failure School',
            'email' => 'fail-'.Str::random(6).'@t.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Import Failure Admin',
            'email' => 'admin-'.Str::random(6).'@t.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Import',
            'lastname' => 'Admin',
        ]);

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'Primary Two', 'status' => 1]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => AcademicYear::where('school_id', $school->id)->value('id'),
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        Auth::login($admin);

        (new UsersImport)->collection(collect([
            [
                'firstname' => 'Amina',
                'lastname' => 'Okello',
                'email' => 'amina@fail-school.test',
                'class' => 'Primary Two',
                'gender' => 'female',
                'date_of_birth' => '2014-03-02',
            ],
            [
                'firstname' => 'Amina',
                'lastname' => 'Duplicate',
                'email' => 'amina@fail-school.test',
                'class' => 'Primary Two',
                'gender' => 'female',
                'date_of_birth' => '2014-03-02',
            ],
            [
                'firstname' => '',
                'class' => '',
            ],
        ]));

        $this->assertSame(1, User::where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(1, Session::get('insertedcount'));

        $failures = Session::get('import_row_failures');
        $this->assertIsArray($failures);
        $this->assertCount(2, $failures);
        $this->assertStringContainsString('Row 3', $failures[0]);
        $this->assertStringContainsString('Row 4', $failures[1]);
        $this->assertStringContainsString('first name and class are required', $failures[1]);
    }
}
