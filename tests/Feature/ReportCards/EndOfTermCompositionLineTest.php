<?php

namespace Tests\Feature\ReportCards;

use App\Enums\ToshiMode;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\Subject;
use App\Services\ReportCards\EndOfTermCompositionLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/** End-of-term mix line on the class report and the printed card. */
class EndOfTermCompositionLineTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
        Cache::flush();
        $this->school->forceFill([
            'toshi_mode' => ToshiMode::Preview,
            'toshi_enabled' => 0,
        ])->save();
        $this->draftMark('English', 74);
        $this->draftMark('Mathematics', 62);
    }

    public function test_format_uses_stored_weights_and_the_empty_default(): void
    {
        $line = app(EndOfTermCompositionLine::class);

        $this->assertNull($line->format(false, [
            '2' => ['include' => true, 'weight' => 70, 'name' => 'End of Term'],
        ]));
        $this->assertSame('End of Term report: End of Term 100%', $line->format(true, null));
        $this->assertSame('End of Term report: End of Term 100%', $line->format(true, []));
        $this->assertSame(
            'End of Term report: End of Term 70% · Mid Term 30%',
            $line->format(true, [
                '1' => ['include' => true, 'weight' => 30, 'name' => 'Mid Term'],
                '2' => ['include' => true, 'weight' => 70, 'name' => 'End of Term'],
            ])
        );
        $this->assertSame(
            'End of Term report: Botany 100%',
            $line->format(true, [
                '9' => ['include' => true, 'weight' => null, 'name' => 'Botany'],
            ])
        );
        $this->assertSame(
            'End of Term report: End of Term 70%',
            $line->format(true, [
                '1' => ['include' => true, 'weight' => null, 'name' => 'Mid Term'],
                '2' => ['include' => true, 'weight' => 70, 'name' => 'End of Term'],
            ])
        );
    }

    public function test_an_end_of_term_class_report_prints_the_stored_mix_and_the_kls_number(): void
    {
        $this->makeEndOfTerm();
        $this->school->forceFill([
            'report_composition' => [
                '1' => ['include' => true, 'weight' => 30],
                '2' => ['include' => true, 'weight' => 70],
            ],
        ])->save();
        $this->release('English');

        $this->school->refresh();
        $this->assertSame(ToshiMode::Preview, $this->school->toshi_mode);
        $this->assertSame(0, (int) $this->school->toshi_enabled);
        $this->assertSame('active', $this->student->status);

        $html = $this->actingAs($this->admin)->get('/admin/reports/cards')->assertOk()->getContent();

        $this->assertStringContainsString('End of Term report: End of Term 70% · Mid Term 30%', $html);
        $this->assertStringContainsString('data-testid="eot-composition"', $html);
        $this->assertStringContainsString('Each exam set has its own report', $html);
        $this->assertStringContainsString('data-testid="own-report-1"', $html);
        $this->assertStringContainsString('Not released yet', $html);
        $release = MarksRelease::query()->where('exam_id', $this->exams['English']->id)->first();
        $releasedOn = 'Released '.$release->released_at->timezone(config('app.timezone'))->format('j M Y');
        $this->assertStringContainsString($releasedOn, $html);
        $this->assertStringNotContainsString('Grace Achieng', $html);
        $this->assertStringNotContainsString('80 × 30%', $html);
        $this->assertStringContainsString('KLS0000431', $html);
        $this->assertStringContainsString('data-testid="rc-phone-kls"', $html);
        $this->assertStringContainsString('data-testid="rc-phone-card"', $html);
    }

    public function test_an_empty_composition_defaults_to_end_of_term_100(): void
    {
        $this->makeEndOfTerm();
        $this->school->forceFill(['report_composition' => null])->save();
        $this->release('English');

        $this->actingAs($this->admin)
            ->get('/admin/reports/cards')
            ->assertOk()
            ->assertSee('End of Term report: End of Term 100%')
            ->assertDontSee('Mid Term 30%');
    }

    public function test_one_included_set_with_a_blank_weight_is_100(): void
    {
        $this->makeEndOfTerm();
        $this->school->forceFill([
            'report_composition' => [
                '2' => ['include' => true, 'weight' => null],
            ],
        ])->save();
        $this->release('English');

        $this->actingAs($this->admin)
            ->get('/admin/reports/cards')
            ->assertOk()
            ->assertSee('End of Term report: End of Term 100%');
    }

    public function test_a_mid_term_report_has_no_composition_line(): void
    {
        $this->school->forceFill([
            'report_composition' => [
                '1' => ['include' => true, 'weight' => 30],
                '2' => ['include' => true, 'weight' => 70],
            ],
        ])->save();
        $this->release('English');

        $this->actingAs($this->admin)
            ->get('/admin/reports/cards')
            ->assertOk()
            ->assertDontSee('End of Term report:');
    }

    public function test_an_absence_is_shown_as_abs_and_the_stored_grade_stays_ab(): void
    {
        $this->makeEndOfTerm();
        Marks::query()->where('exam_id', $this->exams['English']->id)->update([
            'marks' => null,
            'grade' => 'AB',
            'absent' => true,
        ]);
        $this->release('English');
        $this->release('Mathematics');

        $html = $this->actingAs($this->admin)->get('/admin/reports/cards')->assertOk()->getContent();

        $this->assertStringContainsString('ABS', $html);
        $this->assertStringContainsString('62', $html);
        $this->assertSame('AB', Marks::query()->where('exam_id', $this->exams['English']->id)->value('grade'));
    }

    public function test_a_released_report_keeps_the_mix_captured_at_release(): void
    {
        if (! Schema::hasColumn('marks_releases', 'report_composition')) {
            $this->markTestSkipped(
                'marks_releases.report_composition is absent on this branch. The freeze lives on #1330, so the printed line follows the school\'s current report_composition and this assertion is skipped.'
            );
        }

        $this->makeEndOfTerm();
        $this->school->forceFill([
            'report_composition' => [
                '2' => ['include' => true, 'weight' => null],
            ],
        ])->save();
        $this->release('English');
        $releaseId = MarksRelease::query()->where('exam_id', $this->exams['English']->id)->value('id');
        DB::table('marks_releases')->where('id', $releaseId)->update([
            'report_composition' => json_encode([
                '1' => ['include' => true, 'name' => 'Mid Term', 'weight' => 30],
                '2' => ['include' => true, 'name' => 'End of Term', 'weight' => 70],
            ]),
        ]);

        $line = app(EndOfTermCompositionLine::class)->lineFor((int) $this->school->id, true, (int) $this->exams['English']->id);

        $this->assertSame('End of Term report: End of Term 70% · Mid Term 30%', $line);

        $html = $this->actingAs($this->admin)->get('/admin/reports/cards')->assertOk()->getContent();
        $this->assertStringContainsString('End of Term report: End of Term 70% · Mid Term 30%', $html);
        $this->assertStringNotContainsString('End of Term report: End of Term 100%', $html);
    }

    public function test_the_printed_card_shows_the_mix_line_and_abs(): void
    {
        $mark = Marks::query()->where('exam_id', $this->exams['English']->id)->first();
        $mark->update(['marks' => null, 'grade' => 'AB', 'absent' => true]);
        $subject = Subject::query()->whereKey($this->exams['English']->subject_id)->first();
        $learner = $this->student->fresh();
        $learner->setRelation('marks', collect([$mark->fresh()]));
        $learner->setRelation('school', $this->school);

        $data = [
            'learner' => $learner,
            'class_name' => 'Grade 5',
            'schoolIdentity' => ['name' => 'Demo Profile School', 'footer_line' => 'Demo Profile School'],
            'logoPath' => null,
            'isNursery' => false,
            'showAgg' => false,
            'showDivision' => false,
            'showPoints' => false,
            'midExams' => collect(),
            'eotExams' => collect([$this->exams['English']]),
            'subjects' => collect([$subject]),
            'grading_system' => collect(),
            'stdLink' => $this->link,
            'eotMarkBySubject' => [],
            'eotDisplayBySubject' => [$subject->id => $mark->fresh()],
            'midSittings' => [],
            'midStats' => [],
            'total' => null,
            'grade' => ['agg' => null],
            'examinedSubjectCount' => 1,
            'eotAgg' => null,
            'eotDivision' => '-',
            'teacherComment' => '',
            'headTeacherComment' => '',
            'nextTerm' => null,
            'compositionLine' => 'End of Term report: End of Term 70% · Mid Term 30%',
        ];

        foreach (['admin.marks.report-templates.formal', 'admin.marks.report-templates.warm'] as $view) {
            $html = view($view, $data)->render();
            $this->assertStringContainsString('End of Term report: End of Term 70% · Mid Term 30%', $html);
            $this->assertStringContainsString('data-testid="eot-composition"', $html);
            $this->assertStringContainsString('ABS', $html);
            $this->assertStringNotContainsString('>0<', $html);
        }

        unset($data['compositionLine']);
        $plain = view('admin.marks.report-templates.formal', $data)->render();
        $this->assertStringNotContainsString('End of Term report:', $plain);
        $this->assertSame('AB', $mark->fresh()->grade);

        $data['compositionLine'] = 'End of Term report: End of Term 70% · Mid Term 30%';
        $data['ownReports'] = [
            ['id' => 2, 'name' => 'End of Term', 'status' => 'Released 21 Apr 2026', 'released' => true],
            ['id' => 1, 'name' => 'Mid Term', 'status' => 'Not released yet', 'released' => false],
        ];
        $listed = view('admin.marks.report-templates.formal', $data)->render();
        $this->assertStringContainsString('Each exam set has its own report', $listed);
        $this->assertStringContainsString('End of Term — Released 21 Apr 2026', $listed);
        $this->assertStringContainsString('Mid Term — Not released yet', $listed);
        $this->assertStringNotContainsString('Grace Achieng', $listed);
        $this->assertStringNotContainsString('80 × 30%', $listed);
    }

    public function test_another_schools_release_does_not_mark_this_class_released(): void
    {
        $this->makeEndOfTerm();
        $otherYear = \App\Models\AcademicYear::query()->where('school_id', $this->otherSchool->id)->first();
        $otherSection = \App\Models\Section::query()->where('school_id', $this->otherSchool->id)->first();
        $otherStandard = \App\Models\Standard::query()->where('school_id', $this->otherSchool->id)->first();
        $otherTerm = \App\Models\AcademicTerm::query()->create([
            'school_id' => $this->otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'name' => 'Term 3',
            'status' => 'current',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-18',
        ]);
        $subject = Subject::query()->create([
            'school_id' => $this->otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'standard_id' => $otherStandard->id,
            'section_id' => $otherSection->id,
            'name' => 'Other English',
            'code' => 'OE',
            'type' => 'core',
            'status' => 1,
        ]);
        $exam = \App\Models\Academics\Exam::withoutEvents(fn () => \App\Models\Academics\Exam::query()->create([
            'school_id' => $this->otherSchool->id,
            'standard_id' => $otherStandard->id,
            'section_id' => $otherSection->id,
            'academic_year_id' => $otherYear->id,
            'academic_term_id' => $otherTerm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => 2,
            'status' => 'undone',
        ]));
        \Tests\Support\ReleasedExam::release($exam);

        $rows = app(EndOfTermCompositionLine::class)->ownReports(
            (int) $this->school->id,
            [(int) $this->section->id],
            (int) $this->term->id,
        );
        $end = collect($rows)->firstWhere('id', 2);

        $this->assertNotNull($end);
        $this->assertSame('Not released yet', $end['status']);
        $this->assertFalse($end['released']);
    }

    private function makeEndOfTerm(): void
    {
        $now = now();
        DB::table('exam_types')->upsert([
            ['id' => 1, 'school_id' => null, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'school_id' => null, 'name' => 'End of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => $now, 'updated_at' => $now],
        ], 'id');
    }
}
