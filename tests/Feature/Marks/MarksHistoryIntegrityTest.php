<?php

namespace Tests\Feature\Marks;

use App\Exceptions\MarksLockedException;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamSet;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Services\Marks\ExamMarkWriter;
use App\Services\Marks\MarksHistory;
use App\Services\Marks\ReleasedMarksLock;
use App\Services\Records\SnapshotHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Marks stay in workbook order, every change is logged, a release locks the
 * sitting, and a snapshot can be checked against the hash written with it.
 */
class MarksHistoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_marks_are_ordered_by_year_then_term_then_set_then_class_then_subject(): void
    {
        $school = $this->school('Ledger School');
        $other = $this->school('Other Ledger School');
        $teacher = $this->person($school, 5);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'Lower', 'order' => 1, 'status' => 1, 'grading_style' => 'aggregate',
        ]);
        $yearEarly = $this->year($school, '2025', '2025-01-06');
        $yearLate = $this->year($school, '2026', '2026-01-06');
        $term2025 = $this->term($school, $yearEarly, 'First', '2025-02-01');
        $termEarly = $this->term($school, $yearLate, 'Opening', '2026-01-15');
        $termLate = $this->term($school, $yearLate, 'Closing', '2026-05-01');
        $formA = Section::create(['school_id' => $school->id, 'name' => 'Form A', 'status' => 1]);
        $formB = Section::create(['school_id' => $school->id, 'name' => 'Form B', 'status' => 1]);
        $setA = ExamSet::create([
            'school_id' => $school->id, 'academic_year_id' => $yearLate->id, 'academic_term_id' => $termLate->id, 'created_by' => $teacher->id,
        ]);
        $setB = ExamSet::create([
            'school_id' => $school->id, 'academic_year_id' => $yearLate->id, 'academic_term_id' => $termLate->id, 'created_by' => $teacher->id,
        ]);
        $typeId = $this->examType();
        $artA = $this->subject($school, $yearLate, $standard, $formA, 'Art', 'ARTA');
        $bioA = $this->subject($school, $yearLate, $standard, $formA, 'Biology', 'BIOA');
        $artB = $this->subject($school, $yearLate, $standard, $formB, 'Art', 'ARTB');
        $bioB = $this->subject($school, $yearLate, $standard, $formB, 'Biology', 'BIOB');

        $kept = [];
        $kept[] = $this->placed($school, $teacher, $standard, $formB, $yearLate, $termLate, $typeId, $bioB, $setB->id);
        $kept[] = $this->placed($school, $teacher, $standard, $formA, $yearEarly, $term2025, $typeId, $artA, null);
        $kept[] = $this->placed($school, $teacher, $standard, $formA, $yearLate, $termLate, $typeId, $artA, null);
        $kept[] = $this->placed($school, $teacher, $standard, $formA, $yearLate, $termEarly, $typeId, $artA, $setA->id);
        $kept[] = $this->placed($school, $teacher, $standard, $formB, $yearLate, $termLate, $typeId, $artB, $setB->id);
        $kept[] = $this->placed($school, $teacher, $standard, $formB, $yearLate, $termLate, $typeId, $artB, $setA->id);
        $kept[] = $this->placed($school, $teacher, $standard, $formA, $yearLate, $termLate, $typeId, $bioA, $setB->id);

        $zed = $this->subject($school, $yearLate, $standard, $formA, 'Zed', 'ZED');
        $droppedSubject = $this->placed($school, $teacher, $standard, $formA, $yearLate, $termLate, $typeId, $zed, $setA->id);
        $zed->delete();

        $droppedSection = Section::create(['school_id' => $school->id, 'name' => 'Form Z', 'status' => 1]);
        $artZ = $this->subject($school, $yearLate, $standard, $droppedSection, 'Art', 'ARTZ');
        $droppedSectionMark = $this->placed($school, $teacher, $standard, $droppedSection, $yearLate, $termLate, $typeId, $artZ, $setA->id);
        $droppedSection->delete();

        $civics = $this->subject($school, $yearLate, $standard, $formA, 'Civics', 'CIV');
        $droppedExamMark = $this->placed($school, $teacher, $standard, $formA, $yearLate, $termLate, $typeId, $civics, $setA->id);
        DB::table('exams')->where('id', $droppedExamMark->exam_id)->update(['deleted_at' => now()]);

        $otherTeacher = $this->person($other, 5);
        $otherStandard = Standard::create([
            'school_id' => $other->id, 'name' => 'Lower', 'order' => 1, 'status' => 1, 'grading_style' => 'aggregate',
        ]);
        $otherYear = $this->year($other, '2026', '2026-01-06');
        $otherTerm = $this->term($other, $otherYear, 'Opening', '2026-01-15');
        $otherSection = Section::create(['school_id' => $other->id, 'name' => 'Form A', 'status' => 1]);
        $otherArt = $this->subject($other, $otherYear, $otherStandard, $otherSection, 'Art', 'ARTO');
        $otherMark = $this->placed($other, $otherTeacher, $otherStandard, $otherSection, $otherYear, $otherTerm, $typeId, $otherArt, null);

        $order = [
            $kept[1]->id,
            $kept[3]->id,
            $kept[5]->id,
            $kept[6]->id,
            $kept[4]->id,
            $kept[0]->id,
            $kept[2]->id,
        ];

        $this->assertSame($order, app(MarksHistory::class)->forSchool($school->id)->pluck('id')->all());
        $this->assertNotContains($otherMark->id, app(MarksHistory::class)->forSchool($school->id)->pluck('id')->all());
        $this->assertNotContains($droppedSubject->id, app(MarksHistory::class)->forSchool($school->id)->pluck('id')->all());
        $this->assertNotContains($droppedSectionMark->id, app(MarksHistory::class)->forSchool($school->id)->pluck('id')->all());
        $this->assertNotContains($droppedExamMark->id, app(MarksHistory::class)->forSchool($school->id)->pluck('id')->all());
    }

    public function test_entering_and_changing_a_mark_is_logged_with_the_old_and_new_value(): void
    {
        $ctx = $this->sitting();
        $writer = app(ExamMarkWriter::class);

        $row = $writer->save($ctx['teacher'], $ctx['exam'], $ctx['student']->id, 70);

        $this->assertSame((int) $ctx['exam']->teacher_id, (int) $row->teacher_id);
        $entered = ActivityLog::query()->where('description', 'marks.entered')->sole();
        $this->assertSame((int) $ctx['teacher']->id, (int) $entered->causer_id);
        $this->assertNull($entered->properties['old_value']);
        $this->assertSame('70', $entered->properties['new_value']);
        $this->assertSame((int) $ctx['student']->id, (int) $entered->properties['student_id']);
        $this->assertSame((int) $ctx['exam']->id, (int) $entered->properties['exam_id']);
        $this->assertArrayNotHasKey('name', $entered->properties);

        $writer->save($ctx['teacher'], $ctx['exam'], $ctx['student']->id, '70');
        $this->assertSame(1, ActivityLog::query()->where('description', 'marks.entered')->count());
        $this->assertSame(0, ActivityLog::query()->where('description', 'marks.changed')->count());

        $writer->save($ctx['admin'], $ctx['exam'], $ctx['student']->id, 'AB');
        $changed = ActivityLog::query()->where('description', 'marks.changed')->sole();
        $this->assertSame((int) $ctx['admin']->id, (int) $changed->properties['actor_id']);
        $this->assertSame('70', $changed->properties['old_value']);
        $this->assertSame('ABS', $changed->properties['new_value']);
        $stored = $row->fresh();
        $this->assertNull($stored->marks);
        $this->assertSame('AB', $stored->grade);
        $this->assertTrue($stored->isAbsent());
        $this->assertSame((int) $ctx['exam']->teacher_id, (int) $stored->teacher_id);

        $writer->save($ctx['teacher'], $ctx['exam'], $ctx['student']->id, 80.5);
        $again = ActivityLog::query()->where('description', 'marks.changed')->orderByDesc('id')->first();
        $this->assertSame('ABS', $again->properties['old_value']);
        $this->assertSame('80.5', $again->properties['new_value']);
    }

    public function test_a_released_sitting_stays_locked_until_an_admin_or_head_teacher_reopens_it(): void
    {
        $ctx = $this->sitting();
        $writer = app(ExamMarkWriter::class);
        $lock = app(ReleasedMarksLock::class);

        $gone = $this->exam($ctx, $ctx['sectionA'], $ctx['art'], null);
        $this->release($ctx['school'], $gone, 'class', MarksRelease::STATUS_RELEASED, $ctx['admin']);
        DB::table('exams')->where('id', $gone->id)->update(['deleted_at' => now()]);
        $writer->save($ctx['teacher'], $ctx['exam'], $ctx['student']->id, 40);

        $otherReleaseExam = $this->examFor($ctx['other'], $ctx['otherTeacher'], $ctx['otherStandard'], $ctx['otherSection'], $ctx['otherYear'], $ctx['otherTerm'], $ctx['typeId'], $ctx['otherSubject']);
        $this->release($ctx['other'], $otherReleaseExam, 'school', MarksRelease::STATUS_RELEASED, $ctx['otherHead']);
        $writer->save($ctx['teacher'], $ctx['exam'], $ctx['student']->id, 41);

        $bare = new Exam([
            'school_id' => $ctx['school']->id,
            'section_id' => $ctx['sectionA']->id,
            'academic_term_id' => $ctx['term']->id,
        ]);
        $this->assertFalse($lock->isLocked($bare));

        $classRelease = $this->release($ctx['school'], $ctx['exam'], 'class', MarksRelease::STATUS_RELEASED, $ctx['admin']);
        $this->assertLocked($writer, $ctx['teacher'], $ctx['exam'], $ctx['student']);
        $this->assertLocked($writer, $ctx['teacher'], $ctx['biologyExam'], $ctx['student']);
        $this->assertLocked($writer, $ctx['admin'], $ctx['exam'], $ctx['student']);
        $writer->save($ctx['teacher'], $ctx['otherClassExam'], $ctx['student']->id, 50);
        $this->assertSame(41.0, (float) $ctx['exam']->marks()->where('student_id', $ctx['student']->id)->value('marks'));

        $this->assertRefused($lock, $ctx['teacher'], $classRelease, 'own school', false);
        $this->assertRefused($lock, $ctx['otherHead'], $classRelease, 'own school', true);
        $outsider = new User;
        $outsider->usergroup_id = 1;
        $outsider->school_id = 0;
        $this->assertRefused($lock, $outsider, $classRelease, 'own school', true);

        try {
            $lock->reopen($ctx['head'], $classRelease, 'too short');
            $this->fail('a short reason must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }

        $scheduled = $this->release($ctx['school'], $ctx['biologyExam'], 'class', MarksRelease::STATUS_SCHEDULED, $ctx['admin']);
        try {
            $lock->reopen($ctx['head'], $scheduled, 'This row was never released.');
            $this->fail('a scheduled release cannot be reopened');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }

        $lock->reopen($ctx['head'], $classRelease, 'The class needs one corrected mark.');
        $classRelease->refresh();
        $this->assertSame(MarksRelease::STATUS_REOPENED, $classRelease->status);
        $reopened = ActivityLog::query()->where('description', 'marks.release.reopened')->sole();
        $this->assertSame((int) $ctx['head']->id, (int) $reopened->causer_id);
        $this->assertSame('reopened', $reopened->properties['action']);
        $this->assertSame(MarksRelease::STATUS_RELEASED, $reopened->properties['old_value']);
        $this->assertSame(MarksRelease::STATUS_REOPENED, $reopened->properties['new_value']);
        $this->assertSame('The class needs one corrected mark.', $reopened->properties['reason']);
        $this->assertSame((int) $ctx['exam']->id, (int) $reopened->properties['exam_id']);

        $writer->save($ctx['teacher'], $ctx['exam'], $ctx['student']->id, 55);
        $this->assertSame(55.0, (float) $ctx['exam']->marks()->where('student_id', $ctx['student']->id)->value('marks'));

        $this->release($ctx['school'], $ctx['otherClassExam'], 'school', MarksRelease::STATUS_RELEASED, $ctx['admin']);
        $this->assertLocked($writer, $ctx['head'], $ctx['exam'], $ctx['student']);
        $this->assertLocked($writer, $ctx['teacher'], $ctx['otherClassExam'], $ctx['student']);
    }

    public function test_a_snapshot_hash_ignores_key_order_and_a_missing_hash_does_not_match(): void
    {
        $first = ['b' => 1, 'a' => ['z' => 2, 'y' => [3, 1]]];
        $reordered = ['a' => ['y' => [3, 1], 'z' => 2], 'b' => 1];

        $this->assertSame(SnapshotHasher::hash($first), SnapshotHasher::hash($reordered));
        $this->assertNotSame(SnapshotHasher::hash($first), SnapshotHasher::hash(['a' => 1, 'b' => 2]));

        $snapshot = new RecordSnapshot(['payload' => $first]);
        $this->assertFalse($snapshot->contentMatches());
        $snapshot->content_hash = SnapshotHasher::hash($first);
        $this->assertTrue($snapshot->contentMatches());
        $snapshot->payload = ['tampered' => true];
        $this->assertFalse($snapshot->contentMatches());
    }

    /**
     * @return array<string, mixed>
     */
    private function sitting(): array
    {
        $school = $this->school('Lock School');
        $other = $this->school('Other Lock School');
        $teacher = $this->person($school, 5);
        $admin = $this->person($school, 3);
        $head = $this->person($school, 4);
        $otherHead = $this->person($other, 4);
        $otherTeacher = $this->person($other, 5);
        $student = $this->person($school, 6);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'Lower', 'order' => 1, 'status' => 1, 'grading_style' => 'aggregate',
        ]);
        $otherStandard = Standard::create([
            'school_id' => $other->id, 'name' => 'Lower', 'order' => 1, 'status' => 1, 'grading_style' => 'aggregate',
        ]);
        $year = $this->year($school, '2026', '2026-01-06');
        $otherYear = $this->year($other, '2026', '2026-01-06');
        $term = $this->term($school, $year, 'Opening', '2026-01-15');
        $otherTerm = $this->term($other, $otherYear, 'Opening', '2026-01-15');
        $sectionA = Section::create(['school_id' => $school->id, 'name' => 'Form A', 'status' => 1]);
        $sectionB = Section::create(['school_id' => $school->id, 'name' => 'Form B', 'status' => 1]);
        $otherSection = Section::create(['school_id' => $other->id, 'name' => 'Form A', 'status' => 1]);
        $typeId = $this->examType();
        $art = $this->subject($school, $year, $standard, $sectionA, 'Art', 'ART');
        $biology = $this->subject($school, $year, $standard, $sectionA, 'Biology', 'BIO');
        $artB = $this->subject($school, $year, $standard, $sectionB, 'Art', 'ARTB');
        $otherSubject = $this->subject($other, $otherYear, $otherStandard, $otherSection, 'Art', 'ARTA');

        $ctx = [
            'school' => $school,
            'other' => $other,
            'teacher' => $teacher,
            'admin' => $admin,
            'head' => $head,
            'otherHead' => $otherHead,
            'otherTeacher' => $otherTeacher,
            'student' => $student,
            'otherStandard' => $otherStandard,
            'year' => $year,
            'otherYear' => $otherYear,
            'term' => $term,
            'otherTerm' => $otherTerm,
            'sectionA' => $sectionA,
            'otherSection' => $otherSection,
            'typeId' => $typeId,
            'art' => $art,
            'otherSubject' => $otherSubject,
        ];
        $ctx['exam'] = $this->exam($ctx, $sectionA, $art, null);
        $ctx['biologyExam'] = $this->exam($ctx, $sectionA, $biology, null);
        $ctx['otherClassExam'] = $this->exam($ctx, $sectionB, $artB, null);

        return $ctx;
    }

    private function assertLocked(ExamMarkWriter $writer, User $actor, Exam $exam, User $student): void
    {
        try {
            $writer->save($actor, $exam, $student->id, 99);
            $this->fail('a released sitting must refuse the write');
        } catch (MarksLockedException $e) {
            $this->assertSame(
                'These marks are released. An admin or head teacher has to reopen them before they can change.',
                $e->getMessage(),
            );
        }
    }

    private function assertRefused(ReleasedMarksLock $lock, User $actor, MarksRelease $release, string $message, bool $expectSchool): void
    {
        try {
            $lock->reopen($actor, $release, 'A reason that is long enough.');
            $this->fail('this person must not reopen the release');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            if ($expectSchool) {
                $this->assertStringContainsString($message, $e->getMessage());
            } else {
                $this->assertStringContainsString('head teacher', $e->getMessage());
            }
        }
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@example.test',
            'phone' => '07'.random_int(10000000, 99999999),
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Kenya',
        ]);
    }

    private function person(School $school, int $group): User
    {
        return User::factory()->create([
            'usergroup_id' => $group,
            'school_id' => $school->id,
            'email' => 'u.'.uniqid().'@example.test',
            'status' => 'active',
        ]);
    }

    private function year(School $school, string $name, string $start): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => $name,
            'description' => 'Year '.$name,
            'start_date' => $start,
            'end_date' => substr($start, 0, 4).'-12-15',
            'status' => 1,
        ]);
    }

    private function term(School $school, AcademicYear $year, string $name, string $starts): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'status' => 'current',
            'starts_on' => $starts,
            'ends_on' => substr($starts, 0, 4).'-12-01',
        ]);
    }

    private function subject(School $school, AcademicYear $year, Standard $standard, Section $section, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => $name,
            'code' => $code.'-'.uniqid(),
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function examType(): int
    {
        return (int) DB::table('exam_types')->insertGetId([
            'name' => 'End Of Term',
            'code' => 'EOT-'.uniqid(),
            'contributes_to_report_total' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function exam(array $ctx, Section $section, Subject $subject, ?int $setId): Exam
    {
        return $this->examFor($ctx['school'], $ctx['teacher'], $subject, $section, $ctx['year'], $ctx['term'], $ctx['typeId'], $subject, $setId);
    }

    private function examFor(
        School $school,
        User $teacher,
        Standard|Subject $standard,
        Section $section,
        AcademicYear $year,
        AcademicTerm $term,
        int $typeId,
        Subject $subject,
        ?int $setId = null,
    ): Exam {
        $standardId = $standard instanceof Standard ? $standard->id : $subject->standard_id;

        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standardId,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $typeId,
            'academic_term_id' => $term->id,
            'academic_year_id' => $year->id,
            'exam_set_id' => $setId,
            'status' => 'undone',
        ]));
    }

    private function placed(
        School $school,
        User $teacher,
        Standard $standard,
        Section $section,
        AcademicYear $year,
        AcademicTerm $term,
        int $typeId,
        Subject $subject,
        ?int $setId,
    ): Marks {
        $exam = $this->examFor($school, $teacher, $standard, $section, $year, $term, $typeId, $subject, $setId);
        $student = $this->person($school, 6);

        return Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'marks' => 10,
            'grade' => 'C',
            'absent' => false,
        ]);
    }

    private function release(School $school, Exam $exam, string $scope, string $status, User $actor): MarksRelease
    {
        return MarksRelease::create([
            'school_id' => $school->id,
            'exam_id' => $exam->id,
            'scope' => $scope,
            'standard_link_id' => null,
            'scheduled_at' => now()->subHour(),
            'released_at' => $status === MarksRelease::STATUS_RELEASED ? now() : null,
            'status' => $status,
            'created_by' => $actor->id,
            'timezone' => 'UTC',
        ]);
    }
}
