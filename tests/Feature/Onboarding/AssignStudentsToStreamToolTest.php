<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssignStudentsToStreamToolTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private StandardLink $targetLink;
    private User $student1;
    private User $student2;
    private User $student3;
    private int $academicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin'],
            ['id' => 3, 'name' => 'schooladmin'],
            ['id' => 5, 'name' => 'teacher'],
            ['id' => 6, 'name' => 'student'],
        ]);

        $this->school = School::create([
            'name' => 'Test School',
            'email' => 'test@test.sch.ug',
            'phone' => '0700000000',
            'slug' => 'test-school',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'School Admin',
            'email' => 'admin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary 1',
            'status' => 1,
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'description' => 'Current Academic Year',
        ]);
        $this->academicYearId = $academicYear->id;

        // Create an original no-stream StandardLink
        $originalLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => null,
            'no_of_students' => 0,
            'status' => 1,
        ]);

        // Create the target stream link
        $this->targetLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => 'East',
            'no_of_students' => 0,
            'status' => 1,
        ]);

        // Create students assigned to the original no-stream link
        // Seed the ID sequence so StudentIdGeneratorService doesn't collide with
        // the KLS-format registration_numbers set above (KLS0010001, KLS0010002).
        DB::table('student_id_sequences')->insert([
            'school_id' => $this->school->id,
            'next_seq' => 3,
        ]);

        $this->student1 = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'James Odoi',
            'email' => 'james@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
            'registration_number' => 'KLS0010001',
        ]);

        $this->student2 = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Grace Nakato',
            'email' => 'grace@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
            'registration_number' => 'KLS0010002',
        ]);

        $this->student3 = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Peter Wasswa',
            'email' => 'peter@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        // Give them StudentAcademic rows on the original link
        foreach ([$this->student1, $this->student2, $this->student3] as $student) {
            StudentAcademic::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->academicYearId,
                'user_id' => $student->id,
                'standardLink_id' => $originalLink->id,
            ]);
        }

        ToshiActionService::$bypassConfirm = true;
    }

    /** @test */
    public function registration_number_match_updates_standard_link(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['KLS0010001'],
        ]);

        $result = $tool->handle($request);

        $this->assertStringStartsWith('✅', $result);

        $academic = StudentAcademic::where('school_id', $this->school->id)
            ->where('user_id', $this->student1->id)
            ->where('academic_year_id', $this->academicYearId)
            ->first();

        $this->assertEquals($this->targetLink->id, $academic->standardLink_id);
    }

    /** @test */
    public function name_match_unambiguous_updates_standard_link(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['Grace Nakato'],
        ]);

        $result = $tool->handle($request);

        $this->assertStringStartsWith('✅', $result);

        $academic = StudentAcademic::where('school_id', $this->school->id)
            ->where('user_id', $this->student2->id)
            ->where('academic_year_id', $this->academicYearId)
            ->first();

        $this->assertEquals($this->targetLink->id, $academic->standardLink_id);
    }

    /** @test */
    public function unique_constraint_blocks_duplicate_registration_number(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Fake James',
            'email' => 'fake@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
            'registration_number' => 'KLS0010001',
        ]);
    }

    /** @test */
    public function registration_number_not_found_falls_through_to_name_match(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['NONEXISTENT_REG'],
        ]);

        $result = $tool->handle($request);

        $this->assertStringContainsString('Unmatched', $result);
        $this->assertStringStartsWith('❌', $result, 'Non-existent reg number must report as unmatched');
    }

    /** @test */
    public function duplicate_names_reported_ambiguous(): void
    {
        $this->actingAs($this->admin);

        // Create a second student with same name
        User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'James Odoi',
            'email' => 'james2@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['James Odoi'],
        ]);

        $result = $tool->handle($request);

        $this->assertStringContainsString('ambiguous', strtolower($result));
        $this->assertStringStartsWith('❌', $result, 'Must not guess when name is ambiguous');
    }

    /** @test */
    public function no_match_reported_unmatched(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['NonExistent Student'],
        ]);

        $result = $tool->handle($request);

        $this->assertStringContainsString('Unmatched', $result);
        $this->assertStringStartsWith('❌', $result, 'Must report failure when no student matches');
    }

    /** @test */
    public function only_current_academic_year_row_updated_when_student_has_rows_in_two_years(): void
    {
        $this->actingAs($this->admin);

        // Create a second academic year
        $pastYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'type' => 'Previous Academic Year',
        ]);

        // Create a past StandardLink (same section, different year)
        $pastStandard = Standard::where('school_id', $this->school->id)->first();
        $section = Section::where('school_id', $this->school->id)->first();
        $pastLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $pastYear->id,
            'standard_id' => $pastStandard->id,
            'section_id' => $section->id,
            'stream' => null,
            'no_of_students' => 0,
            'status' => 1,
        ]);

        // Give student1 a row in the past year too (on a different link)
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $pastYear->id,
            'user_id' => $this->student1->id,
            'standardLink_id' => $pastLink->id,
        ]);

        // Now move student1 to East in the current year
        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['KLS0010001'],
        ]);

        $result = $tool->handle($request);
        $this->assertStringStartsWith('✅', $result);

        // Current year row should be updated
        $currentAcademic = StudentAcademic::where('school_id', $this->school->id)
            ->where('user_id', $this->student1->id)
            ->where('academic_year_id', $this->academicYearId)
            ->first();
        $this->assertEquals($this->targetLink->id, $currentAcademic->standardLink_id);

        // Past year row should be untouched
        $pastAcademic = StudentAcademic::where('school_id', $this->school->id)
            ->where('user_id', $this->student1->id)
            ->where('academic_year_id', $pastYear->id)
            ->first();
        $this->assertEquals($pastLink->id, $pastAcademic->standardLink_id, 'Past year row must be untouched');
    }

    /** @test */
    public function student_with_no_prior_student_academic_gets_new_row_created(): void
    {
        $this->actingAs($this->admin);

        // Create a student with NO StudentAcademic row
        $newStudent = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'New Kid',
            'email' => 'newkid@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['New Kid'],
        ]);

        $result = $tool->handle($request);
        $this->assertStringStartsWith('✅', $result);

        $academic = StudentAcademic::where('school_id', $this->school->id)
            ->where('user_id', $newStudent->id)
            ->where('academic_year_id', $this->academicYearId)
            ->first();
        $this->assertNotNull($academic, 'Must create StudentAcademic row for student without one');
        $this->assertEquals($this->targetLink->id, $academic->standardLink_id);
    }

    /** @test */
    public function verify_confirms_post_move_state(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['KLS0010001', 'Grace Nakato'],
        ]);

        $tool->handle($request);

        $verifyResult = $tool->verify($request);
        $this->assertTrue($verifyResult['verified']);
    }

    /** @test */
    public function verify_fails_when_student_not_moved(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['KLS0010001'],
        ]);

        // Don't call handle() — no move happened
        $verifyResult = $tool->verify($request);
        $this->assertFalse($verifyResult['verified']);
    }
}
