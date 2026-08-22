<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherListCrossSchoolDeleteTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $teacherB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create([
            'name' => 'School A', 'slug' => 'school-a',
            'email' => 'a@tl.test', 'phone' => '+256700000001',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->schoolB = School::create([
            'name' => 'School B', 'slug' => 'school-b',
            'email' => 'b@tl.test', 'phone' => '+256700000002',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        foreach ([$this->schoolA, $this->schoolB] as $school) {
            AcademicYear::create([
                'school_id' => $school->id, 'name' => '2026 Test',
                'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
            ]);
            Standard::create([
                'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
            ]);
        }

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3,
            'email' => 'admin.a@tl.test',
        ]);

        $this->teacherB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 5,
            'name' => 'Teacher B Unique',
            'email' => 'teacher.b@tl.test',
        ]);
    }

    /** @test */
    public function admin_cannot_delete_another_schools_teacher()
    {
        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->delete("/teacher/delete/{$this->teacherB->name}");

        $response->assertNotFound();
        $this->assertDatabaseHas('users', [
            'id' => $this->teacherB->id,
            'email' => 'teacher.b@tl.test',
        ]);
        $this->assertNull(User::find($this->teacherB->id)->deleted_at);
    }
}
