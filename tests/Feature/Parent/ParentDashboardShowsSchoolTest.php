<?php

namespace Tests\Feature\Parent;

use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The parent's child selector names the school next to the class, so a parent
 * with children in different schools can tell them apart (23 Nov 2026).
 */
class ParentDashboardShowsSchoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_selector_shows_the_childs_school(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create(['name' => 'Hillside Junior School', 'email' => 'office@hillside.test', 'status' => 1]);

        $parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'email' => 'parent.school-name@test.ug',
        ]);

        Userprofile::create([
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Grace',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
        ]);

        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        $response = $this->actingAs($parent)->get(route('parent.dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="child-school-'.$student->id.'"', false);
        $response->assertSeeInOrder(['data-testid="child-school-'.$student->id.'"', 'Hillside Junior School'], false);
    }
}
