<?php

namespace Tests\Feature\Toshi;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ToshiAddStudentWithoutClassTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Toshi School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        // Intentionally created FIRST: if the legacy first-link fallback ever
        // returns, the student would silently join this class.
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Admin '.Str::random(4),
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_toshi_add_student_without_class_does_not_pick_first_class(): void
    {
        $result = ToshiActionService::addStudent($this->admin, [
            'name' => 'Pastel Nakato',
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? 'addStudent failed');

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $this->assertNotNull($student);

        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertNull($academic->standardLink_id);
        $this->assertNotNull($academic->klassapp_student_id);
    }

    public function test_toshi_add_student_with_class_still_assigns(): void
    {
        $result = ToshiActionService::addStudent($this->admin, [
            'name' => 'Ssentongo Basic',
            'class_name' => 'P.1',
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? 'addStudent failed');

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertNotNull($academic->standardLink_id);
        $this->assertEquals('P.1', $academic->standardLink->section->name);
    }
}
