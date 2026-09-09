<?php

namespace Tests\Feature;

use App\Mail\TeacherInviteMail;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ClassTeacherInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClassTeacherInviteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // Seed usergroups required for user creation validation
        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    // ===================================================================
    // Happy paths
    // ===================================================================

    public function test_invites_new_teacher_and_sets_class_teacher_fields(): void
    {
        $inviter = $this->admin();
        $link = $this->stream($inviter->school_id);
        $section = $link->section;
        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'name'  => 'New Teacher',
            'email' => 'newteacher@school.ug',
            'phone' => '0777000000',
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('invited as class teacher', $result['message']);
        $this->assertArrayHasKey('teacher_id', $result);

        // CT fields updated
        $link->refresh();
        $section->refresh();
        $this->assertSame($result['teacher_id'], $link->class_teacher_id);
        $this->assertSame($result['teacher_id'], $section->class_teacher_id);

        // User created with correct attributes
        $teacher = User::find($result['teacher_id']);
        $this->assertSame(5, $teacher->usergroup_id);
        $this->assertSame((int) $inviter->school_id, $teacher->school_id);
        $this->assertSame('newteacher@school.ug', $teacher->email);
        $this->assertSame(1, $teacher->is_reset);
        $this->assertSame(1, $teacher->email_verified);
        $this->assertSame('0777000000', $teacher->mobile_no);

        // Userprofile created
        $this->assertDatabaseHas('userprofiles', [
            'user_id'      => $teacher->id,
            'school_id'    => $inviter->school_id,
            'usergroup_id' => 5,
            'firstname'    => 'New Teacher',
        ]);

        // Email queued with credentials
        Mail::assertQueued(TeacherInviteMail::class, function ($mail) {
            return $mail->email === 'newteacher@school.ug'
                && $mail->password !== null
                && $mail->password !== '';
        });
    }

    public function test_reassigns_existing_teacher_without_credentials_email(): void
    {
        $inviter = $this->admin();
        $existingTeacher = $this->teacher($inviter->school_id, 'existing@school.ug');
        $link = $this->stream($inviter->school_id);
        $section = $link->section;

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'email'                => 'existing@school.ug',
            'existing_teacher_id'  => $existingTeacher->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('assigned as class teacher', $result['message']);
        $this->assertSame($existingTeacher->id, $result['teacher_id']);

        $link->refresh();
        $section->refresh();
        $this->assertSame($existingTeacher->id, $link->class_teacher_id);
        $this->assertSame($existingTeacher->id, $section->class_teacher_id);

        // Reassignment email — password must be null/empty
        Mail::assertQueued(TeacherInviteMail::class, function ($mail) {
            return $mail->email === 'existing@school.ug'
                && ($mail->password === null || $mail->password === '');
        });
    }

    // ===================================================================
    // Validation failures
    // ===================================================================

    public function test_rejects_inviter_without_school(): void
    {
        $inviter = User::factory()->create([
            'school_id'    => 0,
            'usergroup_id' => 3,
        ]);
        $link = $this->stream($this->createSchool()->id);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'name'  => 'Teacher',
            'email' => 't@school.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Inviter is not assigned to a school.', $result['message']);
    }

    public function test_rejects_wrong_school_standard_link(): void
    {
        $inviter = $this->admin();
        $otherSchoolLink = $this->stream($this->createSchool()->id);

        $result = ClassTeacherInviteService::invite($inviter, $otherSchoolLink, [
            'name'  => 'Teacher',
            'email' => 't@school.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Class not found or not in your school.', $result['message']);
    }

    public function test_rejects_inactive_standard_link(): void
    {
        $inviter = $this->admin();
        $schoolId = $inviter->school_id;
        $link = $this->stream($schoolId, ['status' => 0]);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'name'  => 'Teacher',
            'email' => 't@school.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Class not found or not in your school.', $result['message']);
    }

    public function test_rejects_inactive_section(): void
    {
        $inviter = $this->admin();
        $schoolId = $inviter->school_id;
        $section = Section::create([
            'school_id' => $schoolId,
            'name'      => 'Inactive Class',
            'status'    => 0,
        ]);
        $standard = Standard::create(['school_id' => $schoolId, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $year = AcademicYear::create([
            'school_id'   => $schoolId,
            'name'        => '2026',
            'start_date'  => '2026-01-01',
            'end_date'    => '2026-12-31',
            'status'      => 1,
        ]);
        $link = StandardLink::create([
            'school_id'        => $schoolId,
            'academic_year_id' => $year->id,
            'standard_id'      => $standard->id,
            'section_id'       => $section->id,
            'status'           => 1,
        ]);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'name'  => 'Teacher',
            'email' => 't@school.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Class section not found or not in your school.', $result['message']);
    }

    public function test_rejects_duplicate_email(): void
    {
        $inviter = $this->admin();
        $link = $this->stream($inviter->school_id);

        User::factory()->create(['email' => 'dup@school.ug']);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'name'  => 'Teacher',
            'email' => 'dup@school.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }

    public function test_rejects_missing_name_for_new_teacher(): void
    {
        $inviter = $this->admin();
        $link = $this->stream($inviter->school_id);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'name'  => '',
            'email' => 't@school.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Teacher name must be at least 3 characters.', $result['message']);
    }

    public function test_rejects_invalid_email(): void
    {
        $inviter = $this->admin();
        $link = $this->stream($inviter->school_id);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'name'  => 'Teacher',
            'email' => 'not-an-email',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('A valid email address is required.', $result['message']);
    }

    // ===================================================================
    // Existing-teacher edge cases
    // ===================================================================

    public function test_rejects_nonexistent_existing_teacher_id(): void
    {
        $inviter = $this->admin();
        $link = $this->stream($inviter->school_id);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'email'               => 'any@school.ug',
            'existing_teacher_id' => 999999,
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Teacher not found or not in your school.', $result['message']);
    }

    public function test_rejects_existing_teacher_from_other_school(): void
    {
        $inviter = $this->admin();
        $otherSchool = $this->createSchool();
        $otherTeacher = User::factory()->create([
            'school_id'    => $otherSchool->id,
            'usergroup_id' => 5,
            'email'        => 'other@school.ug',
        ]);
        $link = $this->stream($inviter->school_id);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'email'               => 'other@school.ug',
            'existing_teacher_id' => $otherTeacher->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Teacher not found or not in your school.', $result['message']);
    }

    public function test_rejects_existing_teacher_with_wrong_email(): void
    {
        $inviter = $this->admin();
        $existingTeacher = $this->teacher($inviter->school_id, 'correct@school.ug');
        $link = $this->stream($inviter->school_id);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'email'               => 'wrong@school.ug',
            'existing_teacher_id' => $existingTeacher->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('The email does not match the selected teacher.', $result['message']);
    }

    public function test_rejects_reassigning_already_assigned_teacher(): void
    {
        $inviter = $this->admin();
        $existingTeacher = $this->teacher($inviter->school_id, 'already@school.ug');
        $link = $this->stream($inviter->school_id, ['class_teacher_id' => $existingTeacher->id]);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'email'               => 'already@school.ug',
            'existing_teacher_id' => $existingTeacher->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('is already the class teacher', $result['message']);
    }

    public function test_rejects_non_teacher_usergroup_for_existing_teacher(): void
    {
        $inviter = $this->admin();
        $studentUser = User::factory()->create([
            'school_id'    => $inviter->school_id,
            'usergroup_id' => 6,
            'email'        => 'student@school.ug',
        ]);
        $link = $this->stream($inviter->school_id);

        $result = ClassTeacherInviteService::invite($inviter, $link, [
            'email'               => 'student@school.ug',
            'existing_teacher_id' => $studentUser->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Teacher not found or not in your school.', $result['message']);
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    private function createSchool(): School
    {
        return School::create([
            'name'   => 'Test School ' . uniqid(),
            'slug'   => 'test-school-' . uniqid(),
            'email'  => 'test-' . uniqid() . '@school.ug',
            'phone'  => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);
    }

    private function admin(): User
    {
        $school = $this->createSchool();
        return User::factory()->create([
            'school_id'    => $school->id,
            'usergroup_id' => 3,
            'name'         => 'Admin User',
        ]);
    }

    private function teacher(int $schoolId, string $email): User
    {
        return User::factory()->create([
            'school_id'    => $schoolId,
            'usergroup_id' => 5,
            'email'        => $email,
            'name'         => 'Existing Teacher',
        ]);
    }

    /**
     * @param int   $schoolId
     * @param array $overrides Optional overrides for StandardLink creation
     */
    private function stream(int $schoolId, array $overrides = []): StandardLink
    {
        $school = School::find($schoolId);
        if (! $school) {
            throw new \RuntimeException('School not found: ' . $schoolId);
        }

        $section = Section::create([
            'school_id' => $schoolId,
            'name'      => 'P1 A',
            'status'    => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $schoolId,
            'name'      => 'primary',
            'order'     => 1,
            'status'    => 1,
        ]);

        $year = AcademicYear::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'name'        => '2026',
                'description' => 'Test year',
                'start_date'  => '2026-01-01',
                'end_date'    => '2026-12-31',
                'status'      => 1,
            ]
        );

        return StandardLink::create(array_merge([
            'school_id'        => $schoolId,
            'academic_year_id' => $year->id,
            'standard_id'      => $standard->id,
            'section_id'       => $section->id,
            'status'           => 1,
            'stream'           => 'A',
        ], $overrides));
    }
}
