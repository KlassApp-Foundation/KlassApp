<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherReportCardSetNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_set_names_are_the_schools_own_names_in_card_order(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Set Names School',
            'slug' => 'set-names-'.uniqid(),
            'email' => 'set-names-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'report_template' => 'warm',
            'registration_country' => 'Uganda',
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.3 Sets',
            'status' => 1,
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'name' => 'Sets Class Teacher',
            'email' => 'sets.ct.'.uniqid().'@t.sch.ug',
        ]);

        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'status' => '1',
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        $trial = ExamType::create([
            'school_id' => $school->id,
            'name' => 'Trial Paper',
            'code' => 'TRIAL',
            'contributes_to_report_total' => true,
        ]);
        $bot = ExamType::create([
            'school_id' => $school->id,
            'name' => 'Bot',
            'code' => 'MID',
            'contributes_to_report_total' => false,
        ]);

        $make = function (ExamType $type, string $when) use ($school, $standard, $section, $subject, $teacher, $term, $year): void {
            Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'exam_type_id' => $type->id,
                'academic_term_id' => $term->id,
                'academic_year_id' => $year->id,
                'scheduled_at' => $when,
                'status' => 'submitted',
            ]));
        };

        // Trial Paper is dated before Bot. The card prints sets by date.
        $make($trial, '2026-02-01');
        $make($bot, '2026-04-01');

        $names = app(StudentReportCardService::class)->examSetNames((int) $school->id, $link);

        $this->assertSame(['Trial Paper', 'Bot'], $names);

        $response = $this->actingAs($teacher)->get(route('teacher.reports.cards.index'));

        $response->assertOk();
        $response->assertSee('Bot');
        $response->assertSee('Trial Paper');
        $response->assertDontSee('Mid Term');
        $response->assertDontSee('End of Term');
        $response->assertDontSee('End Of Term');
    }
}
