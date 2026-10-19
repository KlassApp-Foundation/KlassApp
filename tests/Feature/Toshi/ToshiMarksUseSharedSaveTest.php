<?php

namespace Tests\Feature\Toshi;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\User;
use App\Services\OutboundWhatsAppService;
use App\Services\Toshi\TeacherActionService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher and admin Toshi mark entry use the same save as the teacher form:
 * a scale grade, a locked submission refused, and a student from this school only.
 */
class ToshiMarksUseSharedSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_admin_entry_refuses_another_schools_student_and_derives_the_grade(): void
    {
        $ctx = $this->school('Admin Save');
        $other = $this->person($this->otherSchool(), 6, 'Other Student');

        $refused = ToshiActionService::enterMark($ctx['admin'], [
            'student_id' => $other->id,
            'exam_id' => $ctx['exam']->id,
            'marks' => 80,
        ]);

        $this->assertFalse($refused['success']);
        $this->assertSame(0, Marks::count());

        $saved = ToshiActionService::enterMark($ctx['admin'], [
            'student_id' => $ctx['student']->id,
            'exam_id' => $ctx['exam']->id,
            'marks' => 80,
        ]);

        $this->assertTrue($saved['success']);
        $mark = Marks::where('student_id', $ctx['student']->id)->where('exam_id', $ctx['exam']->id)->first();
        $this->assertNotNull($mark);
        $this->assertSame('A', $mark->grade);
        $this->assertSame((int) $ctx['school']->id, (int) $mark->school_id);
    }

    public function test_teacher_entry_derives_the_grade_and_a_lock_refuses_the_edit(): void
    {
        $ctx = $this->school('Teacher Save');

        $saved = TeacherActionService::enterMarks($ctx['teacher'], [
            'exam_id' => $ctx['exam']->id,
            'student' => $ctx['student']->name,
            'marks' => 85,
        ]);

        $this->assertTrue($saved['success']);
        $mark = Marks::where('student_id', $ctx['student']->id)->first();
        $this->assertSame('A', $mark->grade);

        ExamMarksSubmission::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $ctx['exam']->id,
            'class_id' => $ctx['section']->id,
            'subject_id' => $ctx['subject']->id,
            'teacher_id' => $ctx['teacher']->id,
            'status' => 'locked',
        ]);

        $locked = TeacherActionService::enterMarks($ctx['teacher'], [
            'exam_id' => $ctx['exam']->id,
            'student' => $ctx['student']->name,
            'marks' => 10,
        ]);

        $this->assertFalse($locked['success']);
        $this->assertSame(85, (int) Marks::where('student_id', $ctx['student']->id)->value('marks'));
    }

    public function test_a_submitted_exam_needs_a_correction_reason_and_does_not_notify_parents(): void
    {
        $ctx = $this->school('Submitted Save');
        $ctx['exam']->update(['status' => 'submitted']);

        $outbound = \Mockery::mock(OutboundWhatsAppService::class);
        $outbound->shouldNotReceive('notifyComprehensiveGrades');
        $this->app->instance(OutboundWhatsAppService::class, $outbound);

        $blocked = ToshiActionService::enterMark($ctx['admin'], [
            'student_id' => $ctx['student']->id,
            'exam_id' => $ctx['exam']->id,
            'marks' => 90,
        ]);
        $this->assertFalse($blocked['success']);
        $this->assertSame(0, Marks::count());

        $saved = ToshiActionService::enterMark($ctx['admin'], [
            'student_id' => $ctx['student']->id,
            'exam_id' => $ctx['exam']->id,
            'marks' => 90,
            'correction_reason' => 'The script total was misread.',
        ]);
        $this->assertTrue($saved['success']);
        $this->assertSame('A', Marks::where('student_id', $ctx['student']->id)->value('grade'));
    }

    /**
     * @return array{school: \App\Models\School, admin: User, teacher: User, student: User, exam: Exam, section: \App\Models\Section, subject: \App\Models\Subject}
     */
    private function school(string $name): array
    {
        $school = \App\Models\School::create([
            'name' => $name,
            'slug' => 'toshi-save-'.uniqid(),
            'email' => uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
        $standard = \App\Models\Standard::create([
            'school_id' => $school->id, 'name' => 'P.4', 'order' => 1, 'status' => 1,
        ]);
        $section = \App\Models\Section::create([
            'school_id' => $school->id, 'name' => 'P.4', 'status' => 1,
        ]);
        $year = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = \App\Models\AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Term 1', 'status' => 'current',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
        ]);
        $subject = \App\Models\Subject::create([
            'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'name' => 'Mathematics', 'type' => 'core', 'status' => 1,
        ]);
        $type = ExamType::firstOrCreate(['id' => 1], ['name' => 'EOT', 'code' => 'EOT']);
        $admin = $this->person($school, 3, 'Admin '.$name);
        $teacher = $this->person($school, 5, 'Teacher '.$name);
        $student = $this->person($school, 6, 'Ada '.$name);
        $exam = Exam::create([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'exam_type_id' => $type->id,
            'teacher_id' => $teacher->id,
            'status' => 'undone',
        ]);
        SchoolGradingSystem::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'grade' => 'A',
            'points' => 1,
            'min_score' => 80,
            'max_score' => 100,
            'remark' => 'A',
        ]);

        return compact('school', 'admin', 'teacher', 'student', 'exam', 'section', 'subject');
    }

    private function otherSchool(): \App\Models\School
    {
        return \App\Models\School::create([
            'name' => 'Other School',
            'slug' => 'other-'.uniqid(),
            'email' => uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
    }

    private function person(\App\Models\School $school, int $group, string $name): User
    {
        $id = DB::table('users')->insertGetId([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => uniqid().'@t.sch.ug',
            'password' => bcrypt('pw'),
            'status' => 'active',
            'email_verified' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::find($id);
    }
}
