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

class ParentPortalWebIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $childA;

    private User $otherStudent;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create(['name' => 'Web Isolation School', 'email' => 'iso@test.sch.ug', 'status' => 1]);
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
            'email' => 'parent.iso@test.sch.ug',
            'password' => bcrypt('iso-pass-123'),
        ]);

        Userprofile::create([
            'user_id' => $this->parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Iso',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $this->childA = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Alice Linked',
        ]);

        $this->otherStudent = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Other Student',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $this->childA->id,
            'standardLink_id' => $link->id,
        ]);

        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $this->childA->id,
            'status' => 1,
        ]);

        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $link->standard_id,
            'name' => 'Linked Child Fees',
            'amount' => 50000,
        ]);
    }

    public function test_parent_can_load_fees_for_linked_child_via_web(): void
    {
        $this->actingAs($this->parent)
            ->getJson(route('parent.children.fees', $this->childA->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student_id', $this->childA->id)
            ->assertJsonPath('data.total_balance', 50000);
    }

    public function test_parent_cannot_load_fees_for_unlinked_peer_student(): void
    {
        $this->actingAs($this->parent)
            ->getJson(route('parent.children.fees', $this->otherStudent->id))
            ->assertForbidden();
    }

    public function test_parent_cannot_load_grades_for_unlinked_peer_student(): void
    {
        $this->actingAs($this->parent)
            ->getJson(route('parent.children.grades', $this->otherStudent->id))
            ->assertForbidden();
    }

    public function test_parent_cannot_load_attendance_for_unlinked_peer_student(): void
    {
        $this->actingAs($this->parent)
            ->getJson(route('parent.children.attendance', $this->otherStudent->id))
            ->assertForbidden();
    }

    public function test_teacher_cannot_hit_parent_child_data_endpoints(): void
    {
        $teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->getJson(route('parent.children.fees', $this->childA->id))
            ->assertRedirect('/teacher/dashboard');
    }

    public function test_children_page_lists_only_linked_students(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children'))
            ->assertOk()
            ->assertSee('Alice Linked')
            ->assertDontSee('Other Student');
    }
}
