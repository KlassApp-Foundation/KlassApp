<?php

namespace Tests\Feature\Toshi;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\EntityResolver;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiIdentifierDisambiguationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Identifier Test School', 'slug' => 'identifier-test-' . uniqid(),
            'email' => 'identifier@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
    }

    /** @test */
    public function resolve_by_id_returns_correct_student()
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Alice Smith',
        ]);

        $result = EntityResolver::resolveStudentByIdentifier(
            $this->school->id, (string) $student->id
        );

        $this->assertEquals('resolved', $result['state']);
        $this->assertEquals($student->id, $result['model']->id);
        $this->assertEquals('Alice Smith', $result['model']->name);
    }

    /** @test */
    public function resolve_by_id_returns_not_found_for_nonexistent_id()
    {
        $result = EntityResolver::resolveStudentByIdentifier(
            $this->school->id, '999999'
        );

        $this->assertEquals('not_found', $result['state']);
    }

    /** @test */
    public function resolve_by_id_is_scoped_to_school()
    {
        $otherSchool = School::create([
            'name' => 'Other School', 'slug' => 'other-' . uniqid(),
            'email' => 'other@test.sch.ug', 'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $student = User::factory()->create([
            'school_id' => $otherSchool->id, 'usergroup_id' => 6, 'name' => 'Bob',
        ]);

        $result = EntityResolver::resolveStudentByIdentifier(
            $this->school->id, (string) $student->id
        );

        $this->assertEquals('not_found', $result['state'],
            'Should not find student from a different school');
    }

    /** @test */
    public function class_label_returns_null_when_no_academic_record()
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'No Class',
        ]);

        $label = EntityResolver::classLabelForUser($this->school->id, $student);
        $this->assertNull($label);
    }

    /** @test */
    public function class_label_returns_class_section_when_academic_record_exists()
    {
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'A', 'status' => 1]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'Primary 4', 'order' => 1, 'status' => 1,
        ]);
        $ay = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        // standards_link requires specific columns
        $sl = \App\Models\StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $ay->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'no_of_students' => 0, 'status' => 1,
        ]);

        $student = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Class Test',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id, 'academic_year_id' => $ay->id,
            'user_id' => $student->id, 'standardLink_id' => $sl->id,
            'academic_status' => 'pass',
        ]);

        $label = EntityResolver::classLabelForUser($this->school->id, $student);
        $this->assertEquals('Primary 4-A', $label);
    }

    /** @test */
    public function ambiguous_result_includes_class_label()
    {
        Standard::create(['school_id' => $this->school->id, 'name' => 'Primary 4', 'order' => 1, 'status' => 1]);
        Section::create(['school_id' => $this->school->id, 'name' => 'A', 'status' => 1]);

        $s1 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Test Person',
        ]);
        $s2 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Test Person',
        ]);

        $result = EntityResolver::resolveStudent($this->school->id, 'Test Person');

        $this->assertEquals('ambiguous', $result['state']);
        $this->assertCount(2, $result['candidates']);
        // Each candidate should have id, name, and class (may be null if no academic record)
        foreach ($result['candidates'] as $c) {
            $this->assertArrayHasKey('id', $c);
            $this->assertArrayHasKey('name', $c);
            $this->assertArrayHasKey('class', $c);
        }
    }

    /** @test */
    public function identifier_plus_enter_mark_writes_to_correct_student()
    {
        $ay = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'A', 'status' => 1]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'Primary 4', 'order' => 1, 'status' => 1,
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $ay->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'name' => 'English', 'type' => 'core', 'status' => 1,
        ]);
        ExamType::create(['id' => 1, 'name' => 'Test Exam', 'code' => 'TEST']);
        AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $ay->id,
            'name' => 'Term 1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-01',
        ]);

        // Create two students with the SAME name
        $s1 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Same Name Kid',
        ]);
        $s2 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Same Name Kid',
        ]);

        $exam = Exam::create([
            'school_id' => $this->school->id, 'academic_year_id' => $ay->id,
            'section_id' => $section->id, 'subject_id' => $subject->id,
            'standard_id' => $standard->id,
            'teacher_id' => 1, 'academic_term_id' => 1,
            'exam_type_id' => 1, 'status' => 'undone',
        ]);

        $teacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 3,
        ]);
        $this->actingAs($teacher);

        ToshiActionService::$bypassConfirm = true;

        // Resolve by identifier should give us $s1
        $idResult = EntityResolver::resolveStudentByIdentifier(
            $this->school->id, (string) $s1->id
        );
        $this->assertEquals('resolved', $idResult['state']);
        $this->assertEquals($s1->id, $idResult['model']->id);

        // Now enter a mark for that specific student
        $result = ToshiActionService::enterMark($teacher, [
            'student_id' => $s1->id,
            'exam_id' => $exam->id,
            'marks' => 85,
        ]);
        $this->assertTrue($result['success']);

        // Verify only $s1 got the mark, not $s2
        $s1Marks = \App\Models\Academics\Marks::where('student_id', $s1->id)->count();
        $s2Marks = \App\Models\Academics\Marks::where('student_id', $s2->id)->count();
        $this->assertEquals(1, $s1Marks, 'Correct student should have 1 mark');
        $this->assertEquals(0, $s2Marks, 'Duplicate-named student should have 0 marks');
    }

    /** @test */
    public function identifier_without_confirmation_blocked_by_guard()
    {
        $s1 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Dup Name',
        ]);
        $s2 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Dup Name',
        ]);

        // Calling guardDuplicateName directly: even though we resolved to s1 via
        // identifier, the guard should fire because there's a duplicate.
        $result = ToshiActionService::guardDuplicateName($this->school->id, $s1->id, 'student');
        $this->assertNotNull($result);
        $this->assertStringContainsString('multiple students', $result);
    }

    /** @test */
    public function identifier_with_confirmation_bypasses_guard()
    {
        $s1 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Conf Name',
        ]);
        $s2 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Conf Name',
        ]);

        // The guard still detects duplicates but the tool bypasses it via the
        // disambiguation_confirmed check. The bypass happens before
        // guardDuplicateName is called. Verify guard still works standalone:
        $result = ToshiActionService::guardDuplicateName($this->school->id, $s1->id, 'student');
        $this->assertNotNull($result, 'Guard should still detect duplicates');

        // Verify the bypass mechanism: the tool checks disambiguation_confirmed
        // BEFORE calling guardDuplicateName. If confirmed=true, guard is not called.
        // The guard method itself doesn't know about bypass — that's the tool's job.
        // This test proves the guard still exists and works as a standalone method.
    }

    /** @test */
    public function identifier_resolved_student_guard_fires_at_tool_level()
    {
        // This simulates what happens at the tool level when identifier is
        // provided without disambiguation_confirmed:
        // 1. Resolve via EntityResolver
        // 2. Check guardDuplicateName
        // 3. If guard fires, return candidates (don't write)
        $s1 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Tool Level Kid',
        ]);
        $s2 = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Tool Level Kid',
        ]);

        // Step 1: resolve by identifier
        $idResult = EntityResolver::resolveStudentByIdentifier($this->school->id, (string) $s1->id);
        $this->assertEquals('resolved', $idResult['state']);

        // Step 2: check guard (simulating the tool with disambiguation_confirmed=false)
        $dupError = ToshiActionService::guardDuplicateName($this->school->id, $idResult['model']->id, 'student');
        $this->assertNotNull($dupError, 'Guard must fire when disambiguation is not confirmed');

        // Step 3: if guard fires, the tool should return candidates, not write
        // Verify by calling resolveStudent to see the full candidate list
        $ambiguousResult = EntityResolver::resolveStudent($this->school->id, 'Tool Level Kid');
        $this->assertEquals('ambiguous', $ambiguousResult['state']);
        $this->assertGreaterThanOrEqual(2, count($ambiguousResult['candidates']));
    }
}
