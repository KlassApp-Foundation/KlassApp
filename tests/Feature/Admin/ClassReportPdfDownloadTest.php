<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\ReportGeneration;
use App\Models\Subject;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassReportPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_merged_file_downloads_as_pdf(): void
    {
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'PDF School', 'slug' => 'pdf-'.uniqid(),
            'email' => uniqid().'@pdf.test', 'phone' => '0700000001', 'status' => 1,
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 3, 'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'y',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Term II', 'starts_on' => '2026-05-18', 'ends_on' => '2026-08-21', 'status' => 'current',
        ]);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'stream' => 'Blue', 'status' => 1,
        ]);
        $type = ExamType::create(['name' => 'End of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1]);
        $subject = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'subject_id' => $subject->id, 'teacher_id' => $admin->id, 'exam_type_id' => $type->id,
            'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
            'scheduled_at' => '2026-08-01', 'status' => 'submitted',
        ]));

        Storage::disk('local')->put('reports/class-ready.pdf', "%PDF-1.4\n% test");
        $generation = ReportGeneration::create([
            'school_id' => $school->id,
            'standard_link_id' => $link->id,
            'academic_term_id' => $term->id,
            'class_name' => 'P.5',
            'mode' => 'merged',
            'status' => 'completed',
            'file_path' => 'reports/class-ready.pdf',
            'file_name' => 'P.5.pdf',
            'requested_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.cards.generation.download', $generation));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', file_get_contents($response->baseResponse->getFile()->getPathname()));

        $withFile = $this->actingAs($admin)->get('/admin/reports/cards?term='.$term->id);
        $withFile->assertOk();
        $withFile->assertSee('Download PDF');
        $withFile->assertSee('P.5 · Blue');

        $emptyTerm = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term III',
            'starts_on' => '2026-09-14',
            'ends_on' => '2026-12-04',
            'status' => 'last',
        ]);
        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'subject_id' => $subject->id, 'teacher_id' => $admin->id, 'exam_type_id' => $type->id,
            'academic_term_id' => $emptyTerm->id, 'academic_year_id' => $year->id,
            'scheduled_at' => '2026-11-01', 'status' => 'submitted',
        ]));
        $withoutFile = $this->actingAs($admin)->get('/admin/reports/cards?term='.$emptyTerm->id);
        $withoutFile->assertOk();
        $withoutFile->assertDontSee('Download PDF');
        $withoutFile->assertSee('Generate');
    }
}
