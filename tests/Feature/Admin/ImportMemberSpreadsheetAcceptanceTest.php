<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Requests\ImportMemberRequest;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * Admin student/teacher import shares ImportMemberRequest.
 * Wizard already accepts xlsx; admin must accept csv/xlsx/xls too (#552 follow-up).
 */
class ImportMemberSpreadsheetAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Import Xlsx School',
            'email' => 'import-xlsx.'.Str::random(6).'@t.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Import Admin',
            'email' => 'admin.'.Str::random(6).'@t.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Import',
            'lastname' => 'Admin',
        ]);
    }

    public function test_form_request_allows_csv_xlsx_and_xls_extensions(): void
    {
        $request = ImportMemberRequest::create('/admin/importUsers', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $rules = $request->rules();
        $this->assertArrayHasKey('import_file', $rules);
        $this->assertStringContainsString('file_extension:csv,xlsx,xls', $rules['import_file']);

        $messages = $request->messages();
        $this->assertStringContainsString('xlsx', $messages['import_file.file_extension']);
    }

    public function test_real_student_xlsx_fixture_passes_extension_validation(): void
    {
        $path = base_path('tests/fixtures/klassapp-students-test-data.xlsx');
        $this->assertFileExists($path);

        $sheet = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true);
        $headers = array_map(fn ($v) => strtolower((string) $v), array_values($sheet[1] ?? []));
        $this->assertContains('name', $headers);
        $this->assertContains('class', $headers);

        $file = new UploadedFile(
            $path,
            'klassapp-students-test-data.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->assertTrue($this->passesImportMemberValidation($file), 'Real student xlsx fixture must pass ImportMemberRequest');
    }

    public function test_real_teacher_xlsx_fixture_passes_extension_validation(): void
    {
        $path = base_path('tests/fixtures/klassapp-teachers-test-data.xlsx');
        $this->assertFileExists($path);

        $file = new UploadedFile(
            $path,
            'klassapp-teachers-test-data.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->assertTrue($this->passesImportMemberValidation($file), 'Real teacher xlsx fixture must pass ImportMemberRequest');
    }

    public function test_generated_xls_fixture_passes_extension_validation(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'klassapp_import_').'.xls';
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['firstname', 'lastname', 'class'],
            ['Amina', 'Nakato', 'Primary One'],
        ]);
        IOFactory::createWriter($spreadsheet, 'Xls')->save($tmp);

        try {
            $file = new UploadedFile(
                $tmp,
                'admin-import-sample.xls',
                'application/vnd.ms-excel',
                null,
                true
            );
            $this->assertTrue($this->passesImportMemberValidation($file), 'Generated .xls must pass ImportMemberRequest');
        } finally {
            @unlink($tmp);
        }
    }

    public function test_pdf_is_rejected_by_extension_rule(): void
    {
        $file = UploadedFile::fake()->create('roster.pdf', 20, 'application/pdf');
        $this->assertFalse($this->passesImportMemberValidation($file));
    }

    public function test_admin_import_views_advertise_spreadsheet_accept(): void
    {
        foreach ([
            resource_path('views/admin/member/import/import.blade.php'),
            resource_path('views/admin/teacher/import.blade.php'),
        ] as $blade) {
            $this->assertFileExists($blade);
            $html = file_get_contents($blade);
            $this->assertStringContainsString('accept=', $html);
            $this->assertStringContainsString('.xlsx', $html);
            $this->assertStringContainsString('.xls', $html);
            $this->assertStringContainsString('.csv', $html);
        }
    }

    public function test_posting_real_student_xlsx_does_not_fail_extension_validation(): void
    {
        $path = base_path('tests/fixtures/klassapp-students-test-data.xlsx');
        $file = new UploadedFile(
            $path,
            'klassapp-students-test-data.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->admin)->post('/admin/importUsers', [
            'import_file' => $file,
        ]);

        $response->assertSessionMissing('errors');
        if ($response->getSession()->has('errors')) {
            $this->fail('Unexpected validation errors: '.json_encode($response->getSession()->get('errors')->toArray()));
        }
    }

    private function passesImportMemberValidation(UploadedFile $file): bool
    {
        $this->actingAs($this->admin);

        $request = ImportMemberRequest::create('/admin/importUsers', 'POST', [], [], [
            'import_file' => $file,
        ]);
        $request->setUserResolver(fn () => $this->admin);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        // rules() registers the custom Validator::extend callbacks
        $rules = $request->rules();

        $validator = Validator::make(
            ['import_file' => $file],
            $rules,
            $request->messages()
        );

        if ($validator->fails()) {
            return false;
        }

        return true;
    }
}
