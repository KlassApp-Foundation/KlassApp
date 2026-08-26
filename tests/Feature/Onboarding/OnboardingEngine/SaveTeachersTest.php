<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaveTeachersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private StandardLink $link;
    private Subject $subject;

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
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'code' => 'MTH',
            'status' => 1,
        ]);

        // Seed usergroups so User::create works
        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_creates_teacher_user_with_random_password(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'John Teacher', 'email' => 'john@test.sch.ug'],
        ]);

        $teacher = User::where('email', 'john@test.sch.ug')->first();
        $this->assertNotNull($teacher);
        $this->assertEquals(5, $teacher->usergroup_id);
        $this->assertEquals($this->school->id, $teacher->school_id);
        // Random password — NOT 'password'
        $this->assertFalse(\Hash::check('password', $teacher->password));
        // Password is a real bcrypt hash (60 chars)
        $this->assertEquals(60, strlen($teacher->password));
        // Note: users.name is overwritten by UserprofileObserver to a slug,
        // so we don't assert name here — display name lives in userprofiles.firstname
    }

    public function test_sets_is_reset_on_created_teacher(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'Reset Teacher', 'email' => 'reset@test.sch.ug'],
        ]);

        $teacher = User::where('email', 'reset@test.sch.ug')->first();
        $this->assertEquals(1, $teacher->is_reset);
    }

    public function test_creates_userprofile_for_teacher(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'Profile Teacher', 'email' => 'profile@test.sch.ug', 'phone' => '+256700123456'],
        ]);

        $teacher = User::where('email', 'profile@test.sch.ug')->first();
        $this->assertNotNull($teacher);

        $profile = Userprofile::where('user_id', $teacher->id)->first();
        $this->assertNotNull($profile);
        // Note: Userprofile has a getFirstNameAttribute accessor that uppercases
        $this->assertEquals('PROFILE TEACHER', $profile->firstname);
        $this->assertEquals(5, $profile->usergroup_id);
        // Phone is stored on users.mobile_no (fillable) and userprofiles.alternate_no
        $this->assertEquals('+256700123456', $profile->alternate_no);
    }

    public function test_creates_teacherlink_when_class_and_subject_provided(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'Linked Teacher', 'email' => 'linked@test.sch.ug', 'standardLink_id' => $this->link->id, 'subject_id' => $this->subject->id],
        ]);

        $teacher = User::where('email', 'linked@test.sch.ug')->first();
        $this->assertNotNull($teacher);

        $teacherlink = Teacherlink::where('teacher_id', $teacher->id)->first();
        $this->assertNotNull($teacherlink);
        $this->assertEquals($this->link->id, $teacherlink->standardLink_id);
        $this->assertEquals($this->subject->id, $teacherlink->subject_id);
    }

    public function test_does_not_create_teacherlink_without_class_and_subject(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'No Link Teacher', 'email' => 'nolink@test.sch.ug'],
        ]);

        $teacher = User::where('email', 'nolink@test.sch.ug')->first();
        $this->assertNotNull($teacher);
        $this->assertEquals(0, Teacherlink::where('teacher_id', $teacher->id)->count());
    }

    public function test_email_dedup_generates_fallback_email(): void
    {
        $engine = app(OnboardingEngine::class);

        // Pre-create a user with the same email in this school
        User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Existing Teacher',
            'email' => 'dup@test.sch.ug',
            'password' => bcrypt('whatever'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $result = $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'New Teacher', 'email' => 'dup@test.sch.ug'],
        ]);

        // The new teacher should have been created with a different email
        $teachers = User::where('school_id', $this->school->id)->where('usergroup_id', 5)->get();
        $this->assertCount(2, $teachers);
        $newTeacher = $teachers->last();
        $this->assertNotEquals('dup@test.sch.ug', $newTeacher->email);
        $this->assertStringContainsString('@', $newTeacher->email);
    }

    public function test_skips_invalid_teacher_entries(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveTeachers($this->school, $this->year, [
            ['name' => '', 'email' => ''],  // empty name and email
            ['name' => 'Valid', 'email' => ''],  // empty email
            ['name' => 'Also Valid', 'email' => 'not-an-email'],  // invalid email
        ]);

        // Only the invalid entries should be skipped; no teachers created
        $this->assertCount(0, $result['created'] ?? []);
    }

    public function test_returns_created_and_skipped_arrays(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'Good Teacher', 'email' => 'good@test.sch.ug', 'phone' => '+256700111222'],
            ['name' => '', 'email' => ''],  // skipped
        ]);

        $this->assertCount(1, $result['created']);
        $this->assertEquals('Good Teacher', $result['created'][0]['name']);
        $this->assertCount(1, $result['skipped']);
    }

    public function test_handles_empty_teacher_list(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveTeachers($this->school, $this->year, []);

        $this->assertCount(0, $result['created']);
        $this->assertCount(0, $result['skipped']);
    }

    public function test_marks_email_verified(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'Verified Teacher', 'email' => 'verified@test.sch.ug'],
        ]);

        $teacher = User::where('email', 'verified@test.sch.ug')->first();
        $this->assertEquals(1, $teacher->email_verified);
    }

    public function test_teacherlink_is_idempotent(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'Idempotent Teacher', 'email' => 'idempotent@test.sch.ug', 'standardLink_id' => $this->link->id, 'subject_id' => $this->subject->id],
        ]);

        $teacher = User::where('email', 'idempotent@test.sch.ug')->first();

        // Call again — should not duplicate the Teacherlink
        $engine->saveTeachers($this->school, $this->year, [
            ['name' => 'Idempotent Teacher', 'email' => 'idempotent@test.sch.ug', 'standardLink_id' => $this->link->id, 'subject_id' => $this->subject->id],
        ]);

        // Only one Teacherlink for this teacher
        $this->assertEquals(1, Teacherlink::where('teacher_id', $teacher->id)->count());
    }
}
