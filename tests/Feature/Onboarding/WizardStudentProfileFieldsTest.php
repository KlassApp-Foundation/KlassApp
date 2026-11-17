<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * T-128: the gender + school_student_id coverage that lived on the retired Toshi
 * commitAll path, moved to the wizard's own roster import — the path real schools use.
 */
class WizardStudentProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Profile Fields School',
            'email' => 'profiles@test.sch.ug',
            'phone' => '0700777001',
            'slug' => 'profile-fields-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'student_size' => 'Up to 500',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Profile Admin',
            'email' => 'profile-admin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Profile',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->seedClassStructure('P.7');
    }

    #[Test]
    public function the_wizard_roster_import_keeps_gender_and_the_two_id_fields_apart(): void
    {
        $this->actingAs($this->admin);

        $result = Livewire::test(ManualOnboardingWizard::class)
            ->call('runRosterImport', 'students', [[
                'name' => 'Ada Lovelace',
                'class' => 'P.7',
                'gender' => 'female',
                'school_student_id' => 'ADM-P7-001',
                'board_registration_number' => 'U1234/567',
            ]])
            ->assertSet('errorMessage', '');

        $student = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->where('name', 'Ada Lovelace')
            ->first();

        $this->assertNotNull($student, 'The wizard must create the student');

        $profile = Userprofile::where('user_id', $student->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('female', $profile->gender, 'Gender must survive the wizard import');

        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertSame('ADM-P7-001', $academic->school_student_id);
        $this->assertSame('U1234/567', $academic->board_registration_number);
        $this->assertNotSame(
            $academic->school_student_id,
            $academic->board_registration_number,
            'The school ID and the board registration number are different fields'
        );
    }

    private function seedClassStructure(string $sectionName): void
    {
        $year = AcademicYear::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => date('Y')],
            [
                'description' => 'Current Academic Year',
                'type' => 'Current Academic Year',
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'status' => 1,
            ]
        );

        $standard = Standard::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'primary'],
            ['order' => 1, 'status' => '1']
        );

        $section = Section::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => $sectionName],
            ['status' => '1']
        );

        StandardLink::firstOrCreate([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
        ]);
    }
}
