<?php

namespace Tests\Feature\Parent;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\Parent\ParentPortalService;
use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * A parent can download a released card when no exam type contributes.
 * The card names only the released sitting. WhatsApp keeps its own check.
 */
class ReleasedCardWithoutContributingTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_released_non_contributing_sitting_is_enough_for_the_parent_download(): void
    {
        Cache::flush();
        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Release Primary',
            'email' => 'release@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'release-'.uniqid(),
            'status' => 1,
            'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$school->id);
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term I',
            'starts_on' => '2026-02-10',
            'ends_on' => '2026-05-01',
            'status' => 'current',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 5, 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'status' => 1,
        ]);
        $beginning = ExamType::create([
            'name' => 'Beginning Of Term', 'code' => 'BOT-'.uniqid(), 'contributes_to_report_total' => 0,
        ]);
        $mid = ExamType::create([
            'name' => 'Mid Term', 'code' => 'MID-'.uniqid(), 'contributes_to_report_total' => 0,
        ]);
        $subject = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);
        $beginningExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $beginning->id,
            'scheduled_at' => '2026-02-20',
            'status' => 'done',
        ]));
        $midExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $mid->id,
            'scheduled_at' => '2026-03-20',
            'status' => 'done',
        ]));
        $student = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'status' => 'active', 'name' => 'PUPIL 076',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);
        $parent = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 7, 'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $school->id, 'parent_id' => $parent->id, 'student_id' => $student->id, 'status' => 1,
        ]);
        Marks::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'exam_id' => $beginningExam->id, 'teacher_id' => $teacher->id, 'school_id' => $school->id,
            'marks' => 17, 'grade' => 'F9',
        ]);
        Marks::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'exam_id' => $midExam->id, 'teacher_id' => $teacher->id, 'school_id' => $school->id,
            'marks' => 83, 'grade' => 'C3',
        ]);

        $portal = app(ParentPortalService::class);
        $before = $portal->releasedReportPdf($student, (int) $school->id);
        $this->assertFalse($before['ok']);
        $this->assertArrayNotHasKey('pdf', $before);

        MarksRelease::create([
            'school_id' => $school->id,
            'exam_id' => $midExam->id,
            'scope' => 'class',
            'standard_link_id' => $link->id,
            'scheduled_at' => now()->subHour(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
            'whatsapp_send' => false,
        ]);

        $captured = null;
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4');
        $this->app->instance('dompdf.wrapper', $pdf);

        $result = $portal->releasedReportPdf($student, (int) $school->id);
        $this->assertTrue($result['ok']);
        $this->assertStringStartsWith('%PDF', $result['pdf']);

        $labels = array_column($captured['setColumns'] ?? [], 'label');
        $this->assertContains('Mid Term', $labels);
        $this->assertNotContains('Beginning Of Term', $labels);
        $marks = [];
        foreach ($captured['setColumns'] ?? [] as $column) {
            foreach ($column['cells'] as $cell) {
                if ($cell->mark !== null) {
                    $marks[] = (float) $cell->mark;
                }
            }
        }
        $this->assertContains(83.0, $marks);
        $this->assertNotContains(17.0, $marks);

        $whatsApp = app(WhatsAppReportCardDeliveryService::class)->prepareForStudent($parent, $student);
        $this->assertFalse($whatsApp['ok']);
    }
}
