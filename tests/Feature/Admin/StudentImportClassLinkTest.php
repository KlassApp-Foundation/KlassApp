<?php

namespace Tests\Feature\Admin;

use App\Imports\UsersImport;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The CSV/XLSX student import must attach every imported student to the class
 * named in the "class" column.
 *
 * Regression (found in the batched staging pass): the o/a-level word lists in
 * UsersImport were capitalized while the lookup value is lowercased, so rows
 * saying "Senior One" (the exact wording shown in the download template for
 * primary) silently produced classless students — created, KLS-minted, but
 * never linked to a standardLink and therefore missing from class rosters.
 */
class StudentImportClassLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private StandardLink $olevelLink;

    private StandardLink $primaryLink;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Import Class Link School',
            'email' => 'import-link.'.Str::random(6).'@t.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Import Link Admin',
            'email' => 'admin.'.Str::random(6).'@t.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Import',
            'lastname' => 'Admin',
        ]);

        $olevel = Standard::create(['school_id' => $this->school->id, 'name' => 'o-level', 'order' => 0]);
        $seniorOne = Section::create(['school_id' => $this->school->id, 'name' => 'Senior One']);
        $this->olevelLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_teacher_id' => $this->admin->id,
            'standard_id' => $olevel->id,
            'section_id' => $seniorOne->id,
        ]);

        $primary = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1]);
        $primaryTwo = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Two']);
        $this->primaryLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_teacher_id' => $this->admin->id,
            'standard_id' => $primary->id,
            'section_id' => $primaryTwo->id,
        ]);

        Auth::login($this->admin);
    }

    private function importRow(string $class): User
    {
        (new UsersImport)->collection(collect([
            [
                'firstname' => 'Imported',
                'lastname' => 'Pupil '.Str::random(4),
                'gender' => 'male',
                'date_of_birth' => '2013-04-12',
                'class' => $class,
                'address' => 'Kampala',
                'region' => 'Central',
                'district' => 'Kampala',
                'country' => 'Uganda',
                'joining_date' => '2026-01-10',
            ],
        ]));

        return User::where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    public function test_senior_one_word_class_is_linked_to_its_standard_link(): void
    {
        $student = $this->importRow('Senior One');

        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

        $this->assertSame(
            $this->olevelLink->id,
            (int) $academic->standardLink_id,
            'A student imported with class "Senior One" must be attached to the Senior One standard link.'
        );
    }

    public function test_short_form_s_class_is_linked(): void
    {
        $student = $this->importRow('S.1');

        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

        $this->assertSame($this->olevelLink->id, (int) $academic->standardLink_id);
    }

    public function test_primary_word_class_is_linked(): void
    {
        $student = $this->importRow('Primary Two');

        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

        $this->assertSame($this->primaryLink->id, (int) $academic->standardLink_id);
    }

    public function test_imported_student_gets_a_kls_id(): void
    {
        $student = $this->importRow('Senior One');

        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

        $this->assertMatchesRegularExpression('/^KLS\d{7}$/i', (string) $academic->klassapp_student_id);
    }
}
