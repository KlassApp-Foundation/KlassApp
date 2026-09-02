<?php

namespace Tests\Feature\Toshi;

use App\Mail\TeacherInviteMail;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AddTeacherInviteEmailTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private User $admin;

    private StandardLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Teacher Invite School',
            'slug' => 'teacher-invite-school',
            'email' => 'teacher-invite@test.sch.ug',
            'phone' => '+256700000088',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'UnlimitedTeachers',
            'display_name' => 'UnlimitedTeachers',
            'cycle' => 30,
            'no_of_students' => 999,
            'no_of_users' => 999,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CurrentPlan::create([
            'school_id' => $this->schoolId,
            'plan_id' => $planId,
            'status' => 'running',
        ]);

        AcademicYear::create([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->schoolId,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'P.3',
            'status' => 1,
        ]);

        $yearId = AcademicYear::where('school_id', $this->schoolId)->value('id');

        $this->link = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $yearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'email' => 'admin-invite@test.sch.ug',
            'password' => bcrypt('admin-secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_add_teacher_queues_invite_mail_with_plain_password(): void
    {
        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Jane Teacher',
            'email' => 'jane.teacher@test.sch.ug',
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertStringContainsString('invite email', strtolower($result['message']));
        $this->assertStringNotContainsString('password: `', $result['message']);

        $teacher = User::where('email', 'jane.teacher@test.sch.ug')->first();
        $this->assertNotNull($teacher);
        $this->assertSame(5, (int) $teacher->usergroup_id);
        $this->assertSame(1, (int) $teacher->is_reset);

        Mail::assertQueued(TeacherInviteMail::class, function (TeacherInviteMail $mail) use ($teacher) {
            return $mail->email === 'jane.teacher@test.sch.ug'
                && $mail->password !== ''
                && $mail->className === null
                && Hash::check($mail->password, $teacher->password);
        });
    }

    public function test_add_teacher_with_class_assigns_class_teacher_and_names_class_in_mail(): void
    {
        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'CT Teacher',
            'email' => 'ct.teacher@test.sch.ug',
            'class' => 'P.3',
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertStringContainsString('class teacher', strtolower($result['message']));

        $teacher = User::where('email', 'ct.teacher@test.sch.ug')->firstOrFail();
        $this->link->refresh();
        $this->assertSame($teacher->id, (int) $this->link->class_teacher_id);
        $this->assertSame($teacher->id, (int) $this->link->section->fresh()->class_teacher_id);

        Mail::assertQueued(TeacherInviteMail::class, function (TeacherInviteMail $mail) {
            return $mail->email === 'ct.teacher@test.sch.ug'
                && $mail->className === 'P.3'
                && $mail->schoolName === 'Teacher Invite School';
        });
    }

    public function test_add_teacher_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'dup@test.sch.ug',
            'status' => 'active',
        ]);

        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Dup Teacher',
            'email' => 'dup@test.sch.ug',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
        Mail::assertNothingQueued();
    }
}
