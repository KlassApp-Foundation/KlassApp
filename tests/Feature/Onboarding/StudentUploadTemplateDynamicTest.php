<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\StudentUploadTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class StudentUploadTemplateDynamicTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBeSchoolAdmin::class,
            MustBePrivilege::class,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Dynamic Template School',
            'slug' => 'dyn-template-' . uniqid(),
            'email' => 'dyn-template-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_' . $this->school->id);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'status' => 'active',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $base = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
        ]);
        $streamA = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One East',
            'status' => 1,
        ]);

        foreach ([$base, $streamA] as $section) {
            StandardLink::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'status' => '1',
            ]);
        }
    }

    public function test_sample_rows_use_school_stream_names(): void
    {
        $rows = app(StudentUploadTemplateService::class)
            ->sampleClassStreamRows($this->school, $this->year);

        $this->assertContains(['class' => 'Primary One', 'stream' => ''], $rows);
        $this->assertContains(['class' => 'Primary One', 'stream' => 'East'], $rows);
        $this->assertNotContains(['class' => 'Baby Class', 'stream' => 'A'], $rows);
    }

    public function test_download_route_returns_xlsx_with_school_streams(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.upload-template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $tmp = tempnam(sys_get_temp_dir(), 'stu');
        file_put_contents($tmp, $response->streamedContent());
        $sheet = IOFactory::load($tmp)->getActiveSheet()->toArray(null, true, true, true);
        @unlink($tmp);

        $this->assertSame('Name', $sheet[1]['A']);
        $this->assertSame('Class', $sheet[1]['B']);
        $this->assertSame('Stream', $sheet[1]['C']);

        $classes = array_column(array_slice($sheet, 1), 'B');
        $streams = array_column(array_slice($sheet, 1), 'C');
        $this->assertContains('Primary One', $classes);
        $this->assertContains('East', $streams);
        $this->assertNotContains('Baby Class', $classes);
    }

    public function test_wizard_students_step_links_dynamic_template_and_help(): void
    {
        $html = view('livewire.partials.manual-wizard-step-fields', [
            'stepKey' => 'students',
            'studentDrafts' => [],
        ])->render();

        $this->assertStringContainsString(route('admin.students.upload-template', [], false), $html);
        $this->assertStringContainsString("Leave Stream blank if your school doesn't use streams.", $html);
        $this->assertStringNotContainsString('templates/student-upload-template.xlsx', $html);
    }

    public function test_sample_rows_prefer_stream_rows_first(): void
    {
        $rows = app(StudentUploadTemplateService::class)
            ->sampleClassStreamRows($this->school, $this->year);

        $this->assertSame('East', $rows[0]['stream']);
        $this->assertContains(['class' => 'Primary One', 'stream' => ''], $rows);
    }
}
