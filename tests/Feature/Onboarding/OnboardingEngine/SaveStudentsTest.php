<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\StudentIdGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaveStudentsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private StandardLink $link;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Test School '.Str::random(6),
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

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'status' => 1,
        ]);

        // Seed usergroups so User::create works
        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed a student_id_sequences row so StudentIdGeneratorService works
        \DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);
    }

    public function test_creates_student_user_with_random_password(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Alice Student'],
        ]);

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $this->assertNotNull($student);
        $this->assertEquals(6, $student->usergroup_id);
        $this->assertEquals($this->school->id, $student->school_id);
        // Random password — NOT 'password'
        $this->assertFalse(\Hash::check('password', $student->password));
        $this->assertEquals(60, strlen($student->password));
    }

    public function test_sets_is_reset_on_created_student(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Reset Student'],
        ]);

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $this->assertEquals(1, $student->is_reset);
    }

    public function test_creates_userprofile_for_student(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Profile Student'],
        ]);

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $this->assertNotNull($student);

        $profile = Userprofile::where('user_id', $student->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(6, $profile->usergroup_id);
    }

    public function test_generates_klassapp_id_for_student(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Klass Student'],
        ]);

        $this->assertNotEmpty($result['created'][0]['klassapp_id']);

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $this->assertNotNull($student->registration_number);
    }

    public function test_creates_student_academic_with_class_assignment(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Assigned Student', 'class' => 'P.1'],
        ]);

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $this->assertNotNull($student);

        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertEquals($this->link->id, $academic->standardLink_id);
        $this->assertEquals($this->school->id, $academic->school_id);
        $this->assertEquals($this->year->id, $academic->academic_year_id);
    }

    public function test_assigns_to_first_class_when_no_class_specified(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'No Class Student'],
        ]);

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        // Falls back to first StandardLink
        $this->assertEquals($this->link->id, $academic->standardLink_id);
    }

    public function test_marks_email_verified(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Verified Student'],
        ]);

        $student = User::where('usergroup_id', 6)->where('school_id', $this->school->id)->first();
        $this->assertEquals(1, $student->email_verified);
    }

    public function test_skips_empty_name(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => ''],
        ]);

        $this->assertCount(0, $result['created']);
        $this->assertCount(1, $result['skipped']);
    }

    public function test_returns_created_and_skipped_arrays(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'Good Student'],
            ['name' => ''],
        ]);

        $this->assertCount(1, $result['created']);
        $this->assertEquals('Good Student', $result['created'][0]['name']);
        $this->assertCount(1, $result['skipped']);
    }

    public function test_handles_empty_student_list(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, []);

        $this->assertCount(0, $result['created']);
        $this->assertCount(0, $result['skipped']);
    }
}
