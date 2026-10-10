<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\ReportGeneration;
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
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.1', 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1,
        ]);

        Storage::disk('local')->put('reports/class-ready.pdf', "%PDF-1.4\n% test");
        $generation = ReportGeneration::create([
            'school_id' => $school->id,
            'standard_link_id' => $link->id,
            'class_name' => 'P.1',
            'mode' => 'merged',
            'status' => 'completed',
            'file_path' => 'reports/class-ready.pdf',
            'file_name' => 'P.1.pdf',
            'requested_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.cards.generation.download', $generation));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', file_get_contents($response->baseResponse->getFile()->getPathname()));
    }
}
