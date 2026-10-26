<?php

namespace Tests\Feature\Results;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
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
use App\Services\Results\ResultSheet;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * One results sheet for grades, totals, positions and divisions.
 * Marks are read by exam type, term, class and subject, for both stored shapes.
 */
class ResultsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_per_subject_exams_and_a_legacy_shared_exam_produce_the_same_totals(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => '9', 'points' => 9, 'min' => 0, 'max' => 100],
        ]);

        $split = $this->student($ctx, 'Split Student');
        $early = $this->exam($ctx, $term, $ctx['english'], '2026-03-01');
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $maths = $this->exam($ctx, $term, $ctx['maths'], '2026-04-02');
        $this->mark($ctx, $early, $ctx['english'], $split, 10, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $split, 70, 'stored');
        $this->mark($ctx, $maths, $ctx['maths'], $split, 55, 'stored');

        $legacyStudent = $this->student($ctx, 'Legacy Student');
        $shared = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $this->mark($ctx, $shared, $ctx['english'], $legacyStudent, 70, 'stored');
        $this->mark($ctx, $shared, $ctx['maths'], $legacyStudent, 55, 'stored');

        $sheet = $this->sheet($ctx, $term);

        $this->assertSame(['ENGLISH', 'MATHEMATICS'], array_column($sheet->subjects, 'name'));
        foreach ([$split, $legacyStudent] as $student) {
            $row = $sheet->student($student->id);
            $this->assertNotNull($row);
            $this->assertSame(70.0, $row->cell($ctx['english']->id)->mark);
            $this->assertSame('9', $row->cell($ctx['english']->id)->grade);
            $this->assertSame(9, $row->cell($ctx['english']->id)->points);
            $this->assertSame('marked', $row->cell($ctx['maths']->id)->status);
            $this->assertSame(125.0, $row->total);
            $this->assertSame(2, $row->subjectsCounted);
            $this->assertSame(18, $row->aggregate);
            $this->assertSame('2', $row->division);
        }
    }

    public function test_missing_marks_are_excluded_and_absent_labels_do_not_count_as_zero(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => '9', 'points' => 9, 'min' => 0, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $maths = $this->exam($ctx, $term, $ctx['maths'], '2026-04-02');

        $partial = $this->student($ctx, 'Partial Student');
        $this->mark($ctx, $english, $ctx['english'], $partial, 80, 'stored');

        $absent = $this->student($ctx, 'Absent Student');
        $this->mark($ctx, $english, $ctx['english'], $absent, 80, 'stored');
        $this->mark($ctx, $maths, $ctx['maths'], $absent, 0, 'ABS');

        $sheet = $this->sheet($ctx, $term, $this->policy(absent: ['ABS']));

        $partialRow = $sheet->student($partial->id);
        $this->assertSame('missing', $partialRow->cell($ctx['maths']->id)->status);
        $this->assertNull($partialRow->cell($ctx['maths']->id)->mark);
        $this->assertSame(80.0, $partialRow->total);
        $this->assertSame(80.0, $partialRow->average);
        $this->assertSame(1, $partialRow->subjectsCounted);
        $this->assertSame(9, $partialRow->aggregate);
        $this->assertSame('1', $partialRow->division);

        $absentRow = $sheet->student($absent->id);
        $this->assertSame('absent', $absentRow->cell($ctx['maths']->id)->status);
        $this->assertNull($absentRow->cell($ctx['maths']->id)->mark);
        $this->assertSame(80.0, $absentRow->total);
        $this->assertSame(80.0, $absentRow->average);
        $this->assertSame(1, $absentRow->subjectsCounted);
    }

    public function test_decimal_marks_use_the_scale_and_display_rounds_half_up(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => 'F', 'points' => 9, 'min' => 0, 'max' => 79],
            ['grade' => 'D', 'points' => 1, 'min' => 80, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');

        $insideGap = $this->student($ctx, 'Gap Student');
        $onBand = $this->student($ctx, 'Band Student');
        $above = $this->student($ctx, 'Above Student');
        $this->mark($ctx, $english, $ctx['english'], $insideGap, 79.5, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $onBand, 80, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $above, 100.4, 'stored');

        $sheet = $this->sheet($ctx, $term, $this->policy(decimals: 0));

        $gap = $sheet->student($insideGap->id);
        $this->assertSame(79.5, $gap->cell($ctx['english']->id)->mark);
        $this->assertSame('F', $gap->cell($ctx['english']->id)->grade);
        $this->assertSame(9, $gap->cell($ctx['english']->id)->points);
        $this->assertSame(80.0, $gap->displayTotal);

        $band = $sheet->student($onBand->id);
        $this->assertSame('D', $band->cell($ctx['english']->id)->grade);
        $this->assertSame(1, $band->cell($ctx['english']->id)->points);

        $over = $sheet->student($above->id);
        $this->assertSame('marked', $over->cell($ctx['english']->id)->status);
        $this->assertNull($over->cell($ctx['english']->id)->grade);
        $this->assertNull($over->cell($ctx['english']->id)->points);
        $this->assertSame(100.4, $over->total);
        $this->assertSame(100.0, $over->displayTotal);

        $half = $this->student($ctx, 'Half Student');
        $this->mark($ctx, $english, $ctx['english'], $half, 10.5, 'stored');
        $rounded = $this->sheet($ctx, $term, $this->policy(decimals: 0));
        $this->assertSame(10.5, $rounded->student($half->id)->total);
        $this->assertSame(11.0, $rounded->student($half->id)->displayAverage);
    }

    public function test_ties_share_a_position_and_the_next_rank_skips(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => 'F', 'points' => 9, 'min' => 0, 'max' => 79],
            ['grade' => 'D', 'points' => 1, 'min' => 80, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');

        $first = $this->student($ctx, 'First Student');
        $tiedA = $this->student($ctx, 'Tied A');
        $tiedB = $this->student($ctx, 'Tied B');
        $fourth = $this->student($ctx, 'Fourth Student');
        $this->mark($ctx, $english, $ctx['english'], $first, 90, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $tiedA, 80, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $tiedB, 80, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $fourth, 70, 'stored');

        $sheet = $this->sheet($ctx, $term);

        $this->assertSame(1, $sheet->student($first->id)->classPosition);
        $this->assertSame(2, $sheet->student($tiedA->id)->classPosition);
        $this->assertSame(2, $sheet->student($tiedB->id)->classPosition);
        $this->assertSame(4, $sheet->student($fourth->id)->classPosition);
        $this->assertSame(4, $sheet->student($fourth->id)->classOutOf);
    }

    public function test_ranking_basis_and_direction_come_from_the_policy(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => 'F', 'points' => 9, 'min' => 0, 'max' => 79],
            ['grade' => 'D', 'points' => 1, 'min' => 80, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $maths = $this->exam($ctx, $term, $ctx['maths'], '2026-04-02');

        $lowerPoints = $this->student($ctx, 'Lower Points');
        $higherTotal = $this->student($ctx, 'Higher Total');
        $this->mark($ctx, $english, $ctx['english'], $lowerPoints, 90, 'stored');
        $this->mark($ctx, $maths, $ctx['maths'], $lowerPoints, 10, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $higherTotal, 60, 'stored');
        $this->mark($ctx, $maths, $ctx['maths'], $higherTotal, 60, 'stored');

        $byTotal = $this->sheet($ctx, $term);
        $this->assertSame(120.0, $byTotal->student($higherTotal->id)->total);
        $this->assertSame(1, $byTotal->student($higherTotal->id)->classPosition);
        $this->assertSame(2, $byTotal->student($lowerPoints->id)->classPosition);

        $byAggregate = $this->sheet($ctx, $term, $this->policy(ResultsPolicy::BASIS_AGGREGATE, false));
        $this->assertSame(10, $byAggregate->student($lowerPoints->id)->aggregate);
        $this->assertSame(18, $byAggregate->student($higherTotal->id)->aggregate);
        $this->assertSame(1, $byAggregate->student($lowerPoints->id)->classPosition);
        $this->assertSame(2, $byAggregate->student($higherTotal->id)->classPosition);
    }

    public function test_a_term_report_keeps_non_contributing_sittings_out_of_the_total(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => '9', 'points' => 9, 'min' => 0, 'max' => 100],
        ]);
        $science = $this->subject($ctx, 'Science', 'SCI');
        $student = $this->student($ctx, 'Sitting Student');
        $midEnglish = $this->exam($ctx, $term, $ctx['english'], '2026-02-01', 1);
        $midScience = $this->exam($ctx, $term, $science, '2026-02-02', 1);
        $endEnglish = $this->exam($ctx, $term, $ctx['english'], '2026-04-01', 2);
        $this->mark($ctx, $midEnglish, $ctx['english'], $student, 40, 'stored');
        $this->mark($ctx, $midScience, $science, $student, 30, 'stored');
        $this->mark($ctx, $endEnglish, $ctx['english'], $student, 80, 'stored');

        $sheet = $this->sheet($ctx, $term);

        $this->assertSame(['ENGLISH'], array_column($sheet->subjects, 'name'));
        $row = $sheet->student($student->id);
        $this->assertSame(80.0, $row->total);
        $this->assertSame(1, $row->subjectsCounted);
        $this->assertArrayNotHasKey($science->id, $row->cells);

        $this->assertCount(2, $sheet->sittings);
        $mid = $sheet->sittings[0];
        $end = $sheet->sittings[1];
        $this->assertSame('MID', $mid->code);
        $this->assertFalse($mid->contributesToReport);
        $this->assertSame(40.0, $mid->student($student->id)->cell($ctx['english']->id)->mark);
        $this->assertSame(30.0, $mid->student($student->id)->cell($science->id)->mark);
        $this->assertSame('EOT', $end->code);
        $this->assertTrue($end->contributesToReport);
        $this->assertSame(80.0, $end->student($student->id)->total);

        $midSheet = $this->sheet($ctx, $term, examTypeId: 1);
        $this->assertSame(70.0, $midSheet->student($student->id)->total);
        $this->assertSame(2, $midSheet->student($student->id)->subjectsCounted);
    }

    public function test_one_student_keeps_class_and_stream_positions(): void
    {
        $ctx = $this->school();
        $west = $this->stream($ctx, 'West');
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => '9', 'points' => 9, 'min' => 0, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');

        $ada = $this->student($ctx, 'Ada East');
        $bea = $this->student($ctx, 'Bea East');
        $cal = $this->student($ctx, 'Cal West', $west);
        $this->mark($ctx, $english, $ctx['english'], $ada, 90, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $bea, 70, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $cal, 80, 'stored');

        $adaOnly = $this->sheet($ctx, $term, studentId: $ada->id);
        $this->assertCount(1, $adaOnly->students);
        $adaRow = $adaOnly->student($ada->id);
        $this->assertSame(1, $adaRow->classPosition);
        $this->assertSame(3, $adaRow->classOutOf);
        $this->assertSame(1, $adaRow->streamPosition);
        $this->assertSame(2, $adaRow->streamOutOf);
        $this->assertSame(3, $adaOnly->summary->subjects[0]['expectedCount']);

        $westSheet = $this->sheet($ctx, $term, linkId: $west->id);
        $this->assertSame([$cal->id], array_map(fn ($row) => $row->studentId, $westSheet->students));
        $this->assertSame(2, $westSheet->student($cal->id)->classPosition);
        $this->assertSame(3, $westSheet->student($cal->id)->classOutOf);
        $this->assertSame(1, $westSheet->student($cal->id)->streamPosition);
        $this->assertSame(1, $westSheet->student($cal->id)->streamOutOf);
    }

    public function test_other_schools_terms_and_inactive_students_are_excluded(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $otherTerm = $this->term($ctx, 'Term II', 'next', '2026-05-01', '2026-08-31');
        $this->bands($ctx, [
            ['grade' => '9', 'points' => 9, 'min' => 0, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $active = $this->student($ctx, 'Active Student');
        $this->mark($ctx, $english, $ctx['english'], $active, 50, 'stored');

        $laterOtherTerm = $this->exam($ctx, $otherTerm, $ctx['english'], '2026-06-01');
        $this->mark($ctx, $laterOtherTerm, $ctx['english'], $active, 99, 'stored');

        $inactive = $this->student($ctx, 'Inactive Student', status: 'inactive');
        $this->mark($ctx, $english, $ctx['english'], $inactive, 100, 'stored');

        $other = $this->school('Other School');
        $otherTermRow = $this->term($other);
        $otherExam = $this->exam($other, $otherTermRow, $other['english'], '2026-04-15');
        $otherExam->section_id = $ctx['section']->id;
        $otherExam->save();
        $this->mark($other, $otherExam, $other['english'], $active, 1, 'stored');

        $sheet = $this->sheet($ctx, $term);

        $this->assertSame(50.0, $sheet->student($active->id)->total);
        $this->assertSame(1, $sheet->student($active->id)->classPosition);
        $this->assertSame(1, $sheet->student($active->id)->classOutOf);
        $this->assertNull($sheet->student($inactive->id));
    }

    public function test_split_mid_term_by_month_keeps_each_month_and_the_default_collapses_them(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => '9', 'points' => 9, 'min' => 0, 'max' => 100],
        ]);
        $student = $this->student($ctx, 'Month Student');
        $june = $this->exam($ctx, $term, $ctx['english'], '2026-06-15', 1);
        $july = $this->exam($ctx, $term, $ctx['english'], '2026-07-15', 1);
        $end = $this->exam($ctx, $term, $ctx['english'], '2026-08-01', 2);
        $this->mark($ctx, $june, $ctx['english'], $student, 40, 'stored');
        $this->mark($ctx, $july, $ctx['english'], $student, 70, 'stored');
        $this->mark($ctx, $end, $ctx['english'], $student, 80, 'stored');

        $collapsed = $this->sheet($ctx, $term);
        $collapsedMid = array_values(array_filter($collapsed->sittings, fn ($sitting) => $sitting->code === 'MID'));
        $this->assertCount(1, $collapsedMid);
        $this->assertSame('', $collapsedMid[0]->periodKey);
        $this->assertSame(70.0, $collapsedMid[0]->student($student->id)->cell($ctx['english']->id)->mark);
        $this->assertSame(80.0, $collapsed->student($student->id)->total);

        $split = $this->sheet($ctx, $term, $this->policy(splitMidTermByMonth: true));
        $months = array_values(array_filter($split->sittings, fn ($sitting) => $sitting->code === 'MID'));
        $this->assertCount(2, $months);
        $this->assertSame('2026-06', $months[0]->periodKey);
        $this->assertSame('JUNE', $months[0]->periodLabel);
        $this->assertSame(40.0, $months[0]->student($student->id)->cell($ctx['english']->id)->mark);
        $this->assertSame('2026-07', $months[1]->periodKey);
        $this->assertSame('JULY', $months[1]->periodLabel);
        $this->assertSame(70.0, $months[1]->student($student->id)->cell($ctx['english']->id)->mark);
        $this->assertSame(80.0, $split->student($student->id)->total);
        $this->assertCount(1, array_filter($split->sittings, fn ($sitting) => $sitting->contributesToReport));

        $earlierJune = $this->exam($ctx, $term, $ctx['english'], '2026-06-02', 1);
        $this->mark($ctx, $earlierJune, $ctx['english'], $student, 11, 'stored');
        $sameMonth = $this->sheet($ctx, $term, $this->policy(splitMidTermByMonth: true));
        $juneSitting = null;
        foreach ($sameMonth->sittings as $sitting) {
            if ($sitting->periodKey === '2026-06') {
                $juneSitting = $sitting;
            }
        }
        $this->assertNotNull($juneSitting);
        $this->assertSame(40.0, $juneSitting->student($student->id)->cell($ctx['english']->id)->mark);

        $undatedEarly = $this->exam($ctx, $term, $ctx['maths'], '2026-03-01', 1);
        $undatedLate = $this->exam($ctx, $term, $ctx['maths'], '2026-03-02', 1);
        Exam::withoutEvents(function () use ($undatedEarly, $undatedLate) {
            $undatedEarly->scheduled_at = null;
            $undatedEarly->save();
            $undatedLate->scheduled_at = null;
            $undatedLate->save();
        });
        $this->mark($ctx, $undatedEarly, $ctx['maths'], $student, 15, 'stored');
        $this->mark($ctx, $undatedLate, $ctx['maths'], $student, 25, 'stored');

        $separate = $this->sheet($ctx, $term, $this->policy(splitMidTermByMonth: true));
        $undated = array_values(array_filter(
            $separate->sittings,
            fn ($sitting) => $sitting->code === 'MID' && $sitting->periodKey === ''
        ));
        $this->assertCount(2, $undated);
        $undatedMarks = array_map(
            fn ($sitting) => $sitting->student($student->id)->cell($ctx['maths']->id)->mark,
            $undated
        );
        sort($undatedMarks);
        $this->assertSame([15.0, 25.0], $undatedMarks);
    }

    public function test_school_exam_type_preference_overrides_the_global_flag_and_the_letter_comes_from_the_scale(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => 'B', 'points' => 2, 'min' => 0, 'max' => 100],
        ]);
        $student = $this->student($ctx, 'Pref Student');
        $mid = $this->exam($ctx, $term, $ctx['english'], '2026-03-01', 1);
        $eot = $this->exam($ctx, $term, $ctx['english'], '2026-04-01', 2);
        $this->mark($ctx, $mid, $ctx['english'], $student, 40, 'C3');
        $this->mark($ctx, $eot, $ctx['english'], $student, 80, 'C3');

        // The settings form stores the checkbox as the strings "0" and "1".
        $ctx['school']->exam_type_preferences = ['1' => '1', '2' => '0'];
        $ctx['school']->save();

        $sheet = $this->sheet($ctx, $term);
        $row = $sheet->student($student->id);

        $this->assertSame(40.0, $row->total);
        $this->assertSame('B', $row->cell($ctx['english']->id)->grade);
        $contributing = array_values(array_filter(
            $sheet->sittings,
            fn ($sitting) => $sitting->contributesToReport
        ));
        $this->assertSame(['MID'], array_map(fn ($sitting) => $sitting->code, $contributing));
    }

    public function test_a_promoted_learner_stays_on_the_sitting_they_sat(): void
    {
        $ctx = $this->school();
        $west = $this->stream($ctx, 'West');
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => 'C3', 'points' => 3, 'min' => 0, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $ada = $this->student($ctx, 'Ada East');
        $bea = $this->student($ctx, 'Bea East');
        $this->mark($ctx, $english, $ctx['english'], $ada, 80, 'stored');
        $this->mark($ctx, $english, $ctx['english'], $bea, 70, 'stored');

        $nextYear = AcademicYear::create([
            'school_id' => $ctx['school']->id,
            'name' => '2027',
            'description' => 'Next year',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 0,
        ]);
        $nextSection = Section::create([
            'school_id' => $ctx['school']->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $nextLink = StandardLink::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $nextYear->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $nextSection->id,
            'class_teacher_id' => $ctx['teacher']->id,
            'stream' => 'North',
            'status' => 1,
        ]);
        StudentAcademic::query()->where('user_id', $ada->id)->update([
            'standardLink_id' => $nextLink->id,
            'academic_year_id' => $nextYear->id,
        ]);

        $other = $this->school('Outside School');
        $outsider = $this->student($other, 'Outsider Learner');
        Marks::create([
            'student_id' => $outsider->id,
            'exam_id' => $english->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $ctx['english']->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $ctx['section']->id,
            'marks' => 99,
            'grade' => 'stored',
        ]);

        $sheet = $this->sheet($ctx, $term, examTypeId: 2);
        $row = $sheet->student($ada->id);
        $this->assertNotNull($row);
        $this->assertSame(80.0, $row->total);
        $this->assertSame('', $row->stream);
        $this->assertSame(0, $row->standardLinkId);
        $this->assertSame('C3', $row->cell($ctx['english']->id)->grade);
        $this->assertNotNull($sheet->student($bea->id));
        $this->assertSame('East', $sheet->student($bea->id)->stream);
        $this->assertNull($sheet->student($outsider->id));
        $this->assertSame(1, $row->classPosition);
        $this->assertSame(2, $row->classOutOf);

        $westOnly = $this->sheet($ctx, $term, linkId: $west->id, examTypeId: 2);
        $this->assertNull($westOnly->student($ada->id));

        $solo = $this->school('Solo Stream School');
        $soloTerm = $this->term($solo);
        $this->bands($solo, [
            ['grade' => 'C3', 'points' => 3, 'min' => 0, 'max' => 100],
        ]);
        $soloExam = $this->exam($solo, $soloTerm, $solo['english'], '2026-04-01');
        $moved = $this->student($solo, 'Moved Learner');
        $this->mark($solo, $soloExam, $solo['english'], $moved, 60, 'stored');
        $later = AcademicYear::create([
            'school_id' => $solo['school']->id,
            'name' => '2027',
            'description' => 'Later year',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 0,
        ]);
        $laterSection = Section::create([
            'school_id' => $solo['school']->id,
            'name' => 'P.6',
            'status' => 1,
        ]);
        $laterLink = StandardLink::create([
            'school_id' => $solo['school']->id,
            'academic_year_id' => $later->id,
            'standard_id' => $solo['standard']->id,
            'section_id' => $laterSection->id,
            'stream' => 'South',
            'status' => 1,
        ]);
        StudentAcademic::query()->where('user_id', $moved->id)->update([
            'standardLink_id' => $laterLink->id,
            'academic_year_id' => $later->id,
        ]);

        $soloSheet = $this->sheet($solo, $soloTerm, examTypeId: 2);
        $soloRow = $soloSheet->student($moved->id);
        $this->assertNotNull($soloRow);
        $this->assertSame('East', $soloRow->stream);
        $this->assertSame($solo['link']->id, $soloRow->standardLinkId);
        $this->assertSame('1 of 1', $soloRow->placeLabel(false));
    }

    public function test_subject_rows_carry_the_submission_label(): void
    {
        $ctx = $this->school();
        $term = $this->term($ctx);
        $this->bands($ctx, [
            ['grade' => 'C3', 'points' => 3, 'min' => 0, 'max' => 100],
        ]);
        $english = $this->exam($ctx, $term, $ctx['english'], '2026-04-01');
        $maths = $this->exam($ctx, $term, $ctx['maths'], '2026-04-02');
        $ada = $this->student($ctx, 'Ada Label');
        $this->mark($ctx, $english, $ctx['english'], $ada, 80, 'stored');
        ExamMarksSubmission::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $english->id,
            'class_id' => $ctx['section']->id,
            'subject_id' => $ctx['english']->id,
            'teacher_id' => $ctx['teacher']->id,
            'status' => 'submitted',
            'approval_status' => 'pending',
        ]);

        $sheet = $this->sheet($ctx, $term, examTypeId: 2);
        $byId = [];
        foreach ($sheet->subjects as $subject) {
            $byId[$subject['id']] = $subject['submission'];
        }

        $this->assertSame('Submitted', $byId[$ctx['english']->id]);
        $this->assertSame('Not started', $byId[$ctx['maths']->id]);
        $this->assertSame('East', $sheet->student($ada->id)->stream);
    }

    /**
     * @return array{school: School, year: AcademicYear, standard: Standard, section: Section, link: StandardLink, teacher: User, english: Subject, maths: Subject}
     */
    private function school(string $name = 'Results School'): array
    {
        $school = School::create([
            'name' => $name,
            'email' => 'results-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'results-'.uniqid(),
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
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => 'ct.'.uniqid().'@t.sch.ug',
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
        $english = $this->subject(compact('school', 'year', 'standard', 'section'), 'English', 'ENG');
        $maths = $this->subject(compact('school', 'year', 'standard', 'section'), 'Mathematics', 'MATH');

        return compact('school', 'year', 'standard', 'section', 'link', 'teacher', 'english', 'maths');
    }

    private function subject(array $ctx, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function stream(array $ctx, string $name): StandardLink
    {
        return StandardLink::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'class_teacher_id' => $ctx['teacher']->id,
            'stream' => $name,
            'status' => 1,
        ]);
    }

    private function student(array $ctx, string $name, ?StandardLink $link = null, string $status = 'active'): User
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $ctx['school']->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@t.sch.ug',
            'status' => $status,
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => ($link ?? $ctx['link'])->id,
        ]);

        return $user;
    }

    private function term(array $ctx, string $name = 'Term I', string $status = 'current', string $starts = '2026-01-01', string $ends = '2026-04-30'): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'name' => $name,
            'status' => $status,
            'starts_on' => $starts,
            'ends_on' => $ends,
        ]);
    }

    private function exam(array $ctx, AcademicTerm $term, Subject $subject, string $when, int $examTypeId = 2): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => $examTypeId,
            'academic_term_id' => $term->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(array $ctx, Exam $exam, Subject $subject, User $student, float $score, string $grade): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $exam->section_id,
            'marks' => $score,
            'grade' => $grade,
        ]);
    }

    /**
     * @param list<array{grade: string, points: int, min: int, max: int}> $rows
     */
    private function bands(array $ctx, array $rows): void
    {
        foreach ($rows as $row) {
            SchoolGradingSystem::create([
                'school_id' => $ctx['school']->id,
                'standard_id' => $ctx['standard']->id,
                'grade' => $row['grade'],
                'points' => $row['points'],
                'min_score' => $row['min'],
                'max_score' => $row['max'],
                'remark' => $row['grade'],
            ]);
        }
    }

    private function policy(?string $basis = null, ?bool $higher = null, int $decimals = 0, array $absent = [], bool $splitMidTermByMonth = false): ResultsPolicy
    {
        return new ResultsPolicy(
            app(GradingScale::class),
            app(DivisionRule::class),
            $basis ?? ResultsPolicy::BASIS_TOTAL,
            $higher ?? true,
            ResultsPolicy::TIE_SHARED,
            $decimals,
            $absent,
            $splitMidTermByMonth,
        );
    }

    private function sheet(
        array $ctx,
        AcademicTerm $term,
        ?ResultsPolicy $policy = null,
        ?int $linkId = null,
        ?int $examTypeId = null,
        ?int $studentId = null,
    ): ResultSheet {
        return app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: $ctx['school']->id,
            sectionId: $ctx['section']->id,
            academicTermId: $term->id,
            standardLinkId: $linkId,
            examTypeId: $examTypeId,
            studentId: $studentId,
        ), $policy ?? $this->policy());
    }
}
