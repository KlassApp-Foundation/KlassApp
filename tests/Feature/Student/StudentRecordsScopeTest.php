<?php

namespace Tests\Feature\Student;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The student marks and attendance pages must only ever expose the authenticated
 * student's own record. These routes take no student id at all, which is deliberate:
 * the parent equivalents do and enforce ownership through StudentParentLink, while a
 * student reading themselves needs no such surface.
 */
class StudentRecordsScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $student;

    private User $otherStudent;

    private Section $section;

    private Standard $standard;

    private int $termId;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Records Scope School', 'slug' => 'rec-scope-'.uniqid(),
            'email' => 'rs-'.uniqid().'@t.sch.ug', 'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => (string) now()->year, 'description' => 'AY',
            'start_date' => now()->subMonths(2)->startOfDay(), 'end_date' => now()->addMonths(6)->endOfDay(), 'status' => 1,
        ]);
        Cache::flush();

        $this->student = User::factory()->create(['usergroup_id' => 6, 'school_id' => $this->school->id, 'status' => 'active', 'email' => 'self.records@t.sch.ug']);
        $this->otherStudent = User::factory()->create(['usergroup_id' => 6, 'school_id' => $this->school->id, 'status' => 'active', 'email' => 'other.records@t.sch.ug']);
        $teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'rec.teacher@t.sch.ug']);

        // exams.academic_term_id and exam_type_id are both NOT NULL
        $this->termId = (int) DB::table('academic_terms')->insertGetId([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'RS Term '.uniqid(), 'status' => 'current',
            'starts_on' => now()->subMonths(2), 'ends_on' => now()->addMonths(2),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'RS P7', 'status' => 1]);

        $subjectOwn = Subject::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'name' => 'OWNSUBJ', 'status' => 1]);
        $subjectOther = Subject::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'name' => 'OTHERSUBJ', 'status' => 1]);

        $this->seedExamWithMark($this->student->id, $subjectOwn->id, $teacher->id, 88.5, 'A');
        $this->seedExamWithMark($this->otherStudent->id, $subjectOther->id, $teacher->id, 43.0, 'D');

        // attendance: self 2 present 1 absent, other 1 present only
        foreach ([[$this->student, 'forenoon', 1, 1], [$this->student, 'afternoon', 1, 2], [$this->student, 'forenoon', 0, 3], [$this->otherStudent, 'forenoon', 1, 1]] as [$u, $session, $status, $ago]) {
            Attendance::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
                'user_id' => $u->id, 'date' => now()->subDays($ago)->format('Y-m-d'),
                'session' => $session, 'status' => $status, 'recorded_by' => $teacher->id,
            ]);
        }
    }

    private function seedExamWithMark(int $studentId, int $subjectId, int $teacherId, float $score, string $grade): void
    {
        $exam = [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'section_id' => $this->section->id,
            'status' => 'done',
            'academic_term_id' => $this->termId,
            'created_at' => now(), 'updated_at' => now(),
        ];
        if (Schema::hasColumn('exams', 'exam_type_id')) {
            // exam_types has no school_id and requires both name and code
            $exam['exam_type_id'] = DB::table('exam_types')->insertGetId([
                'name' => 'RS EOT', 'code' => 'RS-EOT-'.uniqid(),
                'contributes_to_report_total' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $examId = DB::table('exams')->insertGetId($exam);

        DB::table('marks')->insert([
            'student_id' => $studentId, 'teacher_id' => $teacherId, 'school_id' => $this->school->id,
            'subject_id' => $subjectId, 'exam_id' => $examId, 'section_id' => $this->section->id,
            'marks' => $score, 'grade' => $grade, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_student_sees_own_marks_only(): void
    {
        $r = $this->actingAs($this->student)->get('/student/marks');

        $r->assertOk();
        $this->assertStringContainsString('OWNSUBJ', $r->getContent());
        $this->assertStringNotContainsString('OTHERSUBJ', $r->getContent(), 'another student marks must never render');
    }

    public function test_student_sees_own_attendance_only(): void
    {
        $r = $this->actingAs($this->student)->get('/student/attendance');

        $r->assertOk();
        $content = $r->getContent();
        // own rows: 2 present, 1 absent, 3 total. The other student's row must not count.
        $this->assertStringContainsString('>2<', $content);
        $this->assertStringContainsString('>3<', $content);
        $this->assertStringNotContainsString('>4<', $content, 'total must exclude other students rows');
    }

    public function test_crafted_id_in_the_query_string_is_ignored(): void
    {
        $r = $this->actingAs($this->student)->get('/student/marks?student_id='.$this->otherStudent->id.'&user_id='.$this->otherStudent->id);

        $r->assertOk();
        $this->assertStringNotContainsString('OTHERSUBJ', $r->getContent(), 'a crafted student_id must not switch the subject of the page');
        $this->assertStringContainsString('OWNSUBJ', $r->getContent());
    }

    public function test_no_student_id_route_exists_to_abuse(): void
    {
        $this->actingAs($this->student)->get('/student/'.$this->otherStudent->id.'/marks')->assertNotFound();
        $this->actingAs($this->student)->get('/student/'.$this->otherStudent->id.'/attendance')->assertNotFound();
    }

    public function test_student_cannot_reach_the_parent_per_child_routes(): void
    {
        // MustBeParent refuses a student; it redirects rather than aborting, so the
        // assertion is that the page is not served, and no child data leaks with it.
        foreach (['grades', 'attendance'] as $path) {
            $r = $this->actingAs($this->student)->get('/parent/children/'.$this->otherStudent->id.'/'.$path);
            $this->assertContains($r->status(), [302, 403], "student must not be served the parent {$path} page");
            $this->assertStringNotContainsString('OWNSUBJ', (string) $r->getContent());
        }
    }

    public function test_another_schools_data_never_appears(): void
    {
        $other = School::create([
            'name' => 'Records Other School', 'slug' => 'rec-other-'.uniqid(),
            'email' => 'ro-'.uniqid().'@t.sch.ug', 'phone' => '071'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $intruder = User::factory()->create(['usergroup_id' => 6, 'school_id' => $other->id, 'status' => 'active', 'email' => 'intruder.records@t.sch.ug']);

        $r = $this->actingAs($intruder)->get('/student/marks');
        $r->assertOk();
        $this->assertStringNotContainsString('OWNSUBJ', $r->getContent());
        $this->assertStringNotContainsString('OTHERSUBJ', $r->getContent());
    }
}
