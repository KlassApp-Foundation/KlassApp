<?php

namespace Tests\Feature\Academics;

use App\Models\AcademicYear;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Reference\ExamTypeCatalog;
use App\Services\Academics\ExamTypeCatalogSync;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A school must always have exam types to choose from, and must never see
 * another school's private ones. The catalog is global (school_id null);
 * schools may add their own on top.
 */
class ExamTypeCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function school(string $name, string $slug): School
    {
        return School::create([
            'name' => $name, 'slug' => $slug,
            'email' => $slug.'@catalog.test', 'phone' => null,
            'status' => 1,
        ]);
    }

    public function test_a_fresh_database_gets_the_shared_catalog(): void
    {
        $this->assertSame(0, ExamType::query()->count());

        $touched = app(ExamTypeCatalogSync::class)->syncShared();

        $this->assertSame(count(ExamTypeCatalog::all()), $touched);
        $this->assertSame(count(ExamTypeCatalog::all()), ExamType::query()->count());
        $this->assertSame(0, ExamType::query()->whereNotNull('school_id')->count(), 'the catalog stays global');
        $this->assertSame('End Of Term', ExamType::query()->where('code', 'EOT')->value('name'));
        $this->assertTrue((bool) ExamType::query()->where('code', 'EOT')->value('contributes_to_report_total'));
    }

    public function test_syncing_twice_never_duplicates_and_repairs_a_drifted_shared_row(): void
    {
        app(ExamTypeCatalogSync::class)->syncShared();

        $this->assertSame(0, app(ExamTypeCatalogSync::class)->syncShared(), 'a second clean run is a no-op');

        ExamType::query()->whereNull('school_id')->where('code', 'MID')->update(['name' => 'Mid Term Exam']);

        $this->assertSame(1, app(ExamTypeCatalogSync::class)->syncShared());
        $this->assertSame(count(ExamTypeCatalog::all()), ExamType::query()->count());
        $this->assertSame('Mid Term', ExamType::query()->whereNull('school_id')->where('code', 'MID')->value('name'));
    }

    public function test_a_school_created_by_signup_has_the_catalog_to_choose_from(): void
    {
        config(['klassapp.signup_enabled' => true]);

        $user = app(SchoolSignupBootstrapService::class)->bootstrap([
            'name' => 'Catalog Owner', 'email' => 'owner@catalog.test', 'phone' => null,
            'password' => 'secret123', 'school_name' => 'Catalog School',
        ]);

        $this->assertGreaterThan(0, ExamType::visibleToSchool($user->school_id)->count());
        $this->assertSame(count(ExamTypeCatalog::all()), ExamType::visibleToSchool($user->school_id)->count());
    }

    public function test_a_schools_own_type_survives_and_another_school_never_sees_it(): void
    {
        app(ExamTypeCatalogSync::class)->syncShared();

        $a = $this->school('School A', 'catalog-a');
        $b = $this->school('School B', 'catalog-b');

        $own = ExamType::create([
            'school_id' => $a->id, 'name' => 'School A Special', 'code' => 'A-SPECIAL',
            'contributes_to_report_total' => false,
        ]);

        $this->assertTrue(ExamType::visibleToSchool($a->id)->whereKey($own->id)->exists());
        $this->assertFalse(ExamType::visibleToSchool($b->id)->whereKey($own->id)->exists());

        app(ExamTypeCatalogSync::class)->syncShared();
        $this->assertSame('School A Special', $own->fresh()->name);
    }

    public function test_the_teacher_exam_form_lists_shared_and_own_types_only(): void
    {
        app(ExamTypeCatalogSync::class)->syncShared();

        $a = $this->school('School A', 'catalog-a');
        $b = $this->school('School B', 'catalog-b');

        ExamType::create([
            'school_id' => $b->id, 'name' => 'School B Special', 'code' => 'B-SPECIAL',
            'contributes_to_report_total' => false,
        ]);

        $teacher = User::factory()->create([
            'school_id' => $a->id, 'usergroup_id' => 5,
            'email' => 'teacher@catalog.test', 'name' => 'Teacher A', 'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'school_id' => $a->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create(['school_id' => $a->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        StandardLink::create([
            'school_id' => $a->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => Section::create(['school_id' => $a->id, 'name' => 'P.1', 'status' => 1])->id,
            'class_teacher_id' => $teacher->id, 'status' => 1,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.exams.create'))
            ->assertOk()
            ->assertSee('End Of Term')
            ->assertDontSee('School B Special');
    }

    public function test_the_exam_set_page_seeds_the_catalog_even_on_an_old_database(): void
    {
        $school = $this->school('Old School', 'catalog-old');
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        \App\Models\AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30', 'status' => 'current',
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 3,
            'email' => 'admin@catalog.test', 'name' => 'Admin', 'status' => 'active',
        ]);

        $this->assertSame(0, ExamType::query()->count());

        $this->actingAs($admin)->get(route('admin.exam-sets.create'))->assertOk();

        $this->assertGreaterThan(0, ExamType::query()->count());
    }
}
