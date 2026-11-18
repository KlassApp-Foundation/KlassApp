<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
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
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Progressive headings come from the school's exam sets, in id order.
 * The card does not look at the words Beginning, Mid, or End of Term.
 */
class ProgressiveHeadingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_columns_use_the_school_set_names_and_their_order(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 8, 'name' => 'Closing paper', 'code' => 'CLP', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Opening paper', 'code' => 'OPP', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'Practice paper', 'code' => 'PRP', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Heading Primary', 'slug' => 'heading-'.uniqid(), 'email' => uniqid().'@heading.test',
            'phone' => '076'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@heading-teacher.test', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $english = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Amina Kato',
            'email' => uniqid().'@heading-learner.test', 'status' => 'active', 'registration_number' => 'KLS0093001',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);

        foreach ([4 => 55, 9 => 61, 8 => 80] as $typeId => $score) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $english->id, 'teacher_id' => $teacher->id, 'exam_type_id' => $typeId,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-03-02 09:00:00', 'status' => 'submitted',
            ]));
            Marks::create([
                'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                'subject_id' => $english->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                'marks' => $score, 'grade' => 'stored',
            ]);
        }

        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);
        app(StudentReportCardService::class)->pdfForStudent((int) $school->id, $link, $learner);

        $this->assertSame(['Opening paper', 'Practice paper'], array_column($captured['monthlySets'], 'label'));
        $this->assertSame(['Closing paper'], array_column($captured['termSets'], 'label'));
        $this->assertSame(80.0, (float) $captured['total']);

        $html = view('admin.marks.report-templates.formal', $captured)->render();
        $monthly = strpos($html, 'MONTHLY RESULTS');
        $opening = $monthly === false ? false : strpos($html, '<th>Opening paper</th>', $monthly);
        $practice = $monthly === false ? false : strpos($html, '<th>Practice paper</th>', $monthly);
        $end = strpos($html, 'END OF TERM');
        $closing = $end === false ? false : strpos($html, '<th>Closing paper</th>', $end);
        $this->assertNotFalse($monthly);
        $this->assertNotFalse($opening);
        $this->assertTrue($monthly < $opening && $opening < $practice && $practice < $end && $end < $closing);
        $this->assertStringNotContainsString('MONTHLY RESULTS — MID TERM', $html);
        $this->assertStringNotContainsString('Month Of', $html);
        $this->assertStringNotContainsString('Mark Gained', $html);
        $this->assertStringNotContainsString('>EOT<', $html);
        $this->assertStringNotContainsString('BEGINNING OF TERM', $html);
        $this->assertStringNotContainsString('MARCH', $html);

        $school->forceFill(['report_template' => 'warm'])->save();
        $captured['school'] = $school->fresh();
        $warm = view('admin.marks.report-templates.warm', $captured)->render();
        $this->assertStringContainsString('Opening paper', $warm);
        $this->assertStringContainsString('Closing paper', $warm);
        $this->assertStringNotContainsString('MONTHLY RESULTS — MID TERM', $warm);

        foreach ([$html, $warm] as $card) {
            $this->assertStringContainsString('>AGGREGATE<', $card);
            foreach (array_merge($captured['monthlySets'], $captured['termSets']) as $set) {
                $printed = $set['aggregate'] === null ? '-' : (string) $set['aggregate'];
                $this->assertNotSame('', $printed);
                $this->assertMatchesRegularExpression(
                    '/data-kind="aggregate" data-set="'.preg_quote((string) $set['label'], '/').'"[^>]*>\s*<strong>'.preg_quote($printed, '/').'<\/strong>/',
                    $card,
                );
            }
        }
    }

    public function test_two_counting_sets_keep_the_weighted_combined_total(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 5, 'name' => 'First paper', 'code' => 'FST', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Second paper', 'code' => 'SND', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Weighted Primary', 'slug' => 'weighted-'.uniqid(), 'email' => uniqid().'@weighted.test',
            'phone' => '075'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
            'report_composition' => [
                '5' => ['include' => true, 'weight' => 30],
                '7' => ['include' => true, 'weight' => 70],
            ],
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@weighted-teacher.test', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $english = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Amina Kato',
            'email' => uniqid().'@weighted-learner.test', 'status' => 'active', 'registration_number' => 'KLS0093002',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);

        foreach ([5 => 60, 7 => 80] as $typeId => $score) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $english->id, 'teacher_id' => $teacher->id, 'exam_type_id' => $typeId,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-03-02 09:00:00', 'status' => 'submitted',
            ]));
            Marks::create([
                'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                'subject_id' => $english->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                'marks' => $score, 'grade' => '',
            ]);
            \App\Models\MarksRelease::create([
                'school_id' => $school->id, 'exam_id' => $exam->id, 'scope' => 'class', 'standard_link_id' => $link->id,
                'scheduled_at' => now(), 'released_at' => now(), 'status' => \App\Models\MarksRelease::STATUS_RELEASED, 'timezone' => 'UTC',
            ]);
        }

        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);
        app(StudentReportCardService::class)->pdfForStudent((int) $school->id, $link, $learner);

        $this->assertSame(['First paper', 'Second paper'], array_column($captured['termSets'], 'label'));
        $this->assertSame([], $captured['monthlySets']);
        $this->assertSame([60.0, 80.0], array_map(fn ($set) => (float) $set['total'], $captured['termSets']));
        $this->assertSame(74.0, (float) $captured['total']);

        $html = view('admin.marks.report-templates.formal', $captured)->render();
        $first = strpos($html, 'First paper');
        $second = strpos($html, 'Second paper');
        $combined = strpos($html, 'TOTAL:');
        $this->assertNotFalse($first);
        $this->assertTrue($first < $second && $second < $combined);
        $this->assertStringContainsString('>74<', $html);
        $this->assertStringNotContainsString('140', $html);
    }

    public function test_one_term_column_prints_the_rounded_cells_that_add_up_to_its_total(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 2, 'name' => 'End paper', 'code' => 'END', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Opening paper', 'code' => 'OPN', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Closing paper', 'code' => 'CLS', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Column Primary', 'slug' => 'column-'.uniqid(), 'email' => uniqid().'@column.test',
            'phone' => '074'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
            'report_composition' => [
                '4' => ['include' => true, 'weight' => 50],
                '6' => ['include' => true, 'weight' => 50],
            ],
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@column-teacher.test', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $english = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'Mathematics', 'code' => 'MAT', 'type' => 'core', 'status' => 1,
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Amina Kato',
            'email' => uniqid().'@column-learner.test', 'status' => 'active', 'registration_number' => 'KLS0093003',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);

        // Each weighted paper is 0.6. Rounded once, each cell is 1, and the
        // printed total is 2. Rounding the raw sum 1.2 would print 1.
        // The end paper keeps a different sitting mark, which must not replace
        // those cells while the total stays the combined figure.
        $scores = [
            4 => [0.6, 0.6],
            6 => [0.6, 0.6],
            2 => [44, 56],
        ];
        foreach ($scores as $typeId => [$englishScore, $mathsScore]) {
            foreach ([[$english, $englishScore], [$maths, $mathsScore]] as [$subject, $score]) {
                $exam = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                    'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => $typeId,
                    'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                    'scheduled_at' => '2026-03-02 09:00:00', 'status' => 'submitted',
                ]));
                Marks::create([
                    'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                    'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                    'marks' => $score, 'grade' => '',
                ]);
                if ($typeId !== 2) {
                    \App\Models\MarksRelease::create([
                        'school_id' => $school->id, 'exam_id' => $exam->id, 'scope' => 'class', 'standard_link_id' => $link->id,
                        'scheduled_at' => now(), 'released_at' => now(), 'status' => \App\Models\MarksRelease::STATUS_RELEASED, 'timezone' => 'UTC',
                    ]);
                }
            }
        }

        $stored = Marks::query()->orderBy('id')->pluck('marks')->map(fn ($mark) => (float) $mark)->all();

        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);
        app(StudentReportCardService::class)->pdfForStudent((int) $school->id, $link, $learner);

        $this->assertSame(['End paper'], array_column($captured['termSets'], 'label'));
        $this->assertSame(2.0, (float) $captured['total']);
        $this->assertSame($stored, Marks::query()->orderBy('id')->pluck('marks')->map(fn ($mark) => (float) $mark)->all());

        foreach (['admin.marks.report-templates.formal', 'admin.marks.report-templates.warm'] as $viewName) {
            $column = $this->endOfTermMarkColumn(view($viewName, $captured)->render());
            $this->assertSame(['1', '1'], $column['marks']);
            $this->assertSame('2', $column['total']);
            $this->assertSame(2, array_sum(array_map('intval', $column['marks'])));
        }
    }

    /**
     * @return array{marks: list<string>, total: string}
     */
    private function endOfTermMarkColumn(string $html): array
    {
        $start = strpos($html, 'END OF TERM');
        $this->assertNotFalse($start);
        $slice = substr($html, $start);
        $totalAt = strpos($slice, 'total-row');
        $this->assertNotFalse($totalAt);
        preg_match_all('/<td class="left">.*?<\/td>\s*<td>100<\/td>\s*<td>(.*?)<\/td>/s', substr($slice, 0, $totalAt), $marks);
        preg_match('/total-row.*?<\/td>\s*<td>.*?<\/td>\s*<td><strong>(.*?)<\/strong><\/td>/s', $slice, $total);

        return [
            'marks' => array_map(static fn (string $mark): string => trim(strip_tags($mark)), $marks[1]),
            'total' => trim(strip_tags($total[1] ?? '')),
        ];
    }

    public function test_columns_follow_the_exam_date_when_that_is_not_the_creation_order(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Later paper', 'code' => 'LAT', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Twin paper', 'code' => 'TWN', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'Earlier paper', 'code' => 'ERL', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Closing paper', 'code' => 'CLP', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Date Order Primary', 'slug' => 'date-order-'.uniqid(), 'email' => uniqid().'@date-order.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@date-order-teacher.test', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $english = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Amina Kato',
            'email' => uniqid().'@date-order-learner.test', 'status' => 'active', 'registration_number' => 'KLS0093003',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);

        foreach ([
            [1, '2026-04-20 09:00:00', 40],
            [9, '2026-02-10 09:00:00', 50],
            [3, '2026-02-10 09:00:00', 55],
            [2, '2026-03-15 09:00:00', 70],
        ] as [$typeId, $when, $score]) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $english->id, 'teacher_id' => $teacher->id, 'exam_type_id' => $typeId,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => $when, 'status' => 'submitted',
            ]));
            Marks::create([
                'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                'subject_id' => $english->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                'marks' => $score, 'grade' => 'stored',
            ]);
        }

        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);
        app(StudentReportCardService::class)->pdfForStudent((int) $school->id, $link, $learner);

        $this->assertSame(['Earlier paper', 'Twin paper', 'Later paper'], array_column($captured['monthlySets'], 'label'));
        $this->assertSame(['Closing paper'], array_column($captured['termSets'], 'label'));

        $html = view('admin.marks.report-templates.formal', $captured)->render();
        $earlier = strpos($html, '<th>Earlier paper</th>');
        $twin = strpos($html, '<th>Twin paper</th>');
        $later = strpos($html, '<th>Later paper</th>');
        $this->assertNotFalse($earlier);
        $this->assertTrue($earlier < $twin && $twin < $later);

        $binary = \Maatwebsite\Excel\Facades\Excel::raw(
            new \App\Exports\CombinedMarksheetExport($link, $term),
            \Maatwebsite\Excel\Excel::XLSX,
        );
        $tmp = tempnam(sys_get_temp_dir(), 'date-order').'.xlsx';
        file_put_contents($tmp, $binary);
        $names = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getSheetNames();
        @unlink($tmp);
        $this->assertSame(
            ['Earlier paper FEBRUARY', 'Twin paper FEBRUARY', 'Closing paper', 'Later paper APRIL'],
            $names,
        );
    }
}
