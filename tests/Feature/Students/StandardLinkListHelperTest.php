<?php

namespace Tests\Feature\Students;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * getStandardLinkList() joins `standards` for ordering. Without an explicit
 * select, the joined columns (id, status, ...) overwrite the class's own, so
 * every class under one standard came back carrying the standard's id and
 * class dropdowns (attendance, homework, posts, ...) submitted the wrong id.
 */
class StandardLinkListHelperTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    /** @var array<int, StandardLink> */
    private array $classes = [];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'List School',
            'slug' => 'list-school',
            'email' => 'list@school.test',
            'phone' => '+256700000009',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        foreach (['P.1', 'P.2', 'P.3'] as $sectionName) {
            $section = Section::create([
                'school_id' => $this->school->id,
                'name' => $sectionName,
                'status' => 1,
            ]);
            $this->classes[] = StandardLink::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'stream' => 'A',
                'status' => 1,
            ]);
        }

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
        ]);
    }

    /** @test */
    public function each_class_under_one_standard_keeps_its_own_id(): void
    {
        $list = collect(SiteHelper::getStandardLinkList($this->school->id)->resolve());

        $this->assertEqualsCanonicalizing($this->classIds(), $list->pluck('id')->all());
        $this->assertEqualsCanonicalizing(['P.1', 'P.2', 'P.3'], $list->pluck('section_name')->all());
    }

    /** @test */
    public function attendance_class_dropdown_offers_each_real_class_id(): void
    {
        $offeredIds = collect(
            $this->actingAs($this->admin)
                ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
                ->getJson('/admin/attendance/list')
                ->assertOk()
                ->json('standardlist')
        )->pluck('id');

        $this->assertEqualsCanonicalizing($this->classIds(), $offeredIds->all());
    }

    /**
     * @return array<int, int>
     */
    private function classIds(): array
    {
        return array_map(fn (StandardLink $class): int => $class->id, $this->classes);
    }
}
