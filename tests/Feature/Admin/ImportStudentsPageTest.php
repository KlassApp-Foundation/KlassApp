<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ImportStudents;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImportStudentsPageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Import Students School',
            'email' => 'import-students@example.test',
            'phone' => '+256700333000',
            'slug' => 'import-students',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 2,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Import Admin',
            'email' => 'import-admin@example.test',
            'password' => bcrypt('secret'),
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

    public function test_student_list_import_and_add_open_the_import_page(): void
    {
        $page = $this->actingAs($this->admin)->get('/admin/students');
        $page->assertOk();
        $page->assertSee('/admin/students/import', false);
        $page->assertDontSee('href="'.url('/admin/import').'"', false);

        $this->actingAs($this->admin)->get('/admin/import')
            ->assertRedirect('/admin/students/import');

        $import = $this->actingAs($this->admin)->get('/admin/students/import');
        $import->assertOk();
        $import->assertSee('Import students', false);
        $import->assertSee(route('admin.students.upload-template'), false);
        $import->assertSee('data-testid="import-students-upload"', false);
    }

    public function test_upload_reports_added_and_skipped_like_the_wizard(): void
    {
        $this->actingAs($this->admin);
        $csv = "Name,Class,Stream,Parent Name,Parent Phone\n"
            ."Amina Nakato,P.1,,Grace Nakato,+256700444111\n"
            ."Amina Nakato,P.1,,Grace Nakato,+256700444111\n";

        $component = Livewire::test(ImportStudents::class)
            ->set('studentUpload', UploadedFile::fake()->createWithContent('students.csv', $csv));

        $component->assertSet('studentUploadReport', '1 student added, 1 skipped');
        $this->assertSame(1, User::query()->where('school_id', $this->school->id)->where('usergroup_id', 6)->count());

        $parent = User::query()->where('school_id', $this->school->id)->where('usergroup_id', 7)->first();
        $this->assertNotNull($parent);
        $this->assertSame('Grace Nakato', $parent->name);
        $this->assertNotNull($parent->mobile_no);
        $this->assertStringContainsString('700444111', (string) $parent->mobile_no);
    }

    public function test_missing_stream_names_the_class_and_can_be_created(): void
    {
        $this->actingAs($this->admin);
        $csv = "Name,Class,Stream\nBrian Okello,P.1,A\n";

        $component = Livewire::test(ImportStudents::class)
            ->set('studentUpload', UploadedFile::fake()->createWithContent('missing.csv', $csv));

        $component->assertSee("P.1, stream A doesn't exist yet. Create it in Classes, or use Create missing streams and retry.");
        $component->assertSee('Create missing streams and retry');
        $this->assertSame(0, User::query()->where('usergroup_id', 6)->where('school_id', $this->school->id)->count());

        $component->call('createMissingStreamsAndRetry', 'students');
        $component->assertSet('studentUploadReport', '1 student added, 0 skipped');
        $this->assertSame(1, User::query()->where('usergroup_id', 6)->where('school_id', $this->school->id)->count());
        $this->assertTrue(
            Section::query()->where('school_id', $this->school->id)->where('name', 'P.1 A')->exists()
        );
    }

    public function test_add_one_saves_the_parent_name_and_phone_on_the_parent(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ImportStudents::class)
            ->set('studentName', 'Carol Adongo')
            ->set('studentClass', 'P.1')
            ->set('studentParent', 'Peter Adongo')
            ->set('studentParentPhone', '+256700555222')
            ->call('addStudent')
            ->assertSet('studentUploadReport', '1 student added, 0 skipped');

        $parent = User::query()->where('school_id', $this->school->id)->where('usergroup_id', 7)->first();
        $this->assertNotNull($parent);
        $this->assertSame('Peter Adongo', $parent->name);
        $this->assertStringContainsString('700555222', (string) $parent->mobile_no);
        $this->assertNotSame('700555222', (string) User::query()->where('name', 'Carol Adongo')->value('mobile_no'));
    }
}
