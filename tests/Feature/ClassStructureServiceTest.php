<?php

namespace Tests\Feature;

use App\Helpers\SiteHelper;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ClassStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassStructureServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Section $base;

    private Standard $standard;

    private StandardLink $baseLink;

    private ClassStructureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Stream Structure School',
            'email' => 'stream-structure@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'stream-structure',
            'status' => 1,
            'school_category' => 'primary',
            'curriculum' => 'uneb',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 2,
            'status' => '1',
        ]);

        $this->base = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => '1',
        ]);

        $this->baseLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->base->id,
            'status' => '1',
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->base->id,
            'name' => 'Mathematics',
            'code' => '007',
            'type' => 'core',
            'status' => 1,
        ]);
        Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->base->id,
            'name' => 'English Language',
            'code' => '013',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->service = app(ClassStructureService::class);
    }

    public function test_add_stream_creates_name_encoded_section_keeps_base_and_copies_subjects(): void
    {
        $result = $this->service->addStream($this->school, $this->year, $this->base, 'A');

        $this->assertTrue($result['created']);
        $this->assertSame('Primary One A', $result['section']->name);
        $this->assertTrue(Section::whereKey($this->base->id)->exists());
        $this->assertSame('Primary One', $this->base->fresh()->name);

        $link = $result['standard_link'];
        $this->assertSame((int) $this->school->id, (int) $link->school_id);
        $this->assertSame((int) $result['section']->id, (int) $link->section_id);
        $this->assertNull($link->stream);

        $subjectNames = Subject::where('section_id', $result['section']->id)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($n) => strtolower((string) $n))
            ->values()
            ->all();
        $this->assertSame(['english language', 'mathematics'], $subjectNames);
    }

    public function test_add_stream_does_not_write_standards_link_stream_column(): void
    {
        $result = $this->service->addStream($this->school, $this->year, $this->base, 'East');

        $row = DB::table('standards_link')->where('id', $result['standard_link']->id)->first();
        $this->assertTrue($row->stream === null || $row->stream === '');
    }

    public function test_add_stream_from_existing_stream_section_uses_base_name(): void
    {
        $first = $this->service->addStream($this->school, $this->year, $this->base, 'A');
        $second = $this->service->addStream($this->school, $this->year, $first['section'], 'B');

        $this->assertSame('Primary One B', $second['section']->name);
        $this->assertTrue(Section::where('name', 'Primary One')->where('school_id', $this->school->id)->exists());
        $this->assertTrue(Section::where('name', 'Primary One A')->where('school_id', $this->school->id)->exists());
    }

    public function test_add_stream_rejects_blank_label(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->addStream($this->school, $this->year, $this->base, '   ');
    }

    public function test_rename_stream_updates_section_name_only(): void
    {
        $created = $this->service->addStream($this->school, $this->year, $this->base, 'A');
        $renamed = $this->service->renameStream($this->school, $created['section'], 'Primary One East');

        $this->assertSame('Primary One East', $renamed->name);
        $this->assertSame('Primary One', $this->base->fresh()->name);
    }

    public function test_admin_can_add_stream_via_http(): void
    {
        $admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Admin',
            'email' => 'admin-stream@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'User',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.class-stream.create', $this->base))
            ->assertOk()
            ->assertSee('Add stream')
            ->assertSee('Primary One');

        $this->actingAs($admin)
            ->post(route('admin.class-stream.store', $this->base), ['stream' => 'West'])
            ->assertRedirect(route('admin.classes'));

        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary One West',
        ]);
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary One',
        ]);

        $year = SiteHelper::getAcademicYear($this->school->id);
        $this->assertNotNull($year);
    }
}
