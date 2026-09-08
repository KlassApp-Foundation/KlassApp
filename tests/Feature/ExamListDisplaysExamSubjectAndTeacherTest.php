<?php

namespace Tests\Feature;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamListDisplaysExamSubjectAndTeacherTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_list_shows_exam_subject_and_teacher_without_marks(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            [
                'id' => 2,
                'name' => 'End Of Term',
                'code' => 'EOT',
                'contributes_to_report_total' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], 'id');

        $school = School::create([
            'name' => 'Exam List School',
            'email' => 'exam.list@t.sch.ug',
            'phone' => '0700000088',
            'slug' => 'exam-list-'.uniqid(),
            'status' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-01',
        ]);

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'Primary Seven',
            'status' => 1,
        ]);

        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'email' => 'admin.exam.list@t.sch.ug',
        ]);

        $examTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'name' => 'James Okello',
            'email' => 'james.exam.list@t.sch.ug',
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $examTeacher->id,
            'usergroup_id' => 5,
            'firstname' => 'James',
            'lastname' => 'Okello',
            'status' => 'active',
        ]);

        $otherTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'name' => 'Grace Nambogo',
            'email' => 'grace.exam.list@t.sch.ug',
        ]);

        $stdLink = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English Language',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        // Class has a different teacherlink — list must NOT prefer this over exam.teacher_id.
        Teacherlink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $stdLink->id,
            'subject_id' => $subject->id,
            'teacher_id' => $otherTeacher->id,
            'status' => 1,
        ]);

        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $examTeacher->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'exam_type_id' => 2,
            'status' => 'pending',
            'scheduled_at' => now()->addDays(3),
        ]));

        $response = $this->actingAs($admin)->get(route('admin.exams'));

        $response->assertOk();
        $response->assertSee('ENGLISH LANGUAGE', false);
        $response->assertSee('JAMES OKELLO', false);
        $response->assertDontSee('GRACE NAMBOGO', false);
    }
}
