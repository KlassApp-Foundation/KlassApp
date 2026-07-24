<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillRegistrationNumbersCommandTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Backfill Test School', 'email' => 'backfill@test.sch.ug']);

        // Ensure the current academic year exists
        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);

        DB::table('student_id_sequences')->insert([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);
    }

    private function createStudentAcademic(User $student, ?string $klassappId = 'KLS0010001'): StudentAcademic
    {
        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'Primary', 'order' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'Stream 1']);
        $ay = AcademicYear::where('school_id', $this->school->id)->first();
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $ay->id,
        ]);

        return StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $ay->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'klassapp_student_id' => $klassappId,
        ]);
    }

    public function test_dry_run_reports_counts_without_writing()
    {
        $student = $this->createStudentWithKlassappId();

        $exitCode = Artisan::call('students:backfill-registration-numbers', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Students with klassapp_student_id, no regno:', $output);
        $this->assertStringContainsString('Done.', $output);

        $student->refresh();
        $this->assertNull($student->registration_number);
    }

    public function test_backfills_from_existing_klassapp_student_id()
    {
        $student = $this->createStudentWithKlassappId();

        Artisan::call('students:backfill-registration-numbers');

        $student->refresh();
        $this->assertSame('KLS0010001', $student->registration_number);
    }

    public function test_skips_students_with_existing_registration_number()
    {
        $student = $this->createStudentWithKlassappId();
        $student->registration_number = 'EXISTING001';
        $student->save();

        Artisan::call('students:backfill-registration-numbers');

        $student->refresh();
        $this->assertSame('EXISTING001', $student->registration_number);
    }

    public function test_unmatched_student_with_neither_field()
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);
        Userprofile::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $student->id,
            'usergroup_id' => 6,
        ]);

        Artisan::call('students:backfill-registration-numbers');

        $student->refresh();
        $this->assertNotNull($student->registration_number);
        $this->assertStringStartsWith('KLS', $student->registration_number);
    }

    public function test_unmatched_student_also_gets_klassapp_student_id()
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);
        Userprofile::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $student->id,
            'usergroup_id' => 6,
        ]);
        // Create a StudentAcademic row without klassapp_student_id (matching
        // the real pattern from ToshiActionService::addStudent())
        $this->createStudentAcademic($student, null);

        Artisan::call('students:backfill-registration-numbers');

        $student->refresh();
        $sa = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($sa);
        $this->assertNotNull($sa->klassapp_student_id);
        $this->assertSame($student->registration_number, $sa->klassapp_student_id);
    }

    private function createStudentWithKlassappId(): User
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);
        Userprofile::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $student->id,
            'usergroup_id' => 6,
        ]);
        $this->createStudentAcademic($student);

        return $student;
    }
}