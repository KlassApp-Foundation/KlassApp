<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\RangeDivisionRule;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsService;
use App\Services\Results\SchoolGradingScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R4 (approved 2026-11-22): a learner with no mark in one subject still gets a
 * Total, an Aggregate and a Division, the no-mark counts as 0, and the label
 * reads "Division 1", never "Div I".
 *
 * The numbers R4 quotes for the staging demo (Winnie 323 / 12 / 1, Gerald
 * 190 / 23 / 2, Norah 220 / 20 / 2) are that environment's demo data. A fresh
 * seed of DemoAcademySeeder measures differently (Winnie 288 / 15 / 2, Gerald
 * 298 / 14 / 2, Norah 226 / 24 / 2), so this test pins the arithmetic instead
 * of a snapshot: four Division subjects, three with a mark, one with no mark.
 * The no-mark prints a dash, adds 0 to the total, and adds the zero band
 * (F9 = 9 points) to the aggregate.
 */
class MarksNoMarkTotalsTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'school admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->ctx = $this->school();
        $this->learner = $this->learner($this->ctx, 'Amina Kato');
    }

    public function test_a_no_mark_prints_a_dash_and_counts_as_zero_in_the_total(): void
    {
        // Four Division subjects: three marked, one with no mark at all.
        $this->sitting($this->ctx, $this->ctx['english'], 70);
        $this->sitting($this->ctx, $this->ctx['maths'], 80);
        $this->sitting($this->ctx, $this->ctx['science'], 60);
        $this->sitting($this->ctx, $this->ctx['social'], null);

        $row = $this->sheetRow();

        $this->assertSame(ResultCell::DASH, $row->cell($this->ctx['social']->id)->sheetLabel(), 'a subject with no mark reads as a dash, never ABS');
        $this->assertSame(210, (int) round((float) $row->total), 'the three marked subjects add up; the no-mark adds 0');
        $this->assertSame(4, (int) $row->subjectsCounted, 'the no-mark is still counted, so the division sees four subjects');
    }

    public function test_the_no_mark_takes_the_zero_band_in_the_aggregate(): void
    {
        $this->sitting($this->ctx, $this->ctx['english'], 70);
        $this->sitting($this->ctx, $this->ctx['maths'], 80);
        $this->sitting($this->ctx, $this->ctx['science'], 60);
        $this->sitting($this->ctx, $this->ctx['social'], null);

        $row = $this->sheetRow();
        $points = 0;
        foreach ([$this->ctx['english'], $this->ctx['maths'], $this->ctx['science']] as $subject) {
            $points += (int) $row->cell($subject->id)->points;
        }

        $this->assertSame($points + 9, (int) $row->aggregate, 'a no-mark is the zero band, F9 = 9 points');
    }

    public function test_the_division_reads_division_one_not_div_i(): void
    {
        foreach (['english' => 90, 'maths' => 90, 'science' => 90, 'social' => 90] as $key => $score) {
            $this->sitting($this->ctx, $this->ctx[$key], $score);
        }

        $row = $this->sheetRow();
        $this->assertSame('1', (string) $row->division, 'the stored value is the label 1');

        // Every screen prints the stored label after the word "Division", so a
        // label of "1" reads "Division 1" and never "Div I".
        foreach ([
            'resources/views/admin/marks/class-marks.blade.php',
            'resources/views/portal/partials/family-marks.blade.php',
        ] as $view) {
            $source = file_get_contents(base_path($view));
            $this->assertStringContainsString("Division {{", $source, $view.' prints the word Division before the label');
            $this->assertStringNotContainsString('Div {{', $source, $view.' does not abbreviate Division');
        }
    }

    private function sheetRow(): object
    {
        // The school's own scoring rule: a missing mark counts as zero.
        $policy = new ResultsPolicy(
            app(SchoolGradingScale::class),
            new RangeDivisionRule([
                ['min' => 4, 'max' => 12, 'label' => '1'],
                ['min' => 13, 'max' => 24, 'label' => '2'],
                ['min' => 25, 'max' => 34, 'label' => '3'],
                ['min' => 35, 'max' => null, 'label' => '4'],
            ]),
            countMissingAsZero: true,
        );

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->ctx['school']->id,
            sectionId: (int) $this->ctx['section']->id,
            academicTermId: (int) $this->ctx['term']->id,
            examTypeId: 2,
        ), $policy);

        foreach ($sheet->students as $student) {
            if ((int) $student->studentId === (int) $this->learner->id) {
                return $student;
            }
        }

        $this->fail('the learner is not on the sheet: '.json_encode(array_map(fn ($s) => $s->name, $sheet->students)));
    }

    private function school(): array
    {
        $school = School::create([
            'name' => 'No Mark School',
            'email' => 'nomark-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'nomark-'.uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
            'grading_style' => 'aggregate',
        ]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.4', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => 'ct.'.uniqid().'@t.sch.ug', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'stream' => 'East',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        foreach ([[95, 100, 'D1', 1], [90, 94, 'D2', 2], [75, 89, 'C3', 3], [65, 74, 'C4', 4], [60, 64, 'C5', 5],
            [50, 59, 'C6', 6], [45, 49, 'P7', 7], [40, 44, 'P8', 8], [0, 39, 'F9', 9]] as [$min, $max, $grade, $points]) {
            \App\Models\Academics\SchoolGradingSystem::create([
                'school_id' => $school->id, 'standard_id' => $standard->id,
                'grade' => $grade, 'points' => $points, 'min_score' => $min, 'max_score' => $max, 'remark' => '',
            ]);
        }
        Cache::forget('school_grading_'.$school->id);

        $subjects = [];
        foreach (['english' => 'English', 'maths' => 'Mathematics', 'science' => 'Science', 'social' => 'Social Studies'] as $key => $name) {
            $subjects[$key] = Subject::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'name' => $name,
                'code' => strtoupper(substr($key, 0, 4)),
                'type' => 'core',
                'status' => 1,
            ]);
        }

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher') + $subjects;
    }

    private function learner(array $ctx, string $name): User
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $ctx['school']->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => $ctx['link']->id,
        ]);

        return $user;
    }

    private function sitting(array $ctx, Subject $subject, ?float $score): Exam
    {
        $exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => 2,
            'academic_term_id' => $ctx['term']->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => '2026-04-20 09:00:00',
            'status' => 'submitted',
        ]));

        if ($score !== null) {
            Marks::create([
                'student_id' => $this->learner->id,
                'exam_id' => $exam->id,
                'school_id' => $ctx['school']->id,
                'subject_id' => $subject->id,
                'teacher_id' => $ctx['teacher']->id,
                'section_id' => $ctx['section']->id,
                'marks' => $score,
                'grade' => '',
            ]);
        }

        return $exam;
    }
}
