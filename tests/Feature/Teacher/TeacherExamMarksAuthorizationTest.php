<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherExamMarksAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $ownerTeacher;

    private User $otherTeacher;

    private User $student;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $section;

    private Standard $standard;

    private Subject $subject;

    private ExamType $examType;

    private Exam $ownedExam;

    private Exam $otherExam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Marks Auth School',
            'slug' => 'marks-auth-' . uniqid(),
            'email' => 'marks-auth-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->ownerTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Owner Teacher',
            'email' => 'owner.marks@t.sch.ug',
        ]);

        $this->otherTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Other Teacher',
            'email' => 'other.marks@t.sch.ug',
        ]);

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Amina Nakato',
            'email' => 'amina.marks@t.sch.ug',
        ]);

        Userprofile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Amina',
            'lastname' => 'Nakato',
            'status' => 'active',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        // Bukoto-style term name ("Term I") previously crashed the view match().
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-01',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => 1,
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->examType = ExamType::create([
            'name' => 'Mid Term',
            'code' => 'MID',
            'contributes_to_report_total' => true,
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'D1',
            'points' => 1,
            'min_score' => 85,
            'max_score' => 100,
            'remark' => 'Excellent',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'C4',
            'points' => 4,
            'min_score' => 70,
            'max_score' => 74,
            'remark' => 'Fair',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'C3',
            'points' => 3,
            'min_score' => 75,
            'max_score' => 79,
            'remark' => 'Good',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'D2',
            'points' => 2,
            'min_score' => 80,
            'max_score' => 84,
            'remark' => 'V.Good',
        ]);

        $this->ownedExam = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->ownerTeacher->id,
            'exam_type_id' => $this->examType->id,
            'status' => 'done',
        ]);

        $this->otherExam = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->otherTeacher->id,
            'exam_type_id' => $this->examType->id,
            'status' => 'done',
        ]);

        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'exam_id' => $this->ownedExam->id,
            'teacher_id' => $this->ownerTeacher->id,
            'school_id' => $this->school->id,
            'marks' => 93,
            'grade' => 'D1',
        ]);

        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'exam_id' => $this->otherExam->id,
            'teacher_id' => $this->otherTeacher->id,
            'school_id' => $this->school->id,
            'marks' => 70,
            'grade' => 'C4',
        ]);
    }

    public function test_assigned_teacher_can_view_entered_marks(): void
    {
        $response = $this->actingAs($this->ownerTeacher)
            ->get(route('teacher.exam.marks.view', $this->ownedExam));

        $response->assertOk();
        $response->assertSee('AMINA');
        $response->assertSee('NAKATO');
        $response->assertSee('93');
        $response->assertSee('D1');
        $response->assertSee('Term I');
        $response->assertSee('MATHEMATICS');
        $response->assertSee('Owner Teacher');
    }

    public function test_assigned_teacher_can_view_a_second_exam_with_marks(): void
    {
        $response = $this->actingAs($this->otherTeacher)
            ->get(route('teacher.exam.marks.view', $this->otherExam));

        $response->assertOk();
        $response->assertSee('70');
        $response->assertSee('C4');
        $response->assertSee('Other Teacher');
    }

    public function test_teacher_cannot_save_marks_for_another_teachers_exam(): void
    {
        $response = $this->actingAs($this->otherTeacher)
            ->post(route('teacher.exam.marks.save', $this->ownedExam), [
                'marks' => [
                    $this->student->id => 55,
                ],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->ownedExam->id,
            'student_id' => $this->student->id,
            'marks' => 93,
            'teacher_id' => $this->ownerTeacher->id,
        ]);

        $this->assertDatabaseMissing('marks', [
            'exam_id' => $this->ownedExam->id,
            'student_id' => $this->student->id,
            'marks' => 55,
        ]);

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'marks.saved',
            'subject_id' => $this->ownedExam->id,
        ]);
    }

    public function test_teacher_cannot_update_marks_for_another_teachers_exam(): void
    {
        $response = $this->actingAs($this->otherTeacher)
            ->patch(route('teacher.marks.update', [
                'exam' => $this->ownedExam,
                'student' => $this->student,
            ]), [
                'marks' => 55,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->ownedExam->id,
            'student_id' => $this->student->id,
            'marks' => 93,
        ]);

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'marks.updated',
            'subject_id' => $this->ownedExam->id,
        ]);
    }

    public function test_teacher_cannot_update_marks_for_an_exam_from_another_school(): void
    {
        $otherSchool = School::create([
            'name' => 'Other Marks School',
            'slug' => 'other-marks-' . uniqid(),
            'email' => 'other-marks-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->ownedExam->update(['school_id' => $otherSchool->id]);

        $response = $this->actingAs($this->ownerTeacher)
            ->patch(route('teacher.marks.update', [
                'exam' => $this->ownedExam,
                'student' => $this->student,
            ]), [
                'marks' => 55,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'marks.updated',
            'subject_id' => $this->ownedExam->id,
        ]);
    }

    public function test_teacher_cannot_save_marks_for_a_student_from_another_school(): void
    {
        $otherSchool = School::create([
            'name' => 'Other Student School',
            'slug' => 'other-student-' . uniqid(),
            'email' => 'other-student-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $otherStudent = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $otherSchool->id,
            'name' => 'Other School Student',
            'email' => 'other.student@t.sch.ug',
        ]);

        $response = $this->actingAs($this->ownerTeacher)
            ->post(route('teacher.exam.marks.save', $this->ownedExam), [
                'marks' => [
                    $otherStudent->id => 55,
                ],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('marks', [
            'exam_id' => $this->ownedExam->id,
            'student_id' => $otherStudent->id,
        ]);

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'marks.saved',
            'subject_id' => $this->ownedExam->id,
        ]);
    }

    public function test_assigned_teacher_can_save_marks_for_own_exam(): void
    {
        $response = $this->actingAs($this->ownerTeacher)
            ->post(route('teacher.exam.marks.save', $this->ownedExam), [
                'marks' => [
                    $this->student->id => 88,
                ],
            ]);

        $response->assertRedirect(route('teacher.exam.marks'));
        $response->assertSessionHas('successmessage');

        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->ownedExam->id,
            'student_id' => $this->student->id,
            'marks' => 88,
            'teacher_id' => $this->ownerTeacher->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'marks',
            'description' => 'marks.saved',
            'subject_id' => $this->ownedExam->id,
            'causer_id' => $this->ownerTeacher->id,
            'school_id' => $this->school->id,
        ]);

        $log = ActivityLog::query()
            ->where('description', 'marks.saved')
            ->where('subject_id', $this->ownedExam->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(1, $log->properties['affected_mark_count']);
        $this->assertArrayHasKey('request_id', $log->properties);
        $this->assertSame($this->section->id, $log->properties['section_id']);
        $this->assertSame($this->subject->id, $log->properties['subject_id']);
    }

    public function test_assigned_teacher_can_update_marks_and_audit_event_is_aggregate(): void
    {
        $response = $this->actingAs($this->ownerTeacher)
            ->patch(route('teacher.marks.update', [
                'exam' => $this->ownedExam,
                'student' => $this->student,
            ]), [
                'marks' => 88,
            ]);

        $response->assertRedirect(route('teacher.exam.marks'));

        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->ownedExam->id,
            'student_id' => $this->student->id,
            'marks' => 88,
            'teacher_id' => $this->ownerTeacher->id,
        ]);

        $log = ActivityLog::query()
            ->where('description', 'marks.updated')
            ->where('subject_id', $this->ownedExam->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(1, $log->properties['affected_mark_count']);
        $this->assertSame($this->school->id, $log->school_id);
        $this->assertSame($this->ownerTeacher->id, $log->properties['teacher_id']);
    }

    public function test_nursery_save_logs_valid_assessments_without_raw_payload(): void
    {
        $nurseryStandard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'nursery',
            'order' => 1,
            'status' => 1,
        ]);

        $this->ownedExam->update(['standard_id' => $nurseryStandard->id]);

        $response = $this->actingAs($this->ownerTeacher)
            ->post(route('teacher.exam.marks.save', $this->ownedExam), [
                'assessments' => [
                    $this->student->id => [
                        'Literacy' => 'Excellent',
                        'Numeracy' => 'Good',
                        'Motor Skills' => 'Not a valid rating',
                    ],
                ],
            ]);

        $response->assertRedirect(route('teacher.exam.marks'));

        $this->assertDatabaseHas('nursery_assessments', [
            'exam_id' => $this->ownedExam->id,
            'student_id' => $this->student->id,
            'domain' => 'Literacy',
            'rating' => 'Excellent',
        ]);

        $log = ActivityLog::query()
            ->where('description', 'marks.saved')
            ->where('subject_id', $this->ownedExam->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(2, $log->properties['affected_mark_count']);
        $this->assertArrayNotHasKey('marks', $log->properties);
        $this->assertArrayNotHasKey('ratings', $log->properties);
        $this->assertArrayNotHasKey('student_ids', $log->properties);
        $this->assertSame($this->school->id, $log->school_id);
    }
}
