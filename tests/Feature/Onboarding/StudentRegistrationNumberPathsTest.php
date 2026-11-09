<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\ManualOnboardingWizard;

use App\AiAgents\Tools\AddStudentTool;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures registration_number and klassapp_student_id are both populated
 * on all three student-creation paths, using the shared KLS{school}{seq} format.
 */
class StudentRegistrationNumberPathsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'RegNo Paths School',
            'email' => 'regno-paths@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'regno-paths-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'School Admin',
            'email' => 'admin@regno-paths.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'description' => 'Current Academic Year',
            'type' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        // Seed StandardLink so the engine can create StudentAcademic with class assignment
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => 1,
        ]);

        $year = AcademicYear::where('school_id', $this->school->id)->first();

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
    }

    /**
     * Find a student by profile firstname — users.name is slugified by UserprofileObserver.
     */
    private function findStudentByDisplayName(int $schoolId, string $displayName): ?User
    {
        $profile = Userprofile::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->whereRaw('UPPER(firstname) = ?', [strtoupper($displayName)])
            ->first();

        return $profile ? User::find($profile->user_id) : null;
    }

    private function assertStudentIdsSynced(User $student): string
    {
        $student->refresh();
        $this->assertNotNull($student->registration_number, 'users.registration_number must be set');
        $this->assertTrue(\App\Services\StudentIdGeneratorService::isValid((string) $student->registration_number));

        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic, 'StudentAcademic row must exist');
        $this->assertNotNull($academic->klassapp_student_id, 'klassapp_student_id must be set');
        $this->assertSame(
            $student->registration_number,
            $academic->klassapp_student_id,
            'registration_number and klassapp_student_id must match'
        );

        return $student->registration_number;
    }

    #[Test]
    public function add_student_tool_sets_both_student_id_fields(): void
    {
        $this->actingAs($this->admin);
        ToshiActionService::$bypassConfirm = true;

        try {
            $tool = app(AddStudentTool::class);
            $result = $tool->handle(new \Laravel\Ai\Tools\Request([
                'name' => 'Carol Tool',
                'class' => 'P1',
            ]));

            $this->assertStringStartsWith('✅', $result, "AddStudentTool failed: {$result}");

            $student = $this->findStudentByDisplayName($this->school->id, 'Carol Tool');
            $this->assertNotNull($student, 'AddStudentTool student must exist');
            $this->assertStudentIdsSynced($student);
        } finally {
            ToshiActionService::$bypassConfirm = false;
        }
    }

    #[Test]
    public function student_ids_are_unique_within_school_across_paths(): void
    {
        $this->actingAs($this->admin);

        // Path 1: the wizard's own roster import → OnboardingEngine::saveStudents
        $wizard = Livewire::test(ManualOnboardingWizard::class);
        $wizard->call('runRosterImport', 'students', [[
            'name' => 'Dana Wizard',
            'class' => 'P1',
            'gender' => 'female',
            'school_student_id' => '',
        ]]);

        $fromWizard = $this->findStudentByDisplayName($this->school->id, 'Dana Wizard');
        $this->assertNotNull($fromWizard, 'Wizard student must exist');
        $idWizard = $this->assertStudentIdsSynced($fromWizard);

        // Path 2: AddStudentTool (same school)
        ToshiActionService::$bypassConfirm = true;
        try {
            $tool = app(AddStudentTool::class);
            $result = $tool->handle(new \Laravel\Ai\Tools\Request([
                'name' => 'Eve Tool',
                'class' => 'P1',
            ]));
            $this->assertStringStartsWith('✅', $result, "AddStudentTool failed: {$result}");
        } finally {
            ToshiActionService::$bypassConfirm = false;
        }

        $fromTool = $this->findStudentByDisplayName($this->school->id, 'Eve Tool');
        $this->assertNotNull($fromTool, 'AddStudentTool student must exist');
        $idTool = $this->assertStudentIdsSynced($fromTool);

        $this->assertNotSame($idWizard, $idTool, 'IDs from different paths must be unique within a school');

        $allIds = User::where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->whereNotNull('registration_number')
            ->pluck('registration_number');

        $this->assertSame($allIds->count(), $allIds->unique()->count());
    }
}
