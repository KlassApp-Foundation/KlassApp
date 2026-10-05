<?php

namespace Tests\Support;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
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
 * Demo-only fixture for the marks import tests: one school, one class (section + stream),
 * one subject, one exam, an owner teacher, an unrelated teacher, an admin, three enrolled
 * students, one student in a different class and one student in a different school.
 * Admission numbers: KLS0000001 .. KLS0000003 (enrolled), KLS0000004 (other class), KLS0000005 (other school).
 */
trait MarksImportFixture
{
    protected School $school;

    protected User $admin;

    protected User $owner;

    protected User $stranger;

    /** @var array<int,User> keyed 1..3 */
    protected array $students = [];

    protected User $otherClassStudent;

    protected User $otherSchoolStudent;

    protected Exam $exam;

    protected Section $section;

    protected Standard $standard;

    protected Subject $subject;

    protected AcademicYear $year;

    protected function buildMarksImportFixture(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = $this->makeSchool('Demo Import School');
        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current Academic Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $term = AcademicTerm::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'name' => 'Term I', 'status' => 'current', 'starts_on' => '2026-02-01', 'ends_on' => '2026-05-01']);
        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'Grade 4', 'status' => 1]);
        $otherSection = Section::create(['school_id' => $this->school->id, 'name' => 'Grade 5', 'status' => 1]);

        SchoolGradingSystem::create(['school_id' => $this->school->id, 'standard_id' => $this->standard->id, 'grade' => 'A', 'points' => 1, 'min_score' => 0, 'max_score' => 100, 'remark' => 'All']);

        $link = StandardLink::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'status' => 1]);
        $otherLink = StandardLink::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $otherSection->id, 'status' => 1]);

        $this->subject = Subject::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'name' => 'Mathematics', 'code' => '001', 'type' => 'core', 'status' => 1]);

        $this->admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->school->id, 'name' => 'Demo Admin']);
        $this->owner = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Demo Owner Teacher']);
        $this->stranger = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'name' => 'Demo Other Teacher']);

        $names = [1 => 'Amina Nakato', 2 => 'Brian Okello', 3 => 'Chloe Mwesigwa'];
        foreach ($names as $n => $name) {
            $this->students[$n] = $this->makeStudent($this->school, $name, sprintf('KLS%07d', $n), $link);
        }
        $this->otherClassStudent = $this->makeStudent($this->school, 'Daniel Ssemakula', 'KLS0000004', $otherLink);

        $otherSchool = $this->makeSchool('Demo Other School');
        $otherYear = AcademicYear::create(['school_id' => $otherSchool->id, 'name' => '2026', 'description' => 'Current Academic Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $otherStd = Standard::create(['school_id' => $otherSchool->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $otherSec = Section::create(['school_id' => $otherSchool->id, 'name' => 'Grade 4', 'status' => 1]);
        $otherSchoolLink = StandardLink::create(['school_id' => $otherSchool->id, 'academic_year_id' => $otherYear->id, 'standard_id' => $otherStd->id, 'section_id' => $otherSec->id, 'status' => 1]);
        $this->otherSchoolStudent = $this->makeStudent($otherSchool, 'Eve Namukasa', 'KLS0000005', $otherSchoolLink);

        $this->exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id, 'academic_term_id' => $term->id, 'subject_id' => $this->subject->id,
            'teacher_id' => $this->owner->id, 'exam_type_id' => 2, 'status' => 'undone',
        ]));
    }

    protected function makeSchool(string $name): School
    {
        return School::create([
            'name' => $name, 'slug' => 'demo-'.uniqid(), 'email' => uniqid().'@demo.test', 'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
    }

    protected function makeStudent(School $school, string $name, string $admissionNo, StandardLink $link): User
    {
        $student = User::factory()->create(['usergroup_id' => 6, 'school_id' => $school->id, 'name' => $name, 'status' => 'active', 'registration_number' => $admissionNo]);
        Userprofile::create(['school_id' => $school->id, 'user_id' => $student->id, 'usergroup_id' => 6, 'firstname' => $name, 'lastname' => '', 'status' => 'active']);
        StudentAcademic::create(['school_id' => $school->id, 'academic_year_id' => $link->academic_year_id, 'user_id' => $student->id, 'standardLink_id' => $link->id]);

        return $student;
    }

    /**
     * Spreadsheet rows as the service reads them: the #986 template headings, then [registration number, name, mark].
     *
     * @param  list<array{0:?string,1:?string,2:mixed}>  $data
     * @return list<list<mixed>>
     */
    protected function sheet(array $data): array
    {
        return array_merge([['registration_number', 'student_name', 'mark']], $data);
    }

    protected function saveMark(User $student, float $mark, ?Exam $exam = null): Marks
    {
        $exam ??= $this->exam;

        return Marks::create([
            'student_id' => $student->id, 'exam_id' => $exam->id, 'school_id' => $exam->school_id, 'subject_id' => $exam->subject_id,
            'teacher_id' => $exam->teacher_id, 'section_id' => $exam->section_id, 'marks' => $mark, 'grade' => 'A',
        ]);
    }
}
