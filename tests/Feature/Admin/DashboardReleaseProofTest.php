<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\DashboardV2DataService;
use App\Services\ReportCards\EndOfTermCompositionLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Three regression proofs for DashboardV2DataService and EndOfTermCompositionLine:
 *
 *   (a) Bug 1 — releasedLearnerCount() must NOT count a section that has a
 *       blocking set (a counting exam type with marks entered but not released),
 *       even if another counting set has already been released for that section.
 *
 *   (b) Bug 2 — capturedComposition() must find the frozen mix when the release
 *       is anchored on a different subject's exam than the one the caller passes
 *       (i.e. lookup by exam set, not by exact exam_id).
 *
 *   (c) Bug 3 — classesReadyToRelease() must subtract EVERY section that has a
 *       given exam set + term when the release is school-wide, not only the
 *       section of the anchor exam.
 */
class DashboardReleaseProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (a) blocked class not counted as having released cards (Bug 1)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * EOT is released for P.5. TermTest has marks entered but is NOT released.
     * blockingSets() returns [TermTest], so the section must be excluded from
     * releasedLearnerCount() even though EOT itself counts toward the total.
     */
    public function test_blocked_section_excluded_from_released_learner_count(): void
    {
        $ctx = $this->schoolWithTwoCountingTypes();

        // Student enrolled in P.5.
        $student = $this->enroll($ctx, 'Blocked Student');

        // Enter marks for BOTH types so blockingSets can detect them.
        Marks::create([
            'student_id' => $student->id, 'exam_id' => $ctx['eot_exam']->id,
            'school_id' => $ctx['school']->id, 'subject_id' => $ctx['subject']->id,
            'teacher_id' => $ctx['teacher']->id, 'section_id' => $ctx['section']->id,
            'marks' => 80, 'grade' => 'B',
        ]);
        Marks::create([
            'student_id' => $student->id, 'exam_id' => $ctx['tt_exam']->id,
            'school_id' => $ctx['school']->id, 'subject_id' => $ctx['subject']->id,
            'teacher_id' => $ctx['teacher']->id, 'section_id' => $ctx['section']->id,
            'marks' => 70, 'grade' => 'B',
        ]);

        // Release EOT (school-wide) — the class appears released for one counting set.
        MarksRelease::create([
            'school_id' => $ctx['school']->id, 'exam_id' => $ctx['eot_exam']->id,
            'scope' => 'school', 'status' => MarksRelease::STATUS_RELEASED,
            'released_at' => now(), 'scheduled_at' => now()->subHour(), 'timezone' => 'UTC',
        ]);

        // TermTest is NOT released — blockingSets() must flag it.
        $blocking = app(EndOfTermCompositionLine::class)
            ->blockingSets((int) $ctx['school']->id, (int) $ctx['section']->id, (int) $ctx['term']->id);
        $this->assertNotEmpty($blocking, 'blockingSets should detect the unreleased TermTest');

        // releasedLearnerCount must return 0 because the section is blocked.
        $count = app(DashboardV2DataService::class)->releasedLearnerCount((int) $ctx['school']->id);
        $this->assertSame(0, $count, 'blocked section must not be counted as having released cards');
    }

    /**
     * When the blocking set is also released the section is no longer blocked
     * and the learner IS counted.
     */
    public function test_unblocked_section_is_counted_after_all_sets_released(): void
    {
        $ctx = $this->schoolWithTwoCountingTypes();
        $student = $this->enroll($ctx, 'Unblocked Student');

        Marks::create([
            'student_id' => $student->id, 'exam_id' => $ctx['eot_exam']->id,
            'school_id' => $ctx['school']->id, 'subject_id' => $ctx['subject']->id,
            'teacher_id' => $ctx['teacher']->id, 'section_id' => $ctx['section']->id,
            'marks' => 80, 'grade' => 'B',
        ]);
        Marks::create([
            'student_id' => $student->id, 'exam_id' => $ctx['tt_exam']->id,
            'school_id' => $ctx['school']->id, 'subject_id' => $ctx['subject']->id,
            'teacher_id' => $ctx['teacher']->id, 'section_id' => $ctx['section']->id,
            'marks' => 70, 'grade' => 'B',
        ]);

        // Release BOTH sets.
        foreach ([$ctx['eot_exam'], $ctx['tt_exam']] as $exam) {
            MarksRelease::create([
                'school_id' => $ctx['school']->id, 'exam_id' => $exam->id,
                'scope' => 'school', 'status' => MarksRelease::STATUS_RELEASED,
                'released_at' => now(), 'scheduled_at' => now()->subHour(), 'timezone' => 'UTC',
            ]);
        }

        $count = app(DashboardV2DataService::class)->releasedLearnerCount((int) $ctx['school']->id);
        $this->assertSame(1, $count, 'unblocked section with one active student should count 1');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (b) release on non-first exam of set found by set lookup (Bug 2)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Two subjects (English, Maths) produce two exams for the same EOT set.
     * The release is anchored on the HIGHER-id exam (Maths). The fixed
     * capturedComposition() must find it even when called with the lower-id exam
     * (English) — and return the same composition that the class report screen
     * would use.
     */
    public function test_captured_composition_found_via_set_regardless_of_anchor_exam(): void
    {
        $ctx = $this->schoolWithOneEotType();

        // English gets a lower id than Maths because it is created first.
        $english = Subject::create([
            'school_id' => $ctx['school']->id, 'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id, 'section_id' => $ctx['section']->id,
            'name' => 'English', 'code' => 'ENG-'.uniqid(), 'type' => 'core', 'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $ctx['school']->id, 'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id, 'section_id' => $ctx['section']->id,
            'name' => 'Maths', 'code' => 'MTC-'.uniqid(), 'type' => 'core', 'status' => 1,
        ]);

        $engExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id, 'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id, 'subject_id' => $english->id,
            'teacher_id' => $ctx['teacher']->id, 'exam_type_id' => $ctx['eot_type_id'],
            'academic_term_id' => $ctx['term']->id, 'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => '2026-07-01', 'status' => 'submitted',
        ]));
        $mthExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id, 'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id, 'subject_id' => $maths->id,
            'teacher_id' => $ctx['teacher']->id, 'exam_type_id' => $ctx['eot_type_id'],
            'academic_term_id' => $ctx['term']->id, 'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => '2026-07-01', 'status' => 'submitted',
        ]));

        // English exam must have the lower id (created first).
        $this->assertLessThan((int) $mthExam->id, (int) $engExam->id, 'English exam id must be lower');

        // Store a sentinel composition on the release anchored on the MATHS exam.
        // 'proof' is a unique marker; weight uses an integer to survive JSON round-trip.
        $sentinelComposition = [(string) $ctx['eot_type_id'] => ['include' => true, 'weight' => 100, 'proof' => 'sentinel']];
        MarksRelease::create([
            'school_id' => $ctx['school']->id, 'exam_id' => $mthExam->id,
            'scope' => 'school', 'status' => MarksRelease::STATUS_RELEASED,
            'released_at' => now(), 'scheduled_at' => now()->subHour(), 'timezone' => 'UTC',
            'report_composition' => $sentinelComposition,
        ]);

        $composition = app(EndOfTermCompositionLine::class);

        // Calling with the MATHS exam id must find the composition (trivial sanity check).
        $fromMaths = $composition->compositionFor((int) $ctx['school']->id, (int) $mthExam->id);
        $this->assertNotNull($fromMaths, 'maths exam id should find the composition directly');
        $this->assertSame('sentinel', $fromMaths[(string) $ctx['eot_type_id']]['proof'] ?? null);

        // Calling with the ENGLISH exam id must also find the same composition (the fix).
        $fromEnglish = $composition->compositionFor((int) $ctx['school']->id, (int) $engExam->id);
        $this->assertNotNull($fromEnglish, 'english exam id must resolve the composition via set lookup');
        $this->assertSame('sentinel', $fromEnglish[(string) $ctx['eot_type_id']]['proof'] ?? null,
            'english exam id must find the same sentinel proof value as maths');

        // Both callers must receive structurally identical results.
        $this->assertEquals($fromMaths, $fromEnglish, 'both callers must receive the same frozen mix');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (c) school-wide release clears every section, not just the anchor (Bug 3)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Two sections (P.5 and P.6) both have a fully-approved EOT sitting.
     * A school-wide release is anchored on P.5's exam. classesReadyToRelease()
     * must return 0 — both sections are cleared — not 1 (P.6 still ready).
     * Tested via marksDesk()['ready'].
     */
    public function test_school_wide_release_clears_all_sections_with_that_set(): void
    {
        $ctx = $this->schoolWithOneEotType();

        // Create a second section P.6.
        $p6 = Section::create(['school_id' => $ctx['school']->id, 'name' => 'P.6', 'status' => 1]);

        // One subject per section.
        $subP5 = Subject::create([
            'school_id' => $ctx['school']->id, 'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id, 'section_id' => $ctx['section']->id,
            'name' => 'English P5', 'code' => 'EP5-'.uniqid(), 'type' => 'core', 'status' => 1,
        ]);
        $subP6 = Subject::create([
            'school_id' => $ctx['school']->id, 'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id, 'section_id' => $p6->id,
            'name' => 'English P6', 'code' => 'EP6-'.uniqid(), 'type' => 'core', 'status' => 1,
        ]);

        // EOT exam for each section in the same term.
        $examP5 = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id, 'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id, 'subject_id' => $subP5->id,
            'teacher_id' => $ctx['teacher']->id, 'exam_type_id' => $ctx['eot_type_id'],
            'academic_term_id' => $ctx['term']->id, 'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => '2026-07-01', 'status' => 'submitted',
        ]));
        $examP6 = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id, 'standard_id' => $ctx['standard']->id,
            'section_id' => $p6->id, 'subject_id' => $subP6->id,
            'teacher_id' => $ctx['teacher']->id, 'exam_type_id' => $ctx['eot_type_id'],
            'academic_term_id' => $ctx['term']->id, 'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => '2026-07-01', 'status' => 'submitted',
        ]));

        // Approve both — classesReadyToRelease sees them as ready before any release.
        foreach ([
            [$examP5, $ctx['section']->id, $subP5->id],
            [$examP6, $p6->id, $subP6->id],
        ] as [$exam, $classId, $subjectId]) {
            ExamMarksSubmission::create([
                'school_id' => $ctx['school']->id, 'exam_id' => $exam->id,
                'class_id' => $classId, 'subject_id' => $subjectId,
                'teacher_id' => $ctx['teacher']->id, 'status' => 'locked',
                'approval_status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(),
            ]);
        }

        $svc = app(DashboardV2DataService::class);

        // Before the release both sections show as ready.
        $before = $svc->marksDesk((int) $ctx['school']->id);
        $this->assertSame(2, $before['ready'], 'both sections should be ready before any release');

        // School-wide release anchored on P.5's exam only.
        MarksRelease::create([
            'school_id' => $ctx['school']->id, 'exam_id' => $examP5->id,
            'scope' => 'school', 'status' => MarksRelease::STATUS_RELEASED,
            'released_at' => now(), 'scheduled_at' => now()->subHour(), 'timezone' => 'UTC',
        ]);

        // After the school-wide release BOTH sections must be cleared (count = 0).
        $after = $svc->marksDesk((int) $ctx['school']->id);
        $this->assertSame(0, $after['ready'],
            'school-wide release must clear P.6 as well, not only the anchor section P.5');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared fixtures
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * School with two counting exam types: EOT and TermTest.
     * Provides one section (P.5), one subject, one exam per type, year + current term.
     *
     * @return array{school: School, year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section, link: StandardLink, teacher: User, subject: Subject, eot_exam: Exam, tt_exam: Exam}
     */
    private function schoolWithTwoCountingTypes(): array
    {
        $eotTypeId = DB::table('exam_types')->insertGetId([
            'name' => 'End of Term', 'code' => 'EOT-'.uniqid(),
            'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ttTypeId = DB::table('exam_types')->insertGetId([
            'name' => 'Term Test', 'code' => 'TT-'.uniqid(),
            'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $base = $this->makeBase();
        $subject = Subject::create([
            'school_id' => $base['school']->id, 'academic_year_id' => $base['year']->id,
            'standard_id' => $base['standard']->id, 'section_id' => $base['section']->id,
            'name' => 'English', 'code' => 'ENG-'.uniqid(), 'type' => 'core', 'status' => 1,
        ]);
        $eotExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $base['school']->id, 'standard_id' => $base['standard']->id,
            'section_id' => $base['section']->id, 'subject_id' => $subject->id,
            'teacher_id' => $base['teacher']->id, 'exam_type_id' => $eotTypeId,
            'academic_term_id' => $base['term']->id, 'academic_year_id' => $base['year']->id,
            'scheduled_at' => '2026-07-01', 'status' => 'submitted',
        ]));
        $ttExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $base['school']->id, 'standard_id' => $base['standard']->id,
            'section_id' => $base['section']->id, 'subject_id' => $subject->id,
            'teacher_id' => $base['teacher']->id, 'exam_type_id' => $ttTypeId,
            'academic_term_id' => $base['term']->id, 'academic_year_id' => $base['year']->id,
            'scheduled_at' => '2026-06-01', 'status' => 'submitted',
        ]));

        return array_merge($base, ['subject' => $subject, 'eot_exam' => $eotExam, 'tt_exam' => $ttExam]);
    }

    /**
     * School with a single counting EOT type, one section (P.5), no subjects/exams yet.
     *
     * @return array{school: School, year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section, link: StandardLink, teacher: User, eot_type_id: int}
     */
    private function schoolWithOneEotType(): array
    {
        $eotTypeId = DB::table('exam_types')->insertGetId([
            'name' => 'End of Term', 'code' => 'EOT-'.uniqid(),
            'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return array_merge($this->makeBase(), ['eot_type_id' => $eotTypeId]);
    }

    /**
     * @return array{school: School, year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section, link: StandardLink, teacher: User}
     */
    private function makeBase(): array
    {
        $school = School::create([
            'name' => 'Proof School '.uniqid(), 'slug' => 'proof-'.uniqid(),
            'email' => uniqid().'@proof.test', 'phone' => '07'.random_int(70000000, 78999999),
            'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Term 2', 'status' => 'current',
            'starts_on' => '2026-05-01', 'ends_on' => '2026-08-31',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id,
            'email' => 'teacher.'.uniqid().'@proof.test',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher');
    }

    private function enroll(array $ctx, string $name): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $ctx['school']->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@proof.test',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id, 'academic_year_id' => $ctx['year']->id,
            'user_id' => $student->id, 'standardLink_id' => $ctx['link']->id,
        ]);

        return $student;
    }
}
