<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentAcademicLatestScopingTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;
    private int $currentYearId;
    private int $oldYearId;
    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        // ── Seed minimal lookup data ──
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── School ──
        $this->schoolId = DB::table('schools')->insertGetId([
            'name'                 => 'Scoping Test School',
            'slug'                 => 'scoping-test',
            'email'                => 'scoping@test.sch.ug',
            'phone'                => '+256700000001',
            'status'               => 1,
            'registration_country' => 'Uganda',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // ── Academic years ──
        $this->oldYearId = DB::table('academic_years')->insertGetId([
            'school_id'   => $this->schoolId,
            'name'        => '2024',
            'description' => 'Old Academic Year',
            'status'      => 0,
            'start_date'  => '2024-01-01',
            'end_date'    => '2024-12-31',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->currentYearId = DB::table('academic_years')->insertGetId([
            'school_id'   => $this->schoolId,
            'name'        => '2025',
            'description' => 'Current Academic Year',
            'status'      => 1,
            'start_date'  => '2025-01-01',
            'end_date'    => '2025-12-31',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // ── Student ──
        $this->studentId = DB::table('users')->insertGetId([
            'school_id'            => $this->schoolId,
            'usergroup_id'         => 6,
            'name'                 => 'Test Student',
            'email'                => 'student@scoping.test',
            'mobile_no'            => '+256700000002',
            'password'             => bcrypt('password'),
            'status'               => 'active',
            'email_verified'       => 1,
            'registration_number'  => 'REG-SCOPING-001',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    /** @test */
    public function returns_current_year_record_when_current_has_higher_id(): void
    {
        // This is the "normal promotion" scenario: old record created first,
        // then promotion creates a new record in the current year.
        $old = StudentAcademic::create([
            'school_id'        => $this->schoolId,
            'academic_year_id' => $this->oldYearId,
            'user_id'          => $this->studentId,
            'standardLink_id'  => null,
            'height'           => 120.00,
        ]);

        $current = StudentAcademic::create([
            'school_id'        => $this->schoolId,
            'academic_year_id' => $this->currentYearId,
            'user_id'          => $this->studentId,
            'standardLink_id'  => null,
            'height'           => 130.00,
        ]);

        // old.id < current.id — the ID order matches promotion order
        $this->assertLessThan($current->id, $old->id);

        $student = User::find($this->studentId);
        $latest = $student->studentAcademicLatest;

        $this->assertNotNull($latest);
        $this->assertEquals($this->currentYearId, $latest->academic_year_id);
        $this->assertEquals(130.00, $latest->height);
    }

    /** @test */
    public function returns_current_year_record_when_current_has_lower_id(): void
    {
        // This is the "backfill" scenario that the original orderByDesc('id')
        // bug would fail on: a correction insert gives the old-year record
        // a HIGHER id than the current-year record.
        $current = StudentAcademic::create([
            'school_id'        => $this->schoolId,
            'academic_year_id' => $this->currentYearId,
            'user_id'          => $this->studentId,
            'standardLink_id'  => null,
            'height'           => 130.00,
        ]);

        $old = StudentAcademic::create([
            'school_id'        => $this->schoolId,
            'academic_year_id' => $this->oldYearId,
            'user_id'          => $this->studentId,
            'standardLink_id'  => null,
            'height'           => 120.00,
        ]);

        // old.id > current.id — the ID order is INVERTED relative to promotion
        $this->assertGreaterThan($current->id, $old->id);

        $student = User::find($this->studentId);
        $latest = $student->studentAcademicLatest;

        $this->assertNotNull($latest);
        $this->assertEquals($this->currentYearId, $latest->academic_year_id);
        $this->assertEquals(130.00, $latest->height);
    }

    /** @test */
    public function returns_null_when_student_has_no_current_year_record(): void
    {
        // Only an old-year record exists
        StudentAcademic::create([
            'school_id'        => $this->schoolId,
            'academic_year_id' => $this->oldYearId,
            'user_id'          => $this->studentId,
            'standardLink_id'  => null,
        ]);

        $student = User::find($this->studentId);
        $latest = $student->studentAcademicLatest;

        $this->assertNull($latest);
    }

    /** @test */
    public function eager_loading_returns_current_year_record(): void
    {
        StudentAcademic::create([
            'school_id'        => $this->schoolId,
            'academic_year_id' => $this->oldYearId,
            'user_id'          => $this->studentId,
            'standardLink_id'  => null,
        ]);

        StudentAcademic::create([
            'school_id'        => $this->schoolId,
            'academic_year_id' => $this->currentYearId,
            'user_id'          => $this->studentId,
            'standardLink_id'  => null,
        ]);

        // Eager loading path used by StudentDetailsController
        $student = User::with('studentAcademicLatest')->find($this->studentId);

        $this->assertNotNull($student->studentAcademicLatest);
        $this->assertEquals($this->currentYearId, $student->studentAcademicLatest->academic_year_id);
    }
}
