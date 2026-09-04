<?php

namespace Tests\Feature\Parent;

use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentChildDataPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $child;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create(['name' => 'Child Pages School', 'email' => 'pages@test.sch.ug', 'status' => 1]);
        $this->schoolId = $school->id;

        $link = StandardLink::create([
            'school_id' => $school->id,
            'standard_id' => Standard::create(['school_id' => $school->id, 'name' => 'P4', 'order' => 4])->id,
            'section_id' => Section::create(['school_id' => $school->id, 'name' => 'P.4'])->id,
            'academic_year_id' => DB::table('academic_years')->insertGetId([
                'school_id' => $school->id,
                'name' => '2026',
                'description' => 'Current Academic Year',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
        ]);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Parent Pages',
        ]);

        $this->child = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'name' => 'Alice Linked',
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $this->child->id,
            'usergroup_id' => 6,
            'firstname' => 'Alice',
            'lastname' => 'Linked',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $this->child->id,
            'standardLink_id' => $link->id,
            'roll_number' => '1',
        ]);

        StudentParentLink::create([
            'parent_id' => $this->parent->id,
            'student_id' => $this->child->id,
            'school_id' => $school->id,
            'status' => 1,
        ]);

        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $link->standard_id,
            'name' => 'Linked Child Fees',
            'amount' => 50000,
        ]);
    }

    public function test_fees_page_renders_html_not_json(): void
    {
        $response = $this->actingAs($this->parent)
            ->get(route('parent.children.fees', $this->child->id));

        $response->assertOk();
        $response->assertSee('data-testid="parent-child-fees-page"', false);
        $response->assertSee('Linked Child Fees');
        $response->assertSee('50,000');
        $response->assertDontSee('"success":true', false);
        $this->assertStringNotContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function test_grades_page_renders_html_shell(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.grades', $this->child->id))
            ->assertOk()
            ->assertSee('data-testid="parent-child-grades-page"', false)
            ->assertSee('No results published');
    }

    public function test_attendance_page_renders_html_shell(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.attendance', $this->child->id))
            ->assertOk()
            ->assertSee('data-testid="parent-child-attendance-page"', false)
            ->assertSee('No attendance yet');
    }
}
