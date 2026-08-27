<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RenameSchoolStudentIdTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private StandardLink $standardLink;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Rename Test School', 'email' => 'rename@test.sch.ug']);

        $ay = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);

        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'Primary', 'order' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P1']);

        $this->standardLink = StandardLink::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $ay->id,
        ]);
    }

    private function createStudent(): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
        ]);
    }

    /** @test */
    public function student_academics_table_has_school_student_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('student_academics', 'school_student_id'),
            'student_academics should have school_student_id column after migration'
        );
    }

    /** @test */
    public function student_academics_table_no_longer_has_id_card_number_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('student_academics', 'id_card_number'),
            'student_academics should no longer have id_card_number column after migration'
        );
    }

    /** @test */
    public function school_student_id_is_fillable_on_student_academic(): void
    {
        $this->assertContains(
            'school_student_id',
            (new StudentAcademic)->getFillable(),
            'school_student_id should be in StudentAcademic $fillable'
        );
    }

    /** @test */
    public function school_student_id_can_be_set_and_retrieved(): void
    {
        $student = $this->createStudent();

        $sa = StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->standardLink->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $this->standardLink->id,
            'school_student_id' => 'ADM-2025-001',
        ]);

        $this->assertEquals('ADM-2025-001', $sa->fresh()->school_student_id);
    }

    /** @test */
    public function school_student_id_accepts_null(): void
    {
        $student = $this->createStudent();

        $sa = StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->standardLink->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $this->standardLink->id,
            'school_student_id' => null,
        ]);

        $this->assertNull($sa->fresh()->school_student_id);
    }

    /** @test */
    public function klassapp_student_id_still_stores_after_rename(): void
    {
        // Verify the platform ID field still works after the rename —
        // the rename should not break the existing klassapp_student_id column.
        $student = $this->createStudent();

        $sa = StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->standardLink->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $this->standardLink->id,
            'klassapp_student_id' => 'KLS0010001',
        ]);

        $this->assertEquals('KLS0010001', $sa->fresh()->klassapp_student_id);
    }

    /** @test */
    public function admission_user_no_longer_copies_registration_number_to_school_student_id(): void
    {
        // Verify the bug is gone: AdmissionUser::CreateStudent should NOT copy
        // registration_number into school_student_id (formerly id_card_number).
        // The KLS platform ID is already stored in klassapp_student_id and
        // users.registration_number — copying it into school_student_id conflates
        // two different identity systems.
        $source = file(app_path('Traits/AdmissionUser.php'));

        $foundOldBug = false;
        foreach ($source as $line) {
            // Match any assignment of registration_number into school_student_id
            // (or the old column name id_card_number, which should also be gone)
            if (preg_match('/school_student_id\s*=.*registration_number/', $line)
                || preg_match('/id_card_number\s*=.*registration_number/', $line)) {
                $foundOldBug = true;
                break;
            }
        }
        $this->assertFalse($foundOldBug, 'AdmissionUser should not copy registration_number into school_student_id');
    }

    /** @test */
    public function school_student_id_accepts_alphanumeric_values(): void
    {
        // School student IDs are diverse: "P1-A-2025", "ADM/001", etc.
        // No numeric-only constraint should apply (was `numeric` on id_card_number).
        $student = $this->createStudent();

        $sa = StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->standardLink->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $this->standardLink->id,
            'school_student_id' => 'P.1-A-2025',
        ]);

        $this->assertEquals('P.1-A-2025', $sa->fresh()->school_student_id);
    }
}
