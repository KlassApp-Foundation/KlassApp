<?php

namespace Tests\Feature;

use App\Models\Academics\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTimetableIndexTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $academicYearId;

    private int $sectionId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Timetable Index School',
            'slug' => 'timetable-index-school',
            'email' => 'timetable-index@test.sch.ug',
            'phone' => '+256700000010',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Academic year + standard satisfy MustBePrivilege (fully onboarded gate).
        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('standards')->insert([
            'school_id' => $this->schoolId,
            'name' => 'Primary 1',
            'order' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sectionId = DB::table('sections')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => 'P1 East',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'name' => 'Timetable Admin',
            'email' => 'timetable-admin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_admin_timetable_loads_with_no_slots(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/timetable');

        $response->assertOk();
        $response->assertSee('Timetable', false);
        $response->assertSee('Select a class above to view its timetable.', false);
        $response->assertDontSee('ErrorException', false);
    }

    public function test_admin_timetable_loads_with_existing_slots(): void
    {
        $standardId = (int) DB::table('standards')->where('school_id', $this->schoolId)->value('id');

        $subjectId = DB::table('subjects')->insertGetId([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standardId,
            'section_id' => $this->sectionId,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->schoolId,
            'name' => 'Math Teacher',
            'email' => 'math-teacher@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        TimetableSlot::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'section_id' => $this->sectionId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'A1',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/timetable?section_id='.$this->sectionId);

        $response->assertOk();
        $response->assertSee('MATHEMATICS', false);
        $response->assertSee('Monday', false);
        $response->assertSee('08:00', false);
        $response->assertDontSee('ErrorException', false);
    }

    public function test_named_admin_timetable_index_route_points_at_timetable_path(): void
    {
        $this->assertSame(url('/admin/timetable'), route('admin.timetable.index'));
    }
}
