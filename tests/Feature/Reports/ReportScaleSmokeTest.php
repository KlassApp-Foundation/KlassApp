<?php

namespace Tests\Feature\Reports;

use App\Jobs\GenerateClassReportsJob;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\ReportGeneration;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Reports\ReportScoring;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Smalot\PdfParser\Parser;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * T-157: a generated school of 800 invented learners (10 classes of 80, 4 subjects, 2 exam sets, with
 * blanks and absences) goes through the report path: every class's results sheet, one whole-class PDF,
 * and a per-learner PDF from each class. It is a smoke test: it proves nothing errors, times out or loses
 * a learner at that size, and prints the times. Set SCALE_FULL=1 to build the whole-class PDF for every
 * class (about ten times longer). Group "scale".
 */
#[Group('scale')]
class ReportScaleSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const CLASSES = 10;

    private const PER_CLASS = 80;

    public function test_eight_hundred_learners_go_through_sheets_class_pdfs_and_learner_pdfs(): void
    {
        $timings = [];
        $started = microtime(true);
        $school = $this->generate();
        $timings['generate 800 learners'] = microtime(true) - $started;

        // 1. Every class's results sheet, with missing and ABS as 0, has all 80 learners and valid positions.
        $started = microtime(true);
        $scoring = app(ReportScoring::class);
        foreach ($school['classes'] as $class) {
            $sheet = app(ResultsService::class)->sheet(
                new ResultsQuery(schoolId: (int) $school['school']->id, sectionId: (int) $class['section']->id, academicTermId: (int) $school['term']->id),
                $scoring->policy((int) $school['school']->id, (int) $school['standard']->id, true),
            );
            $positions = [];
            foreach ($class['learners'] as $learner) {
                $row = $sheet->student((int) $learner->id);
                $this->assertNotNull($row, 'every learner is on the sheet');
                $this->assertSame(self::PER_CLASS, $row->classOutOf);
                $this->assertGreaterThanOrEqual(1, $row->classPosition);
                $this->assertLessThanOrEqual(self::PER_CLASS, $row->classPosition);
                $positions[] = $row->classPosition;
            }
            $this->assertContains(1, $positions, 'someone is first');
        }
        $timings['10 results sheets'] = microtime(true) - $started;

        // 2. A per-learner PDF from every class.
        $started = microtime(true);
        $service = app(StudentReportCardService::class);
        foreach ($school['classes'] as $class) {
            $pdf = $service->pdfForStudent((int) $school['school']->id, $class['link'], $class['learners'][7]);
            $this->assertStringStartsWith('%PDF', $pdf);
            $this->assertStringContainsString('PROGRESSIVE REPORT', (new Parser)->parseContent($pdf)->getText());
        }
        $timings['10 learner PDFs'] = microtime(true) - $started;

        // 3. The whole-class PDF (one class by default, every class with SCALE_FULL=1).
        $started = microtime(true);
        $classes = getenv('SCALE_FULL') === '1' ? $school['classes'] : [$school['classes'][0]];
        foreach ($classes as $class) {
            $generation = ReportGeneration::create([
                'school_id' => $school['school']->id, 'standard_link_id' => $class['link']->id, 'academic_term_id' => $school['term']->id,
                'class_name' => $class['section']->name, 'mode' => 'merged', 'status' => 'pending', 'requested_by' => $school['admin']->id,
            ]);
            (new GenerateClassReportsJob($generation->id))->handle();

            $generation->refresh();
            $this->assertSame('completed', $generation->status, (string) $generation->error);
            $path = storage_path('app/'.$generation->file_path);
            $this->assertFileExists($path);
            $this->assertGreaterThan(20000, filesize($path));
            $this->assertLessThan(10 * 1024 * 1024, filesize($path), 'the class PDF stays under 10 MB');
            $pages = count((new Parser)->parseFile($path)->getPages());
            $this->assertSame(self::PER_CLASS, $pages, "the whole-class PDF for {$class['section']->name} has a page for every learner");
            @unlink($path);
        }
        $timings['whole-class PDF x'.count($classes)] = microtime(true) - $started;

        fwrite(STDERR, "\nSCALE TIMINGS (seconds): ".json_encode(array_map(fn ($t) => round($t, 1), $timings))."\n");
        $this->assertLessThan(900, array_sum($timings), 'the whole smoke run stays inside 15 minutes');

    }

    /**
     * @return array{school: School, standard: Standard, term: AcademicTerm, admin: User, classes: list<array{section: Section, link: StandardLink, learners: list<User>}>}
     */
    private function generate(): array
    {
        mt_srand(2026);
        $base = SyntheticClass::build(['learners' => 0, 'counting_sets' => [1]]); // school, year, standard, bands, term, exam types, teacher, admin
        $school = $base->school;
        $classes = [];

        foreach (range(1, self::CLASSES) as $number) {
            $section = $number === 1 ? $base->section : Section::create(['school_id' => $school->id, 'name' => 'Class '.$number, 'status' => 1]);
            $link = $number === 1 ? $base->link : StandardLink::create([
                'school_id' => $school->id, 'academic_year_id' => $base->year->id, 'standard_id' => $base->standard->id,
                'section_id' => $section->id, 'class_teacher_id' => $base->teacher->id, 'stream' => 'A', 'status' => 1,
            ]);

            $subjects = $number === 1 ? $base->subjects : [];
            $exams = $number === 1 ? $base->exams : [];
            if ($number !== 1) {
                foreach ($base->fixture['subjects'] as $index => $name) {
                    $subjects[$index] = Subject::create([
                        'school_id' => $school->id, 'academic_year_id' => $base->year->id, 'standard_id' => $base->standard->id,
                        'section_id' => $section->id, 'name' => $name, 'code' => 'S'.$number.'x'.$index, 'type' => 'core', 'status' => 1,
                    ]);
                    foreach ([0, 1] as $setIndex) {
                        $exams[$setIndex][$index] = Exam::withoutEvents(fn () => Exam::create([
                            'school_id' => $school->id, 'standard_id' => $base->standard->id, 'section_id' => $section->id,
                            'subject_id' => $subjects[$index]->id, 'teacher_id' => $base->teacher->id,
                            'exam_type_id' => $base->exams[$setIndex][0]->exam_type_id,
                            'academic_term_id' => $base->term->id, 'academic_year_id' => $base->year->id,
                            'scheduled_at' => $base->exams[$setIndex][0]->scheduled_at, 'status' => 'submitted',
                        ]));
                    }
                }
            }

            $learners = [];
            for ($i = 1; $i <= self::PER_CLASS; $i++) {
                $learner = User::factory()->create([
                    'usergroup_id' => 6, 'school_id' => $school->id, 'name' => sprintf('Scale Pupil %d %02d', $number, $i),
                    'email' => sprintf('scale-%d-%02d@scale.test', $number, $i), 'status' => 'active',
                    'registration_number' => sprintf('KLS8%02d%04d', $number, $i),
                ]);
                $learners[] = $learner;
            }
            StudentAcademic::query()->insert(array_map(fn (User $l) => [
                'school_id' => $school->id, 'academic_year_id' => $base->year->id, 'user_id' => $l->id,
                'standardLink_id' => $link->id, 'created_at' => now(), 'updated_at' => now(),
            ], $learners));

            $rows = [];
            foreach ($learners as $learner) {
                foreach ([0, 1] as $setIndex) {
                    foreach (array_keys($subjects) as $subjectIndex) {
                        $roll = mt_rand(1, 100);
                        if ($roll <= 3) {
                            continue; // missing
                        }
                        $absent = $roll <= 5;
                        $rows[] = [
                            'student_id' => $learner->id, 'exam_id' => $exams[$setIndex][$subjectIndex]->id, 'school_id' => $school->id,
                            'subject_id' => $subjects[$subjectIndex]->id, 'teacher_id' => $base->teacher->id, 'section_id' => $section->id,
                            'marks' => $absent ? null : mt_rand(15, 100), 'grade' => $absent ? 'AB' : '', 'absent' => $absent ? 1 : 0,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('marks')->insert($chunk);
            }

            $classes[] = ['section' => $section, 'link' => $link, 'learners' => $learners];
        }

        return ['school' => $school, 'standard' => $base->standard, 'term' => $base->term, 'admin' => $base->admin, 'classes' => $classes];
    }
}
