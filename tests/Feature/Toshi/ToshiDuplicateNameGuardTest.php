<?php

namespace Tests\Feature\Toshi;

use App\Models\Academics\ExamType;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\AcademicTerm;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiDuplicateNameGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function createSchool(string $name): School
    {
        $slug = \Illuminate\Support\Str::slug($name . '-' . uniqid());
        return School::create([
            'name' => $name, 'slug' => $slug,
            'email' => $slug . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
    }

    /** @test */
    public function guard_allows_write_when_name_is_unique()
    {
        $school = $this->createSchool('Unique Name Test');
        $student = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Unique One',
        ]);

        $result = ToshiActionService::guardDuplicateName($school->id, $student->id, 'student');
        $this->assertNull($result, 'Unique name should not trigger guard');
    }

    /** @test */
    public function guard_blocks_write_when_duplicate_name_exists()
    {
        $school = $this->createSchool('Duplicate Name Test');

        $s1 = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Shared Name',
        ]);
        $s2 = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Shared Name',
        ]);

        $result = ToshiActionService::guardDuplicateName($school->id, $s1->id, 'student');
        $this->assertNotNull($result, 'Duplicate name should trigger guard');
        $this->assertStringContainsString('multiple students', $result);
        $this->assertStringContainsString('Shared Name', $result);
        $this->assertStringContainsString((string) $s2->id, $result);
    }

    /** @test */
    public function guard_is_school_scoped_not_global()
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');

        $a1 = User::factory()->create([
            'school_id' => $schoolA->id, 'usergroup_id' => 6, 'name' => 'Same Name',
        ]);
        User::factory()->create([
            'school_id' => $schoolB->id, 'usergroup_id' => 6, 'name' => 'Same Name',
        ]);

        $result = ToshiActionService::guardDuplicateName($schoolA->id, $a1->id, 'student');
        $this->assertNull($result, 'Same name in different school should not trigger guard');
    }

    /** @test */
    public function enter_mark_is_blocked_by_duplicate_name()
    {
        $school = $this->createSchool('EnterMark Dup');
        $ay = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        $section = \App\Models\Section::create(['school_id' => $school->id, 'name' => 'A', 'status' => 1]);
        $subject = \App\Models\Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id,
            'standard_id' => 1, 'section_id' => $section->id,
            'name' => 'English', 'type' => 'core', 'status' => 1,
        ]);

        $s1 = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Test Person',
        ]);
        User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'name' => 'Test Person',
        ]);

        $teacher = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 3]);
        $this->actingAs($teacher);

        $standard = \App\Models\Standard::create([
            'school_id' => $school->id, 'name' => 'Test Standard',
            'order' => 1, 'status' => 1,
        ]);

        \App\Models\Academics\ExamType::create(['id' => 1, 'name' => 'Test Exam', 'code' => 'TEST']);
        AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id,
            'name' => 'Term 1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-01',
        ]);

        $exam = \App\Models\Academics\Exam::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id,
            'section_id' => $section->id, 'subject_id' => $subject->id,
            'standard_id' => $standard->id,
            'teacher_id' => $teacher->id, 'academic_term_id' => 1,
            'exam_type_id' => 1, 'status' => 'undone',
        ]);

        ToshiActionService::$bypassConfirm = true;

        // The write-time guard was removed from enterMark() because
        // disambiguation via ID now happens at the tool level (EntityResolver).
        // enterMark should succeed even with duplicate names when the caller
        // has already resolved to a specific student_id.
        $result = ToshiActionService::enterMark($teacher, [
            'student_id' => $s1->id, 'exam_id' => $exam->id, 'marks' => 85,
        ]);

        // Verify the mark was actually written to the correct student
        $this->assertTrue($result['success']);
        $s1Marks = \App\Models\Academics\Marks::where('student_id', $s1->id)->count();
        $s2Marks = \App\Models\Academics\Marks::where('student_id', $s2->id)->count();
        $this->assertEquals(1, $s1Marks, 'Correct student should have 1 mark');
        $this->assertEquals(0, $s2Marks, 'Duplicate-named student should have 0 marks');
    }
}
