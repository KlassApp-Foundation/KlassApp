<?php

namespace Tests\Support;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Facades\DB;

/**
 * Demo-only fixture for the admin student profile: one school with a current year and term, one
 * class with two subjects and an exam for each, an admin, a teacher, an enrolled student, and a
 * second school with its own admin and student (for cross-school checks).
 */
trait StudentProfileFixture
{
    protected School $school;

    protected School $otherSchool;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected User $otherAdmin;

    protected User $otherStudent;

    protected AcademicYear $year;

    protected AcademicTerm $term;

    protected Section $section;

    protected Standard $standard;

    protected StandardLink $link;

    protected ExamType $examType;

    /** @var array<string, Exam> subject name => exam */
    protected array $exams = [];

    protected function buildStudentProfileFixture(): void
    {
        $now = now();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'teacher', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'student', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'name' => 'parent', 'created_at' => $now, 'updated_at' => $now],
        ], 'id');

        $this->school = $this->makeProfileSchool('Demo Profile School');
        $this->otherSchool = $this->makeProfileSchool('Demo Other Profile School');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current Academic Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $this->term = AcademicTerm::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'name' => 'Term 3', 'status' => 'current', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-18']);
        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'Grade 5', 'status' => 1]);
        $this->link = StandardLink::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'status' => 1]);
        SchoolGradingSystem::create(['school_id' => $this->school->id, 'standard_id' => $this->standard->id, 'grade' => 'A', 'points' => 1, 'min_score' => 0, 'max_score' => 100, 'remark' => 'All']);
        DB::table('exam_types')->upsert([['id' => 2, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 1, 'created_at' => $now, 'updated_at' => $now]], 'id');
        $this->examType = ExamType::find(2);

        $this->admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->school->id, 'name' => 'Demo Profile Admin', 'status' => 'active']);
        $this->teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Demo Subject Teacher', 'status' => 'active']);
        $this->student = $this->makeProfileStudent($this->school, 'Amara Okoro', 'KLS0000431', $this->link);

        foreach (['English', 'Mathematics'] as $i => $name) {
            $subject = Subject::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'name' => $name, 'code' => '00'.($i + 1), 'type' => 'core', 'status' => 1]);
            $this->exams[$name] = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $this->school->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
                'academic_year_id' => $this->year->id, 'academic_term_id' => $this->term->id, 'subject_id' => $subject->id,
                'teacher_id' => $this->teacher->id, 'exam_type_id' => 2, 'status' => 'undone',
            ]));
        }

        $this->otherAdmin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->otherSchool->id, 'status' => 'active']);
        $otherYear = AcademicYear::create(['school_id' => $this->otherSchool->id, 'name' => '2026', 'description' => 'Current Academic Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $otherStd = Standard::create(['school_id' => $this->otherSchool->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $otherSection = Section::create(['school_id' => $this->otherSchool->id, 'name' => 'Grade 5', 'status' => 1]);
        $otherLink = StandardLink::create(['school_id' => $this->otherSchool->id, 'academic_year_id' => $otherYear->id, 'standard_id' => $otherStd->id, 'section_id' => $otherSection->id, 'status' => 1]);
        $this->otherStudent = $this->makeProfileStudent($this->otherSchool, 'Eve Other', 'KLS0000432', $otherLink);
    }

    protected function makeProfileSchool(string $name): School
    {
        return School::create(['name' => $name, 'slug' => 'demo-'.uniqid(), 'email' => uniqid().'@demo.test', 'phone' => '070'.random_int(1000000, 9999999), 'status' => 1]);
    }

    protected function makeProfileStudent(School $school, string $name, string $kls, StandardLink $link): User
    {
        [$first, $last] = explode(' ', $name, 2) + [1 => ''];
        $student = User::factory()->create(['usergroup_id' => 6, 'school_id' => $school->id, 'name' => $name, 'status' => 'active', 'registration_number' => $kls]);
        Userprofile::create(['school_id' => $school->id, 'user_id' => $student->id, 'usergroup_id' => 6, 'firstname' => $first, 'lastname' => $last, 'gender' => 'female', 'status' => 'active']);
        StudentAcademic::create(['school_id' => $school->id, 'academic_year_id' => $link->academic_year_id, 'user_id' => $student->id, 'standardLink_id' => $link->id]);

        return $student;
    }

    protected function draftMark(string $subject, float $mark, ?User $student = null): Marks
    {
        $exam = $this->exams[$subject];
        $student ??= $this->student;

        return Marks::create(['student_id' => $student->id, 'exam_id' => $exam->id, 'school_id' => $exam->school_id, 'subject_id' => $exam->subject_id, 'teacher_id' => $exam->teacher_id, 'section_id' => $exam->section_id, 'marks' => $mark, 'grade' => 'A']);
    }

    protected function submission(string $subject, string $status, ?string $approval = null): ExamMarksSubmission
    {
        $exam = $this->exams[$subject];

        return ExamMarksSubmission::create([
            'school_id' => $exam->school_id, 'exam_id' => $exam->id, 'class_id' => $exam->section_id, 'subject_id' => $exam->subject_id,
            'teacher_id' => $this->teacher->id, 'status' => $status, 'approval_status' => $approval ?? 'pending',
        ]);
    }

    /** Release the sitting to the whole school (scope school), like Marks Release does. */
    protected function release(string $subject = 'English'): void
    {
        ReleasedExam::release($this->exams[$subject]);
    }
}
