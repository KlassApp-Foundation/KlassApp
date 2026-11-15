<?php

namespace Tests\Support;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Builds the pack's invented class (tests/fixtures/reports/p5-synthetic-class.json: 76 learners, 4
 * subjects, 2 exam sets, with a missing mark, ABS marks and a tie) in the database, through the same
 * tables the app uses. Every name and number is invented. The grade bands are the reference bands
 * (D1 to F9), saved as the school's own, because they are not the app's default bands.
 *
 * A missing mark has no marks row; ABS is a marks row with absent = true. The two exam sets are exam
 * types named as in the fixture and coded SET1 and SET2: nothing depends on the names.
 */
final class SyntheticClass
{
    public School $school;

    public AcademicYear $year;

    public Standard $standard;

    public Section $section;

    public StandardLink $link;

    public AcademicTerm $term;

    public User $teacher;

    public User $admin;

    /** @var list<Subject> in the fixture's subject order */
    public array $subjects = [];

    /** @var list<array<int, Exam>> exams[setIndex][subjectIndex] */
    public array $exams = [];

    /** @var array<string, User> learners keyed by KLS number */
    public array $learners = [];

    /** @var array<string, mixed> the decoded fixture */
    public array $fixture = [];

    /**
     * @param  array{learners?: int, counting_sets?: list<int>, ranking_basis?: ?string, template?: string, suffix?: string, submitted?: bool}  $options
     */
    public static function build(array $options = []): self
    {
        $self = new self;
        $self->fixture = self::fixture();
        $count = (int) ($options['learners'] ?? count($self->fixture['learners']));
        $counting = $options['counting_sets'] ?? [];
        $nameSuffix = isset($options['suffix']) ? ' '.$options['suffix'] : '';
        $suffix = (string) ($options['suffix'] ?? uniqid());

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $typeIds = [];
        foreach ($self->fixture['sets'] as $index => $setName) {
            $typeIds[$index] = DB::table('exam_types')->insertGetId([
                'name' => $setName.$nameSuffix, 'code' => 'SET'.($index + 1).substr($suffix, 0, 4),
                'contributes_to_report_total' => in_array($index, $counting, true) ? 1 : 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $self->school = School::create([
            'name' => $self->fixture['school'], 'slug' => 'synthetic-'.$suffix, 'email' => $suffix.'@synthetic.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => $options['template'] ?? 'progressive',
        ]);
        $self->year = AcademicYear::create([
            'school_id' => $self->school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$self->school->id);
        $self->standard = Standard::create([
            'school_id' => $self->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
            'ranking_basis' => $options['ranking_basis'] ?? null,
        ]);
        foreach (self::bands() as [$min, $max, $grade, $points]) {
            SchoolGradingSystem::create([
                'school_id' => $self->school->id, 'standard_id' => $self->standard->id,
                'grade' => $grade, 'points' => $points, 'min_score' => $min, 'max_score' => $max, 'remark' => '',
            ]);
        }
        Cache::forget('school_grading_'.$self->school->id);

        $self->section = Section::create(['school_id' => $self->school->id, 'name' => $self->fixture['class'], 'status' => 1]);
        $self->teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $self->school->id, 'email' => $suffix.'-teacher@synthetic.test', 'status' => 'active',
        ]);
        $self->admin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $self->school->id, 'email' => $suffix.'-admin@synthetic.test', 'status' => 'active',
        ]);
        $self->link = StandardLink::create([
            'school_id' => $self->school->id, 'academic_year_id' => $self->year->id, 'standard_id' => $self->standard->id,
            'section_id' => $self->section->id, 'class_teacher_id' => $self->teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $self->term = AcademicTerm::create([
            'school_id' => $self->school->id, 'academic_year_id' => $self->year->id, 'name' => 'Third Term',
            'status' => 'current', 'starts_on' => '2026-01-12', 'ends_on' => '2026-12-04',
        ]);

        $dates = ['2026-03-02 09:00:00', '2026-04-20 09:00:00', '2026-05-18 09:00:00'];
        foreach ($self->fixture['subjects'] as $index => $name) {
            $self->subjects[$index] = Subject::create([
                'school_id' => $self->school->id, 'academic_year_id' => $self->year->id, 'standard_id' => $self->standard->id,
                'section_id' => $self->section->id, 'name' => $name, 'code' => strtoupper(substr($name, 0, 3)).$index, 'type' => 'core', 'status' => 1,
            ]);
            foreach ($typeIds as $setIndex => $typeId) {
                $self->exams[$setIndex][$index] = Exam::withoutEvents(fn () => Exam::create([
                    'school_id' => $self->school->id, 'standard_id' => $self->standard->id, 'section_id' => $self->section->id,
                    'subject_id' => $self->subjects[$index]->id, 'teacher_id' => $self->teacher->id, 'exam_type_id' => $typeId,
                    'academic_term_id' => $self->term->id, 'academic_year_id' => $self->year->id,
                    'scheduled_at' => $dates[$setIndex], 'status' => 'submitted',
                ]));
            }
        }

        if (! empty($options['submitted'])) {
            foreach ($self->exams as $perSubject) {
                foreach ($perSubject as $subjectIndex => $exam) {
                    ExamMarksSubmission::create([
                        'school_id' => $self->school->id, 'exam_id' => $exam->id, 'class_id' => $self->section->id,
                        'subject_id' => $self->subjects[$subjectIndex]->id, 'teacher_id' => $self->teacher->id, 'status' => 'locked',
                        'approval_status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(),
                    ]);
                }
            }
        }

        foreach (array_slice($self->fixture['learners'], 0, $count) as $row) {
            $learner = User::factory()->create([
                'usergroup_id' => 6, 'school_id' => $self->school->id, 'name' => $row['name'],
                'email' => strtolower(str_replace(' ', '', $row['kls'])).'-'.$suffix.'@synthetic-learner.test',
                'status' => 'active', 'registration_number' => $row['kls'],
            ]);
            StudentAcademic::create([
                'school_id' => $self->school->id, 'academic_year_id' => $self->year->id,
                'user_id' => $learner->id, 'standardLink_id' => $self->link->id,
            ]);
            foreach ($row['marks'] as $setIndex => $setMarks) {
                foreach ($setMarks as $subjectIndex => $raw) {
                    if ($raw === null) {
                        continue; // missing: no marks row
                    }
                    $absent = $raw === 'ABS';
                    Marks::create([
                        'student_id' => $learner->id, 'exam_id' => $self->exams[$setIndex][$subjectIndex]->id, 'school_id' => $self->school->id,
                        'subject_id' => $self->subjects[$subjectIndex]->id, 'teacher_id' => $self->teacher->id, 'section_id' => $self->section->id,
                        'marks' => $absent ? null : $raw, 'grade' => $absent ? 'AB' : '', 'absent' => $absent,
                    ]);
                }
            }
            $self->learners[$row['kls']] = $learner;
        }

        return $self;
    }

    /** @return array<string, mixed> */
    public static function fixture(): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/../fixtures/reports/p5-synthetic-class.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return list<array{0: int, 1: int, 2: string, 3: int}> reference bands: min, max, grade, points */
    public static function bands(): array
    {
        return [[95, 100, 'D1', 1], [90, 94, 'D2', 2], [75, 89, 'C3', 3], [65, 74, 'C4', 4], [60, 64, 'C5', 5],
            [50, 59, 'C6', 6], [45, 49, 'P7', 7], [40, 44, 'P8', 8], [0, 39, 'F9', 9]];
    }

    /** The fixture's expected values for one learner, keyed by KLS number. */
    public function expected(string $kls): array
    {
        foreach ($this->fixture['expected'] as $row) {
            if ($row['kls'] === $kls) {
                return $row;
            }
        }
        throw new \OutOfBoundsException($kls);
    }

    /**
     * The card's view data for one learner (the real service, a stand-in PDF writer that keeps what the
     * template was given). Needs one counting set, otherwise the card refuses (known gap, see the plan).
     *
     * @return array<string, mixed>
     */
    public function cardData(string $kls): array
    {
        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        app()->instance('dompdf.wrapper', $pdf);

        app(\App\Services\StudentReportCardService::class)->pdfForStudent((int) $this->school->id, $this->link, $this->learners[$kls]);
        app()->forgetInstance('dompdf.wrapper');

        return $captured;
    }

    /** The real PDF for one learner, as the text a person would read in it. */
    public function pdfText(string $kls): string
    {
        app()->forgetInstance('dompdf.wrapper');
        $pdf = app(\App\Services\StudentReportCardService::class)->pdfForStudent((int) $this->school->id, $this->link, $this->learners[$kls]);

        return (new \Smalot\PdfParser\Parser)->parseContent($pdf)->getText();
    }
}
