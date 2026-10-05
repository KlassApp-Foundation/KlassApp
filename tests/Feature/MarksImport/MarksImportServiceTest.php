<?php

namespace Tests\Feature\MarksImport;

use App\Events\MarksUpdated;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Services\MarksImport\MarksImportBlocked;
use App\Services\MarksImport\MarksImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\MarksImportFixture;
use Tests\TestCase;

/** The marks import service, with no UI: preview saves nothing, commit is idempotent, nothing is dropped silently. */
class MarksImportServiceTest extends TestCase
{
    use MarksImportFixture;
    use RefreshDatabase;

    private MarksImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildMarksImportFixture();
        $this->service = app(MarksImportService::class);
    }

    private function outcomes($plan): array
    {
        return array_map(fn ($r) => $r['outcome'].($r['reason'] ? ':'.$r['reason'] : ''), $plan->rows);
    }

    public function test_preview_classifies_every_row_and_saves_nothing(): void
    {
        $this->saveMark($this->students[2], 50);   // will differ
        $this->saveMark($this->students[3], 70);   // will be unchanged

        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([
            ['KLS0000001', 'Amina Nakato', '80'],      // new
            ['KLS0000002', 'Brian Okello', '60'],      // differs (50 -> 60)
            ['KLS0000003', 'Chloe Mwesigwa', '70'],    // unchanged
            ['KLS0000004', 'Daniel Ssemakula', '55'],  // enrolled in another class
            ['KLS0000099', 'Nobody', '55'],            // unknown
        ]));

        $this->assertSame(
            ['new', 'update', 'unchanged', 'skipped:not_in_class', 'skipped:unknown_student'],
            $this->outcomes($plan),
        );
        $this->assertSame(['new' => 1, 'update' => 1, 'unchanged' => 1, 'skipped' => 2, 'total' => 5], $plan->counts());
        $this->assertSame(2, Marks::where('exam_id', $this->exam->id)->count(), 'preview must not write');
        $this->assertSame(0, ActivityLog::where('description', 'marks.imported')->count());
    }

    public function test_invalid_marks_are_skipped_with_a_reason_never_dropped(): void
    {
        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([
            ['KLS0000001', '', '101'],
            ['KLS0000002', '', '-1'],
            ['KLS0000003', '', 'absent'],
            ['KLS0000001', 'Amina Nakato', ''],     // blank mark (admission number still required to be unique below)
            [null, null, '55'],                     // no identifier
        ]));

        $this->assertSame(
            ['skipped:out_of_range', 'skipped:out_of_range', 'skipped:not_a_number', 'skipped:blank_mark', 'skipped:no_identifier'],
            $this->outcomes($plan),
        );
        $this->assertCount(5, $plan->rows, 'every input row is reported');
    }

    public function test_marks_accept_decimals_and_boundary_values(): void
    {
        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([
            ['KLS0000001', '', '0'], ['KLS0000002', '', '100'], ['KLS0000003', '', '72.5'],
        ]));

        $this->assertSame(['new', 'new', 'new'], $this->outcomes($plan));
        $this->assertSame(['0', '100', '72.5'], array_column($plan->rows, 'mark'));
    }

    public function test_names_match_only_when_exact_and_unique(): void
    {
        $twin = $this->makeStudent($this->school, 'Brian Okello', 'KLS0000010', \App\Models\StandardLink::first());

        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([
            [null, 'Amina Nakato', '80'],      // unique exact name: matched
            [null, 'mwesigwa CHLOE', '81'],     // case and word order do not matter, still exact
            [null, 'Brian Okello', '60'],      // two students share it: ambiguous, never guessed
            [null, 'Chloe Mwesigw', '70'],     // near miss: no fuzzy matching
        ]));

        $this->assertSame(['new', 'new', 'skipped:ambiguous_name', 'skipped:unknown_student'], $this->outcomes($plan));
        $this->assertNotNull($twin);
    }

    public function test_duplicate_rows_identical_keep_first_conflicting_skip_all(): void
    {
        $same = $this->service->preview($this->exam, $this->owner, $this->sheet([
            ['KLS0000001', '', '80'], ['KLS0000001', '', '80'],
        ]));
        $this->assertSame(['new', 'skipped:duplicate_in_file'], $this->outcomes($same));

        $conflict = $this->service->preview($this->exam, $this->owner, $this->sheet([
            ['KLS0000001', '', '80'], ['KLS0000001', '', '81'],
        ]));
        $this->assertSame(['skipped:duplicate_in_file', 'skipped:duplicate_in_file'], $this->outcomes($conflict));
    }

    public function test_commit_saves_with_grade_and_is_idempotent(): void
    {
        Event::fake([MarksUpdated::class]);
        $sheet = $this->sheet([['KLS0000001', '', '80'], ['KLS0000002', '', '60']]);

        $plan = $this->service->preview($this->exam, $this->owner, $sheet, 'marks.csv');
        $first = $this->service->commit($plan, $this->owner);

        $this->assertSame([2, 0, 0, 0], [$first->saved, $first->updated, $first->unchanged, $first->skipped]);
        $this->assertDatabaseHas('marks', ['exam_id' => $this->exam->id, 'student_id' => $this->students[1]->id, 'marks' => 80, 'grade' => 'A', 'teacher_id' => $this->owner->id, 'section_id' => $this->section->id]);
        $this->assertSame('done', $this->exam->fresh()->status);

        $second = $this->service->commit($this->service->preview($this->exam, $this->owner, $sheet, 'marks.csv'), $this->owner);
        $this->assertSame([0, 0, 2, 0], [$second->saved, $second->updated, $second->unchanged, $second->skipped]);

        // Committing the very same (stale) plan again is also a no-op: the plan is re-validated at commit.
        $third = $this->service->commit($plan, $this->owner);
        $this->assertSame([0, 0, 2], [$third->saved, $third->updated, $third->unchanged]);

        $this->assertSame(2, Marks::where('exam_id', $this->exam->id)->count(), 'no duplicates after three runs');
    }

    public function test_different_existing_mark_is_not_overwritten_unless_confirmed(): void
    {
        $this->saveMark($this->students[1], 50);
        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([['KLS0000001', '', '90']]));

        $kept = $this->service->commit($plan, $this->owner, overwrite: false);
        $this->assertSame([0, 0, 1], [$kept->saved, $kept->updated, $kept->skipped]);
        $this->assertSame('overwrite_not_confirmed', $kept->rows[0]['reason']);
        $this->assertEquals(50, Marks::where('student_id', $this->students[1]->id)->value('marks'));

        $changed = $this->service->commit($plan, $this->owner, overwrite: true);
        $this->assertSame([0, 1, 0], [$changed->saved, $changed->updated, $changed->skipped]);
        $this->assertEquals(90, Marks::where('student_id', $this->students[1]->id)->value('marks'));
        $this->assertSame(1, Marks::where('student_id', $this->students[1]->id)->count());
    }

    public function test_two_saved_marks_for_one_student_are_never_touched_or_added_to(): void
    {
        $this->saveMark($this->students[1], 50);
        $this->saveMark($this->students[1], 51);

        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([['KLS0000001', '', '90']]));
        $this->assertSame(['skipped:duplicate_existing'], $this->outcomes($plan));
        $this->service->commit($plan, $this->owner, overwrite: true);
        $this->assertSame(2, Marks::where('student_id', $this->students[1]->id)->count());
    }

    public function test_teacher_scope_owner_and_class_teacher_only(): void
    {
        $this->assertTrue($this->service->canImport($this->owner, $this->exam));
        $this->assertTrue($this->service->canImport($this->admin, $this->exam));
        $this->assertFalse($this->service->canImport($this->stranger, $this->exam));

        $this->section->update(['class_teacher_id' => $this->stranger->id]);
        $this->assertTrue($this->service->canImport($this->stranger->fresh(), $this->exam), 'class teacher of the exam class may import');

        $foreignAdmin = \App\Models\User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->otherSchoolStudent->school_id]);
        $this->assertFalse($this->service->canImport($foreignAdmin, $this->exam), 'another school is never allowed');
    }

    public function test_unauthorised_actor_gets_403_from_every_entry_point(): void
    {
        $outsider = \App\Models\User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id]);
        $rows = $this->sheet([['KLS0000001', '', '80']]);

        foreach ([
            fn () => $this->service->preview($this->exam, $outsider, $rows),
            fn () => $this->service->templateRows($this->exam, $outsider),
            fn () => $this->service->commit($this->service->preview($this->exam, $this->owner, $rows), $outsider),
        ] as $call) {
            try {
                $call();
                $this->fail('expected a 403');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame(0, Marks::count());
    }

    public function test_locked_submission_blocks_preview_and_commit(): void
    {
        ExamMarksSubmission::create([
            'school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'class_id' => $this->section->id,
            'subject_id' => $this->subject->id, 'teacher_id' => $this->owner->id, 'status' => 'locked',
        ]);

        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([['KLS0000001', '', '80']]));
        $this->assertTrue($plan->isBlocked());
        $this->assertArrayHasKey('locked', $plan->blockers);

        $this->expectException(MarksImportBlocked::class);
        try {
            $this->service->commit($plan, $this->owner);
        } finally {
            $this->assertSame(0, Marks::count());
        }
    }

    public function test_submitted_exam_needs_a_correction_reason(): void
    {
        $this->exam->update(['status' => 'submitted']);
        $plan = $this->service->preview($this->exam, $this->owner, $this->sheet([['KLS0000001', '', '80']]));
        $this->assertTrue($plan->requiresReason);

        try {
            $this->service->commit($plan, $this->owner, reason: 'too short');
            $this->fail('a short reason must be refused');
        } catch (MarksImportBlocked $e) {
            $this->assertSame('reason_required', $e->reasonCode);
        }
        $this->assertSame(0, Marks::count());

        $result = $this->service->commit($plan, $this->owner, reason: 'Scores re-marked after review');
        $this->assertSame(1, $result->saved);
        $this->assertSame('submitted', $this->exam->fresh()->status, 'an import never moves status forward from submitted');
    }

    public function test_unreadable_files_are_blocked_with_a_clear_message(): void
    {
        $noColumns = $this->service->preview($this->exam, $this->owner, [['foo', 'bar'], ['1', '2']]);
        $this->assertArrayHasKey('columns', $noColumns->blockers);

        $empty = $this->service->preview($this->exam, $this->owner, $this->sheet([]));
        $this->assertArrayHasKey('empty', $empty->blockers);
    }

    public function test_audit_log_records_who_imported_what_and_a_notification_event_fires(): void
    {
        Event::fake([MarksUpdated::class]);
        $plan = $this->service->preview($this->exam, $this->admin, $this->sheet([['KLS0000001', '', '80'], ['KLS0000004', '', '70']]), 'term1-maths.xlsx', str_repeat('a', 64));
        $this->service->commit($plan, $this->admin);

        $log = ActivityLog::where('description', 'marks.imported')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, (int) $log->causer_id);
        $props = $log->properties;
        $this->assertSame('term1-maths.xlsx', $props['file_name']);
        $this->assertSame(str_repeat('a', 64), $props['file_sha256']);
        $this->assertSame(1, $props['saved']);
        $this->assertSame(1, $props['skipped']);
        $this->assertSame(['not_in_class' => 1], $props['skipped_reasons']);
        $this->assertSame((int) $this->school->id, (int) $props['school_id']);
        Event::assertDispatched(MarksUpdated::class, fn ($e) => $e->affectedMarkCount === 1);
    }

    public function test_plan_limits_never_block_an_import(): void
    {
        $plan = Plan::create(['cycle' => 30, 'name' => 'tiny', 'display_name' => 'Tiny', 'no_of_students' => 1, 'no_of_users' => 1, 'is_active' => 1, 'order' => 1, 'amount' => 0]);
        CurrentPlan::create(['school_id' => $this->school->id, 'plan_id' => $plan->id, 'status' => 'running']);

        $result = $this->service->commit($this->service->preview($this->exam, $this->owner, $this->sheet([
            ['KLS0000001', '', '80'], ['KLS0000002', '', '60'], ['KLS0000003', '', '70'],
        ])), $this->owner);

        $this->assertSame(3, $result->saved, 'three students on a one-student plan: still imported in full');
    }

    public function test_template_lists_active_enrolled_students_with_saved_marks(): void
    {
        $this->saveMark($this->students[2], 66.5);
        \App\Models\User::whereKey($this->students[3]->id)->update(['status' => 'inactive']);

        $rows = $this->service->templateRows($this->exam, $this->owner);

        $this->assertSame([['KLS0000001', 'Amina Nakato', ''], ['KLS0000002', 'Brian Okello', '66.5']], $rows);
        $this->assertSame(['Admission No', 'Student', 'Marks'], $this->service->templateHeadings());
    }

    public function test_template_round_trips_through_preview(): void
    {
        $rows = $this->service->templateRows($this->exam, $this->owner);
        foreach ($rows as $i => $r) {
            $rows[$i][2] = (string) (50 + $i);
        }
        $plan = $this->service->preview($this->exam, $this->owner, array_merge([$this->service->templateHeadings()], $rows));

        $this->assertSame(['new', 'new', 'new'], $this->outcomes($plan));
    }
}
