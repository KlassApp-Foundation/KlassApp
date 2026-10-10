<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClasslessStudentHandlingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private StandardLink $link;
    private User $admin;
    private User $teacher;
    private User $enrolled;
    private User $classless;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Classless School '.Str::random(6),
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

        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
            'class_teacher_id' => null,
        ]);

        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Admin Ugu',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Teacher Adu',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        $this->link->update(['class_teacher_id' => $this->teacher->id]);

        $engine = app(OnboardingEngine::class);
        $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Zawadi Enrolled', 'class' => 'P.1'],
            ['name' => 'Mirembe Classless'],
        ]);

        $this->enrolled = User::where('usergroup_id', 6)->where('name', 'Zawadi Enrolled')->first();
        $this->classless = User::where('usergroup_id', 6)->where('name', 'Mirembe Classless')->first();
    }

    public function test_student_list_shows_no_class_label(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertOk();
        $response->assertSee('Mirembe Classless');
        $response->assertSee('Zawadi Enrolled');
        $response->assertSee('No class', false);
        $response->assertSee('data-chip="noclass"', false);
    }

    public function test_needs_a_class_filter_shows_only_classless_students(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/students?standard=none');

        $response->assertOk();
        $response->assertSee('Mirembe Classless');
        $response->assertDontSee('Zawadi Enrolled');
    }

    public function test_class_filter_still_returns_only_that_class(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/students?standard='.$this->link->id);

        $response->assertOk();
        $response->assertSee('Zawadi Enrolled');
        $response->assertDontSee('Mirembe Classless');
    }

    public function test_profile_details_resource_renders_no_class_for_classless_student(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/student/show/details/'.$this->classless->name);

        $response->assertOk();
        $response->assertJsonFragment(['class' => 'No class']);
    }

    public function test_profile_details_resource_still_reports_class_for_enrolled_student(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/student/show/details/'.$this->enrolled->name);

        $response->assertOk();
        $response->assertJsonFragment(['class' => 'P.1']);
    }

    public function test_student_fees_page_renders_for_classless_student(): void
    {
        if (! class_exists(\Gegok12\Fee\Models\Fee::class) && ! class_exists(\App\Models\Fee::class)) {
            $this->markTestSkipped('Fee package not installed in the core test env; verified on staging instead.');
        }

        $response = $this->actingAs($this->admin)->get('/admin/student/show/fees/'.$this->classless->name);

        $response->assertOk();
    }

    public function test_student_attendance_page_renders_for_classless_student(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/student/show/attendance/'.$this->classless->name);

        $response->assertOk();
    }

    public function test_teacher_attendance_page_renders_with_classless_student_in_school(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.attendance.index'));

        $response->assertOk();
    }

    public function test_teacher_marks_list_renders_with_classless_student_in_school(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.exam.marks'));

        $response->assertOk();
    }

    public function test_fee_payments_page_renders_with_classless_student_in_school(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.fee-payments'));

        $response->assertOk();
    }

    public function test_report_cards_index_renders_with_classless_student_in_school(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/reports/cards');

        $response->assertOk();
    }
}
