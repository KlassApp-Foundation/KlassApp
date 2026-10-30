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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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

    public function test_re_release_reason_is_required_and_shown_for_the_class_however_it_is_picked(): void
    {
        $ctx = $this->school();
        $this->addClass($ctx, 'P.5');
        $admin = $ctx['admin'];
        $examId = (int) $ctx['firstExam']->id;
        $linkId = (int) $ctx['link']->id;
        $base = '/admin/marks/releases/new?exam_id='.$examId;

        $this->actingAs($admin)->get($base.'&class=P.4')
            ->assertOk()
            ->assertDontSee('data-testid="release-reason"', false);
        $this->actingAs($admin)->get($base.'&standard_link_id='.$linkId)
            ->assertOk()
            ->assertDontSee('data-testid="release-reason"', false);

        // Class picker: class and stream, no standard_link_id.
        $this->actingAs($admin)->post('/admin/marks/releases', $this->payload($ctx, [
            'class' => 'P.4',
            'stream' => '',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($admin)->get($base.'&class=P.4&stream=')
            ->assertOk()
            ->assertSee('data-testid="release-reason"', false);
        $this->actingAs($admin)->get($base.'&class=P.5')
            ->assertOk()
            ->assertDontSee('data-testid="release-reason"', false);

        // Pre-selected by the class link on the URL.
        $this->actingAs($admin)->get($base.'&standard_link_id='.$linkId)
            ->assertOk()
            ->assertSee('data-testid="release-reason"', false);

        // Changed after load. The class control reloads this page; all classes
        // has no box, and choosing the released class shows it.
        $this->actingAs($admin)->get($base)
            ->assertOk()
            ->assertDontSee('data-testid="release-reason"', false)
            ->assertSee('name="class"', false)
            ->assertSee('onchange="this.form.submit()"', false);
        $this->actingAs($admin)->get($base.'&class=P.4')
            ->assertOk()
            ->assertSee('data-testid="release-reason"', false);

        // The previous URL had no class, but saving the released class demands
        // a reason and the error is on the page, with the box.
        $this->actingAs($admin)->from($base)->post('/admin/marks/releases', $this->payload($ctx, [
            'class' => 'P.4',
            'stream' => '',
        ]))->assertSessionHasErrors('release_reason');
        $this->actingAs($admin)->get($base)
            ->assertOk()
            ->assertSee('data-testid="release-reason"', false)
            ->assertSee('data-testid="release-reason-error"', false);

        $this->actingAs($admin)->from($base.'&standard_link_id='.$linkId)
            ->post('/admin/marks/releases', $this->payload($ctx, [
                'standard_link_id' => $linkId,
                'class' => '',
                'stream' => '',
            ]))->assertSessionHasErrors('release_reason');

        $this->actingAs($admin)->post('/admin/marks/releases', $this->payload($ctx, [
            'class' => 'P.5',
            'stream' => '',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post('/admin/marks/releases', $this->payload($ctx, [
            'class' => 'P.4',
            'stream' => '',
            'release_reason' => 'Corrected one mark after the class release.',
        ]))->assertSessionHasNoErrors();

        $latest = RecordSnapshot::query()
            ->where('school_id', $ctx['school']->id)
            ->where('student_id', $ctx['students'][0]['user']->id)
            ->orderByDesc('version')
            ->first();
        $this->assertSame('Corrected one mark after the class release.', $latest->reason);
    }

    public function test_deleting_a_student_school_year_or_term_does_not_remove_released_snapshots(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $ctx = $this->school();
        $this->release($ctx);
        $kept = RecordSnapshot::query()
            ->where('school_id', $ctx['school']->id)
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $kept);

        foreach (['student_id', 'school_id', 'academic_year_id', 'academic_term_id'] as $column) {
            $this->assertSame('RESTRICT', $this->snapshotOnDelete($column), $column);
        }

        foreach ([
            'users' => (int) $ctx['students'][0]['user']->id,
            'academic_terms' => (int) $ctx['term']->id,
            'academic_years' => (int) $ctx['year']->id,
            'schools' => (int) $ctx['school']->id,
        ] as $table => $id) {
            try {
                DB::table($table)->where('id', $id)->delete();
            } catch (\Illuminate\Database\QueryException) {
                // RESTRICT refuses the parent delete. The snapshot stays.
            }

            $still = RecordSnapshot::query()
                ->where('school_id', $ctx['school']->id)
                ->orderBy('id')
                ->get();
            $this->assertCount(2, $still, $table);
            foreach ($kept as $i => $before) {
                $this->assertSame((int) $before->id, (int) $still[$i]->id, $table);
                $this->assertSame((int) $before->student_id, (int) $still[$i]->student_id, $table);
                $this->assertSame((int) $before->school_id, (int) $still[$i]->school_id, $table);
                $this->assertSame((int) $before->academic_year_id, (int) $still[$i]->academic_year_id, $table);
                $this->assertSame((int) $before->academic_term_id, (int) $still[$i]->academic_term_id, $table);
            }
        }
    }

    public function test_snapshot_rollback_does_not_drop_released_records_without_the_flag(): void
    {
        $ctx = $this->school();
        $this->release($ctx);
        $count = RecordSnapshot::query()->where('school_id', $ctx['school']->id)->count();
        config(['klassapp.records.allow_snapshot_drop' => false]);

        $migration = require base_path('database/migrations/2026_10_27_120000_create_record_snapshots_table.php');
        try {
            $migration->down();
            $this->fail('Rollback dropped record_snapshots without RECORD_SNAPSHOTS_ALLOW_DROP.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('RECORD_SNAPSHOTS_ALLOW_DROP', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('record_snapshots'));
        $this->assertSame($count, RecordSnapshot::query()->where('school_id', $ctx['school']->id)->count());
    }

    public function test_snapshot_rollback_drops_the_table_only_when_the_flag_is_set(): void
    {
        config(['klassapp.records.allow_snapshot_drop' => true]);
        $migration = require base_path('database/migrations/2026_10_27_120000_create_record_snapshots_table.php');

        try {
            $migration->down();
            $this->assertFalse(Schema::hasTable('record_snapshots'));
        } finally {
            config(['klassapp.records.allow_snapshot_drop' => false]);
            if (! Schema::hasTable('record_snapshots')) {
                $migration->up();
            }
        }

        $this->assertTrue(Schema::hasTable('record_snapshots'));
        foreach (['student_id', 'school_id', 'academic_year_id', 'academic_term_id'] as $column) {
            $this->assertSame('RESTRICT', $this->snapshotOnDelete($column), $column);
        }
    }

    public function test_demo_webhook_is_not_called_when_the_release_transaction_rolls_back(): void
    {
        $ctx = $this->school();
        $ctx['school']->forceFill(['is_demo' => 1])->save();
        config([
            'klassapp.demo.release_webhook_url' => 'https://hooks.example.com/n8n/release',
            'klassapp.demo.release_webhook_secret' => 'test-only-webhook-secret',
            'services.whatsapp.replies_enabled' => true,
        ]);
        Http::fake([
            'hooks.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $this->mock(SnapshotService::class, function ($mock): void {
            $mock->shouldReceive('capture')->once()->andThrow(new \RuntimeException('snapshot store unavailable'));
        });

        $release = $this->release($ctx);

        $this->assertSame('failed', $release->status);
        $calls = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'hooks.example.com'));
        $this->assertCount(0, $calls);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    /** @param array<string, mixed> $ctx
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function payload(array $ctx, array $extra = []): array
    {
        return $extra + [
            'exam_id' => (int) $ctx['firstExam']->id,
            'when' => 'now',
            'timezone' => 'Africa/Kampala',
        ];
    }

    /** @param array<string, mixed> $ctx
     * @return array{section: Section, link: StandardLink, student: User, exam: Exam}
     */
    private function addClass(array $ctx, string $name): array
    {
        $section = Section::create(['school_id' => $ctx['school']->id, 'name' => $name, 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $section->id,
            'class_teacher_id' => $ctx['teacher']->id,
            'stream' => null,
            'status' => 1,
        ]);
        $subject = Subject::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $section->id,
            'name' => 'Science',
            'code' => 'SCI',
            'type' => 'core',
            'status' => 1,
            'report_order' => 1,
        ]);
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $ctx['school']->id,
            'name' => $name.' Learner',
            'email' => 's.'.uniqid().'@example.com',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);
        $exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => 2,
            'academic_term_id' => $ctx['term']->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => '2026-11-20 09:00:00',
            'status' => 'done',
        ]));
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $section->id,
            'marks' => 80,
            'grade' => 'stored',
        ]);
        ExamMarksSubmission::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $exam->id,
            'class_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'status' => 'locked',
            'approval_status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        return compact('section', 'link', 'student', 'exam');
    }

    private function snapshotOnDelete(string $column): string
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::select("PRAGMA foreign_key_list('record_snapshots')") as $fk) {
                if (($fk->from ?? null) === $column) {
                    return strtoupper((string) $fk->on_delete);
                }
            }
        } else {
            $row = DB::selectOne(
                'select rc.DELETE_RULE as rule
                 from information_schema.KEY_COLUMN_USAGE kcu
                 join information_schema.REFERENTIAL_CONSTRAINTS rc
                   on rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                  and rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                  and rc.TABLE_NAME = kcu.TABLE_NAME
                 where kcu.TABLE_SCHEMA = database()
                   and kcu.TABLE_NAME = ?
                   and kcu.COLUMN_NAME = ?
                   and kcu.REFERENCED_TABLE_NAME is not null
                 limit 1',
                ['record_snapshots', $column]
            );
            if ($row) {
                return strtoupper((string) $row->rule);
            }
        }

        $this->fail('No foreign key on record_snapshots.'.$column);
    }
}
