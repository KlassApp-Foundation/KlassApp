<?php

namespace Tests\Feature\Students;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\StudentIdGeneratorService;
use App\Traits\AdmissionUser;
use App\Traits\RegisterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every student-creation path must issue a KLS number at creation:
 * users.registration_number and student_academics.klassapp_student_id,
 * always the same value, and never reassigned afterwards.
 *
 * Paths covered here:
 *   - RegisterUser::CreateUser (Excel/CSV import + admin Add Student share it)
 *   - AdmissionUser::CreateStudent (admission approval)
 *   - OnboardingEngine::saveStudents (wizard one-by-one, wizard paste, Toshi)
 *   - StudentAcademicTableSeeder, Phase4RosterDemoSeeder, DemoAcademySeeder
 *   - StudentIdGeneratorService::ensureForStudent (stream tool reuse semantics)
 */
class StudentCreationAssignsKlsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StandardLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'KLS Creation School', 'slug' => 'kls-creation-'.uniqid(), 'email' => uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999), 'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026', 'description' => 'y', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P4', 'status' => 1]);
        $this->link = StandardLink::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1]);
    }

    private function assertStudentCarriesKls(User $student): string
    {
        $student->refresh();
        $this->assertMatchesRegularExpression('/^KLS\d{7}$/i', (string) $student->registration_number, 'users.registration_number must be a KLS number');
        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();
        $this->assertSame($student->registration_number, $academic->klassapp_student_id, 'user and academic must hold the same KLS number');

        return (string) $student->registration_number;
    }

    private function createUserData(array $overrides = []): object
    {
        return (object) array_merge([
            'name' => 'Path Pupil', 'firstname' => 'Path', 'lastname' => 'Pupil',
            'email' => 'path.pupil.'.uniqid().'@example.test', 'mobile_no' => null, 'gender' => 'male',
            'date_of_birth' => null, 'blood_group' => null, 'address' => null, 'city_id' => null,
            'country_id' => null, 'pincode' => null, 'birth_place' => null, 'native_place' => null,
            'caste' => null, 'sub_caste' => null, 'aadhar_number' => null, 'joining_date' => '2026-02-01',
            'notes' => null, 'standard' => $this->link->id, 'std_school_pay_number' => null, 'lin' => null,
            'school_student_id' => null, 'board_registration_number' => null, 'mode_of_transport' => null,
            'siblings' => 'no', 'siblings_count' => 0, 'registration_number' => '',
        ], $overrides);
    }

    /** Import and admin Add Student both create through RegisterUser::CreateUser. */
    public function test_create_user_path_assigns_kls_for_import_and_admin_add(): void
    {
        $controller = new class
        {
            use RegisterUser;
        };

        // Blank admission number (import) or empty form field (admin add): mint a KLS number.
        $student = $controller->CreateUser($this->createUserData(), $this->school->id, $this->year->id, '', 6);
        $this->assertNotNull($student);
        $this->assertStudentCarriesKls($student);

        // A valid KLS number provided in the file is kept as is.
        $provided = sprintf('KLS%07d', $this->school->id * 10000 + 77);
        $kept = $controller->CreateUser($this->createUserData(['registration_number' => $provided]), $this->school->id, $this->year->id, '', 6);
        $this->assertSame($provided, $kept->registration_number);
        $this->assertStudentCarriesKls($kept);

        // A non-KLS admission number from an old file is kept as the school's own id,
        // while the KLS number is minted by the generator.
        $other = $controller->CreateUser($this->createUserData(['registration_number' => 'ADM-123']), $this->school->id, $this->year->id, '', 6);
        $this->assertStudentCarriesKls($other);
        $this->assertSame('ADM-123', StudentAcademic::where('user_id', $other->id)->value('school_student_id'));
    }

    /** Admission approval (AdmissionUser::CreateStudent). */
    public function test_admission_approval_assigns_kls(): void
    {
        $controller = new class
        {
            use AdmissionUser;
        };

        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->school->id]);
        $fee = (object) ['id' => 999, 'amount' => 0];

        $data = (object) [
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Admitted Pupil', 'lastname' => 'Pupil',
            'gender' => 'male', 'date_of_birth' => null, 'blood_group' => null, 'permanent_address' => null,
            'birth_place' => null, 'native_place' => null, 'mother_tongue' => null, 'community' => null,
            'sub_caste' => null, 'aadhar_number' => null, 'joining_date' => '2026-02-01', 'notes' => null,
            'lin' => null, 'board_registration_number' => null, 'mode_of_transport' => null,
            'siblings' => 'no', 'siblings_count' => 0, 'height' => null, 'weight' => null,
            'medical_details' => null, 'payment_status' => 'unpaid',
        ];

        $student = $controller->CreateStudent($data, 6, $this->link->id, '', $fee, $admin);

        $this->assertNotNull($student);
        $this->assertStudentCarriesKls($student);
    }

    /** Wizard one-by-one, wizard paste and Toshi all create through OnboardingEngine::saveStudents. */
    public function test_onboarding_engine_assigns_kls(): void
    {
        $result = app(OnboardingEngine::class)->saveStudents($this->school, $this->year, [[
            'name' => 'Engine Pupil', 'email' => 'engine.pupil.'.uniqid().'@example.test', 'class' => 'P4',
        ]]);

        $this->assertCount(1, $result['created']);
        $student = User::findOrFail($result['created'][0]['user_id']);
        $this->assertStudentCarriesKls($student);
    }

    /** The legacy academic seeder must sync both stores for students that lack an id. */
    public function test_student_academic_table_seeder_assigns_kls(): void
    {
        $student = User::factory()->create(['usergroup_id' => 6, 'school_id' => $this->school->id, 'name' => 'Legacy Pupil']);
        Userprofile::create(['school_id' => $this->school->id, 'user_id' => $student->id, 'usergroup_id' => 6, 'firstname' => 'Legacy', 'lastname' => 'Pupil', 'status' => 'active']);

        $this->seed(\Database\Seeders\StudentAcademicTableSeeder::class);

        $this->assertStudentCarriesKls($student);
    }

    /** Test-fixture demo seeder assigns numbers too. */
    public function test_phase4_roster_seeder_assigns_kls(): void
    {
        $this->seed(\Database\Seeders\Phase4RosterDemoSeeder::class);

        $school = School::where('email', 'phase4-roster-demo@klassapp.xyz')->firstOrFail();
        $students = User::where('school_id', $school->id)->where('usergroup_id', 6)->get();
        $this->assertGreaterThan(0, $students->count());
        foreach ($students as $student) {
            $this->assertStudentCarriesKls($student);
        }
    }

    /** Demo Academy seeder assigns numbers and a re-run never changes them. */
    public function test_demo_academy_seeder_assigns_kls_and_never_reassigns(): void
    {
        $this->seed(\Database\Seeders\DemoAcademySeeder::class);

        $school = School::where('email', 'demo-academy@klassapp.xyz')->firstOrFail();
        $students = User::where('school_id', $school->id)->where('usergroup_id', 6)->get();
        $this->assertGreaterThan(0, $students->count());
        foreach ($students as $student) {
            $this->assertStudentCarriesKls($student);
        }

        $before = $students->pluck('registration_number', 'id')->all();
        $this->seed(\Database\Seeders\DemoAcademySeeder::class);
        $after = User::whereIn('id', array_keys($before))->pluck('registration_number', 'id')->all();
        $this->assertSame($before, $after, 'a re-run must never reassign an existing KLS number');
    }

    /** ensureForStudent reuses an existing number and only mints one when missing (stream tool semantics). */
    public function test_ensure_for_student_never_reassigns(): void
    {
        $student = User::factory()->create(['usergroup_id' => 6, 'school_id' => $this->school->id, 'name' => 'Reuse Pupil']);

        $first = StudentIdGeneratorService::ensureForStudent($student);
        $this->assertSame($first, StudentIdGeneratorService::ensureForStudent($student->refresh()));

        $next = StudentIdGeneratorService::next($this->school->id);
        $this->assertNotSame($first, $next, 'minted numbers must not collide');
    }
}
