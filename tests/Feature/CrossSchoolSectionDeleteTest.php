<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrossSchoolSectionDeleteTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private Section $sectionA;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create([
            'name' => 'School A', 'slug' => 'school-a',
            'email' => 'a@test.sch.ug', 'phone' => '+256700000001',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->schoolB = School::create([
            'name' => 'School B', 'slug' => 'school-b',
            'email' => 'b@test.sch.ug', 'phone' => '+256700000002',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        // AcademicYear + Standard are required for privilegeconditions middleware
        // to let requests through; without them we get redirected to /admin/dashboard.
        foreach ([$this->schoolA, $this->schoolB] as $school) {
            AcademicYear::create([
                'school_id' => $school->id,
                'name' => '2026 Test',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ]);
            Standard::create([
                'school_id' => $school->id,
                'name' => 'primary',
                'order' => 1,
                'status' => 1,
            ]);
        }

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'admin.a@test.sch.ug',
        ]);

        $this->sectionA = Section::create([
            'school_id' => $this->schoolA->id,
            'name' => 'P.1 A',
            'status' => 1,
        ]);
    }

    /** @test */
    public function admin_can_delete_their_own_section()
    {
        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->delete("/admin/classes/delete/{$this->sectionA->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('sections', ['id' => $this->sectionA->id]);
    }

    /** @test */
    public function admin_cannot_delete_another_schools_section()
    {
        $sectionB = Section::create([
            'school_id' => $this->schoolB->id,
            'name' => 'P.1 B',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->delete("/admin/classes/delete/{$sectionB->id}");

        $response->assertForbidden();
        $this->assertNotSoftDeleted('sections', ['id' => $sectionB->id]);
    }
}
