<?php

namespace Tests\Feature\Records;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\MarksRelease\MarksReleaseService;
use App\Services\Records\SnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifiable records, step 1: a release writes one immutable snapshot per
 * learner per term; a correction is a new version with a reason; a release that
 * cannot write its snapshots is not sent.
 */
class ReleaseSnapshotTest extends TestCase
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
        DB::table('exam_types')->upsert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        config(['queue.default' => 'database', 'services.whatsapp.replies_enabled' => true]);
    }

    /** @return array<string, mixed> */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Snapshot School', 'email' => 'snapshot-' . uniqid() . '@t.sch.ug',
            'phone' => '07' . random_int(10000000, 99999999), 'slug' => 'snapshot-' . uniqid(),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term III',
            'status' => 'current', 'starts_on' => '2026-08-24', 'ends_on' => '2026-11-28',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'Primary', 'order' => 1, 'status' => 1, 'grading_style' => 'aggregate',
        ]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.4', 'status' => 1]);
        $teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $school->id, 'email' => 't.' . uniqid() . '@t.sch.ug']);
        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $school->id, 'email' => 'a.' . uniqid() . '@t.sch.ug']);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => null, 'status' => 1,
        ]);

        $subjects = [];
        foreach (['Mathematics', 'English'] as $i => $name) {
            $subjects[] = Subject::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
                'section_id' => $section->id, 'name' => $name, 'code' => strtoupper(substr($name, 0, 3)),
                'type' => 'core', 'status' => 1, 'report_order' => $i + 1,
            ]);
        }

        $students = [];
        foreach (['First Learner' => 90, 'Second Learner' => 70] as $name => $mark) {
            $student = User::factory()->create([
                'usergroup_id' => 6, 'school_id' => $school->id, 'name' => $name,
                'email' => 's.' . uniqid() . '@t.sch.ug', 'status' => 'active',
            ]);
            StudentAcademic::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'user_id' => $student->id, 'standardLink_id' => $link->id,
            ]);
            $students[] = ['user' => $student, 'mark' => $mark];
        }

        $firstExam = null;
        foreach ($subjects as $subject) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 2,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-11-20 09:00:00', 'status' => 'done',
            ]));
            $firstExam ??= $exam;
            foreach ($students as $row) {
                Marks::create([
                    'student_id' => $row['user']->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                    'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                    'marks' => $row['mark'], 'grade' => 'stored',
                ]);
            }
            ExamMarksSubmission::create([
                'school_id' => $school->id, 'exam_id' => $exam->id, 'class_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'status' => 'locked',
                'approval_status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(),
            ]);
        }

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher', 'admin', 'subjects', 'students', 'firstExam');
    }

    /** @param array<string, mixed> $ctx */
    private function release(array $ctx, ?string $reason = null): MarksRelease
    {
        $service = app(MarksReleaseService::class);
        $release = $service->create($ctx['admin'], (int) $ctx['firstExam']->id, null, null, 'Africa/Kampala', false, false, $reason);
        $service->run((int) $release->id);

        return $release->fresh();
    }

    public function test_a_release_writes_one_snapshot_per_learner_for_the_term(): void
    {
        $ctx = $this->school();
        $release = $this->release($ctx);

        $this->assertSame(MarksRelease::STATUS_RELEASED, $release->status);
        $this->assertCount(2, RecordSnapshot::all());

        $first = RecordSnapshot::where('student_id', $ctx['students'][0]['user']->id)->sole();
        $this->assertSame(1, $first->version);
        $this->assertSame(RecordSnapshot::STATE_CURRENT, $first->state);
        $this->assertSame('P.4', trim((string) $first->payload['class']));
        $this->assertSame(180.0, (float) $first->payload['total']);
        $this->assertSame(2, count($first->payload['subjects']));
        $this->assertSame((int) $ctx['term']->id, (int) $first->academic_term_id);
        $this->assertSame((int) $release->id, (int) $first->release_id);
    }

    public function test_releasing_the_same_term_again_adds_a_version_and_supersedes_the_old_one(): void
    {
        $ctx = $this->school();
        $this->release($ctx);

        $first = RecordSnapshot::where('student_id', $ctx['students'][0]['user']->id)->sole();

        $second = $this->release($ctx, 'A mark was corrected after the first release.');
        $this->assertSame(MarksRelease::STATUS_RELEASED, $second->status);

        $rows = RecordSnapshot::where('student_id', $ctx['students'][0]['user']->id)->orderBy('version')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows[1]->version);
        $this->assertSame(RecordSnapshot::STATE_SUPERSEDED, $rows[0]->fresh()->state);
        $this->assertSame(RecordSnapshot::STATE_CURRENT, $rows[1]->state);
        $this->assertSame((int) $rows[0]->id, (int) $rows[1]->supersedes_id);
        $this->assertSame('A mark was corrected after the first release.', $rows[1]->reason);
    }

    public function test_a_re_release_from_the_screen_needs_a_reason_and_stores_it(): void
    {
        $ctx = $this->school();

        // A release now, so the sitting counts as already released.
        $payload = [
            'exam_id' => (int) $ctx['firstExam']->id,
            'standard_link_id' => null,
            'when' => 'now',
            'timezone' => 'Africa/Kampala',
        ];
        $this->actingAs($ctx['admin'])->post('/admin/marks/releases', $payload)->assertRedirect();

        // The screen asks for a reason now that the sitting has been released.
        $this->actingAs($ctx['admin'])->get('/admin/marks/releases/new?exam_id=' . $ctx['firstExam']->id)
            ->assertOk()
            ->assertSee('data-testid="release-reason"', false);

        // Releasing again without one is refused.
        $this->actingAs($ctx['admin'])->post('/admin/marks/releases', $payload)
            ->assertSessionHasErrors('release_reason');

        // With one, it lands on the new version.
        $this->actingAs($ctx['admin'])->post('/admin/marks/releases', $payload + [
            'release_reason' => 'Corrected the Science mark for one learner.',
        ])->assertSessionHasNoErrors();

        $latest = RecordSnapshot::where('student_id', $ctx['students'][0]['user']->id)->orderByDesc('version')->first();
        $this->assertSame('Corrected the Science mark for one learner.', $latest->reason);
    }

    public function test_a_failed_snapshot_fails_the_release_and_sends_nothing(): void
    {
        $ctx = $this->school();

        $this->mock(SnapshotService::class, function ($mock): void {
            $mock->shouldReceive('capture')->andThrow(new \RuntimeException('snapshot store unavailable'));
        });

        $release = $this->release($ctx);

        $this->assertSame('failed', $release->status);
        $this->assertSame(0, RecordSnapshot::count());
        $this->assertSame(0, DB::table('jobs')->count(), 'nothing is queued for a failed release');
    }
}
