<?php

namespace Tests\Feature\ReportCards;

use App\Enums\ToshiMode;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ReportCards\EndOfTermCompositionLine;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\Support\ReleasedExam;
use Tests\TestCase;

/**
 * A school with five exam sets of its own names. Two count at 40 and 60.
 * The others stand alone. Missing and ABS count as 0 and stay visible.
 */
class SchoolExamSetReportScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_school_with_five_named_sets_builds_one_combined_report(): void
    {
        $now = now();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'teacher', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'student', 'created_at' => $now, 'updated_at' => $now],
        ], 'id');

        $school = School::create([
            'name' => 'Paperlane School',
            'slug' => 'paperlane-'.uniqid(),
            'email' => uniqid().'@demo.test',
            'phone' => '0700000000',
            'status' => 1,
        ]);
        $school->forceFill([
            'toshi_mode' => ToshiMode::Preview,
            'toshi_enabled' => 0,
        ])->save();

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
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-15',
        ]);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'lower', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'Form Two', 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'name' => 'Paperlane Admin',
            'status' => 'active',
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'name' => 'Paperlane Teacher',
            'status' => 'active',
        ]);

        $sets = [
            'North Paper' => ['code' => 'NP', 'when' => '2026-02-02', 'weight' => 40],
            'South Paper' => ['code' => 'SP', 'when' => '2026-03-02', 'weight' => 60],
            'Quiz Hour' => ['code' => 'QH', 'when' => '2026-03-16', 'weight' => null],
            'Month Check' => ['code' => 'MC', 'when' => '2026-04-06', 'weight' => null],
            'Late Paper' => ['code' => 'LP', 'when' => '2026-05-04', 'weight' => null],
        ];
        $types = [];
        foreach ($sets as $name => $set) {
            $types[$name] = ExamType::create([
                'school_id' => $school->id,
                'name' => $name,
                'code' => $set['code'],
                'contributes_to_report_total' => false,
            ]);
        }

        $mix = [];
        foreach ($types as $name => $type) {
            $weight = $sets[$name]['weight'];
            $mix[(string) $type->id] = $weight === null
                ? ['include' => false]
                : ['include' => true, 'weight' => $weight];
        }
        $school->forceFill(['report_composition' => $mix])->save();

        $subjects = [];
        foreach (['English' => 'EN', 'Mathematics' => 'MA'] as $name => $code) {
            $subjects[$name] = Subject::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'name' => $name,
                'code' => $code,
                'type' => 'core',
                'status' => 1,
            ]);
        }

        $exams = [];
        foreach ($types as $name => $type) {
            foreach ($subjects as $subjectName => $subject) {
                $exams[$name][$subjectName] = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $school->id,
                    'standard_id' => $standard->id,
                    'section_id' => $section->id,
                    'academic_year_id' => $year->id,
                    'academic_term_id' => $term->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'exam_type_id' => $type->id,
                    'status' => 'undone',
                    'scheduled_at' => $sets[$name]['when'],
                ]));
            }
        }

        $ada = $this->learner($school, $link, 'Ada North', 'KLS0009101');
        $ben = $this->learner($school, $link, 'Ben Gap', 'KLS0009102');
        $cho = $this->learner($school, $link, 'Cho Away', 'KLS0009103');

        $this->mark($exams['North Paper']['English'], $ada, 80);
        $this->mark($exams['North Paper']['Mathematics'], $ada, 70);
        $this->mark($exams['North Paper']['English'], $ben, 90);
        $this->mark($exams['North Paper']['English'], $cho, 40);
        Marks::create([
            'student_id' => $cho->id,
            'exam_id' => $exams['North Paper']['Mathematics']->id,
            'school_id' => $school->id,
            'subject_id' => $subjects['Mathematics']->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'marks' => null,
            'grade' => 'AB',
            'absent' => true,
        ]);
        $this->mark($exams['South Paper']['English'], $ada, 55);
        $this->mark($exams['Quiz Hour']['English'], $ada, 33);
        $this->mark($exams['Month Check']['English'], $ada, 22);

        ReleasedExam::release($exams['North Paper']['English']);
        ReleasedExam::release($exams['North Paper']['Mathematics']);

        $northUrl = '/admin/reports/cards?class='.$section->id.'&exam='.$types['North Paper']->id.':'.$term->id;
        $blocked = $this->actingAs($admin)->get($northUrl)->assertOk();
        $blockedHtml = $blocked->getContent();

        $blocked->assertSee('data-testid="report-blocked"', false);
        $this->assertMatchesRegularExpression(
            '/data-testid="report-blocked-sets">\s*South Paper has marks that are not released yet\./',
            $blockedHtml
        );
        $blocked->assertSee('Marks Release');
        $blocked->assertSee(route('admin.marks.releases.index'), false);
        $blocked->assertSee('Made up of: North Paper 40% · South Paper 60%');
        $blocked->assertSee('Quiz Hour');
        $blocked->assertSee('Month Check');
        $blocked->assertSee('Late Paper');
        $blocked->assertDontSee('Ada North');
        $this->assertDoesNotMatchRegularExpression('/Quiz Hour has marks that are not released yet/', $blockedHtml);
        $this->assertDoesNotMatchRegularExpression('/Month Check has marks that are not released yet/', $blockedHtml);
        $this->assertDoesNotMatchRegularExpression('/Late Paper has marks that are not released yet/', $blockedHtml);
        $this->assertNoWorkaround($blockedHtml);
        $this->assertNoFixedSetNames($blockedHtml);

        $quizUrl = '/admin/reports/cards?class='.$section->id.'&exam='.$types['Quiz Hour']->id.':'.$term->id;
        $quiz = $this->actingAs($admin)->get($quizUrl)->assertOk();
        $quiz->assertDontSee('data-testid="report-blocked"', false);
        $quiz->assertDontSee('Made up of:');
        $this->assertNoFixedSetNames($quiz->getContent());

        ReleasedExam::release($exams['South Paper']['English']);
        ReleasedExam::release($exams['South Paper']['Mathematics']);

        $ready = $this->actingAs($admin)->get($northUrl)->assertOk();
        $html = $ready->getContent();
        $ready->assertDontSee('data-testid="report-blocked"', false);
        $ready->assertSee('Made up of: North Paper 40% · South Paper 60%');
        $ready->assertSee('data-testid="eot-composition"', false);
        $ready->assertSee('Each exam set has its own report');
        $ready->assertSee('Missing');
        $ready->assertSee('ABS');
        $ready->assertSee('23.3');
        $this->assertMatchesRegularExpression('/<strong>1<\/strong>.*?Ada North/s', $html);
        $this->assertMatchesRegularExpression('/<strong>2<\/strong>.*?Ben Gap/s', $html);
        $this->assertMatchesRegularExpression('/<strong>3<\/strong>.*?Cho Away/s', $html);
        $this->assertMatchesRegularExpression('/Ben Gap.*?Missing/s', $html);
        $this->assertMatchesRegularExpression('/Cho Away.*?ABS/s', $html);
        $this->assertMatchesRegularExpression('/>150</', $html);
        $this->assertMatchesRegularExpression('/>90</', $html);
        $this->assertMatchesRegularExpression('/>40</', $html);
        $this->assertNoWorkaround($html);
        $this->assertNoFixedSetNames($html);
        $this->assertSame('AB', Marks::query()->where('student_id', $cho->id)->where('exam_id', $exams['North Paper']['Mathematics']->id)->value('grade'));

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            (int) $school->id,
            (int) $section->id,
            (int) $term->id,
            null,
            (int) $types['North Paper']->id,
        ));
        $benRow = $sheet->student($ben->id);
        $choRow = $sheet->student($cho->id);
        $adaRow = $sheet->student($ada->id);
        $this->assertSame('missing', $benRow->cell($subjects['Mathematics']->id)->status);
        $this->assertNull($benRow->cell($subjects['Mathematics']->id)->mark);
        $this->assertSame(90.0, $benRow->total);
        $this->assertSame(2, $benRow->subjectsCounted);
        $this->assertSame(2, $benRow->classPosition);
        $this->assertSame('absent', $choRow->cell($subjects['Mathematics']->id)->status);
        $this->assertNull($choRow->cell($subjects['Mathematics']->id)->mark);
        $this->assertSame(40.0, $choRow->total);
        $this->assertSame(3, $choRow->classPosition);
        $this->assertSame(150.0, $adaRow->total);
        $this->assertSame(1, $adaRow->classPosition);

        $line = app(EndOfTermCompositionLine::class)->lineFor(
            (int) $school->id,
            true,
            (int) $exams['North Paper']['English']->id,
        );
        $this->assertSame('Made up of: North Paper 40% · South Paper 60%', $line);

        $service = app(StudentReportCardService::class);
        $display = new ReflectionMethod($service, 'displayMarksBySubject');
        $benEnglish = Marks::query()
            ->where('student_id', $ben->id)
            ->where('exam_id', $exams['North Paper']['English']->id)
            ->first();
        $printed = $display->invoke(
            $service,
            [$subjects['English']->id => $benEnglish],
            collect(),
            collect([$exams['North Paper']['English'], $exams['North Paper']['Mathematics']]),
        );
        $this->assertSame('90', $printed[$subjects['English']->id]->displayMark());
        $this->assertSame('Missing', $printed[$subjects['Mathematics']->id]->displayMark());

        $absentPrinted = $display->invoke(
            $service,
            [],
            Marks::query()->where('student_id', $cho->id)->where('exam_id', $exams['North Paper']['Mathematics']->id)->get(),
            collect([$exams['North Paper']['Mathematics']]),
        );
        $this->assertSame('ABS', $absentPrinted[$subjects['Mathematics']->id]->displayMark());

        $missingCell = new ResultCell((int) $subjects['Mathematics']->id, null, null, null, ResultCell::MISSING);
        $absentCell = new ResultCell((int) $subjects['Mathematics']->id, null, 'AB', null, ResultCell::ABSENT);
        $this->assertStringContainsString('Missing', view('admin.marks.report-templates.mark-text', ['mark' => $printed[$subjects['Mathematics']->id]])->render());
        $this->assertStringContainsString('Missing', view('admin.marks.report-templates.mark-text', ['mark' => $missingCell])->render());
        $this->assertStringContainsString('ABS', view('admin.marks.report-templates.mark-text', ['mark' => $absentPrinted[$subjects['Mathematics']->id]])->render());
        $this->assertStringContainsString('ABS', view('admin.marks.report-templates.mark-text', ['mark' => $absentCell])->render());

        $card = $this->cardData($ben, $subjects['Mathematics'], $printed[$subjects['Mathematics']->id], $line, $link);
        foreach (['admin.marks.report-templates.formal', 'admin.marks.report-templates.warm'] as $view) {
            $pdf = view($view, $card)->render();
            $this->assertStringContainsString('Made up of: North Paper 40% · South Paper 60%', $pdf);
            $this->assertStringContainsString('Missing', $pdf);
            $this->assertStringContainsString('>90<', $pdf);
            $this->assertNoWorkaround($pdf);
        }

        ReleasedExam::release($exams['Late Paper']['English']);
        $afterLate = $this->actingAs($admin)->get($northUrl)->assertOk()->getContent();
        $this->assertMatchesRegularExpression(
            '/data-testid="own-report-'.$types['Late Paper']->id.'".*?Late Paper.*?Released /s',
            $afterLate
        );
        $this->assertSame(ToshiMode::Preview, $school->fresh()->toshi_mode);
        $this->assertSame(0, (int) $school->fresh()->toshi_enabled);
    }

    private function learner(School $school, StandardLink $link, string $name, string $number): User
    {
        [$first, $last] = explode(' ', $name, 2);
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => $name,
            'status' => 'active',
            'registration_number' => $number,
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $student->id,
            'usergroup_id' => 6,
            'firstname' => $first,
            'lastname' => $last,
            'gender' => 'female',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }

    private function mark(Exam $exam, User $student, float $score): Marks
    {
        return Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $exam->school_id,
            'subject_id' => $exam->subject_id,
            'teacher_id' => $exam->teacher_id,
            'section_id' => $exam->section_id,
            'marks' => $score,
            'grade' => 'A',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cardData(User $learner, Subject $subject, Marks $mark, string $line, StandardLink $link): array
    {
        $learner = $learner->fresh();
        $learner->setRelation('marks', collect([$mark]));
        $learner->setRelation('school', $learner->school);

        return [
            'learner' => $learner,
            'class_name' => 'Form Two',
            'schoolIdentity' => ['name' => 'Paperlane School', 'footer_line' => 'Paperlane School'],
            'logoPath' => null,
            'isNursery' => false,
            'showAgg' => false,
            'showDivision' => false,
            'showPoints' => false,
            'midExams' => collect(),
            'eotExams' => collect([$mark->exam]),
            'subjects' => collect([$subject]),
            'grading_system' => collect(),
            'stdLink' => $link,
            'eotMarkBySubject' => [],
            'eotDisplayBySubject' => [$subject->id => $mark],
            'midSittings' => [],
            'midStats' => [],
            'total' => 90,
            'grade' => ['agg' => null],
            'examinedSubjectCount' => 1,
            'eotAgg' => null,
            'eotDivision' => '-',
            'teacherComment' => '',
            'headTeacherComment' => '',
            'nextTerm' => null,
            'compositionLine' => $line,
        ];
    }

    private function assertNoWorkaround(string $html): void
    {
        $this->assertStringNotContainsString('incomplete', strtolower($html));
        $this->assertStringNotContainsString('re-weight', strtolower($html));
        $this->assertStringNotContainsString('reweight', strtolower($html));
        $this->assertStringNotContainsString('workaround', strtolower($html));
    }

    private function assertNoFixedSetNames(string $html): void
    {
        $this->assertStringNotContainsString('Beginning', $html);
        $this->assertStringNotContainsString('Mid Term', $html);
        $this->assertStringNotContainsString('End of Term', $html);
        $this->assertStringNotContainsString('End Of Term', $html);
    }
}
