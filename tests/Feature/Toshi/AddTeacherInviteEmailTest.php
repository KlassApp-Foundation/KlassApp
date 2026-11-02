<?php

namespace Tests\Feature\Toshi;

use App\Mail\TeacherInviteLinkMail;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_add_teacher_queues_link_only_invite_mail_without_a_password(): void
    {
        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Jane Teacher',
            'email' => 'jane.teacher@example.test',
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertArrayNotHasKey('password', $result);
        $this->assertStringNotContainsString('password:', strtolower((string) json_encode($result)));
        $this->assertDatabaseMissing('users', ['email' => 'jane.teacher@example.test']);

        $invite = TeacherInvite::query()
            ->where('school_id', $this->schoolId)
            ->where('email', 'jane.teacher@example.test')
            ->first();
        $this->assertNotNull($invite);
        $this->assertNull($invite->user_id);
        $this->assertSame(64, strlen((string) $invite->token_hash));
        $this->assertStringNotContainsString($invite->token_hash, (string) json_encode($result));

        Mail::assertQueued(TeacherInviteLinkMail::class, function (TeacherInviteLinkMail $mail) {
            return str_contains($mail->inviteUrl, '/invite/teacher/')
                && $mail->className === null
                && ! property_exists($mail, 'password');
        });

        $mail = Mail::queued(TeacherInviteLinkMail::class)->first();
        $html = $mail->render();
        $token = basename((string) parse_url($mail->inviteUrl, PHP_URL_PATH));
        $this->assertStringContainsString('Set your password', $html);
        $this->assertStringContainsString($mail->inviteUrl, $html);
        $this->assertStringNotContainsString('Password:', $html);
        $this->assertStringNotContainsString($token, (string) json_encode($result));
        $this->assertSame(hash('sha256', $token), $invite->token_hash);
    }

    public function test_add_teacher_with_class_invites_for_that_class_without_storing_a_password(): void
    {
        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'CT Teacher',
            'email' => 'ct.teacher@example.test',
            'class' => 'P.3',
        ]);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertArrayNotHasKey('password', $result);
        $this->assertStringContainsString('invite', strtolower($result['message']));
        $this->assertDatabaseMissing('users', ['email' => 'ct.teacher@example.test']);
        $this->assertNull($this->link->fresh()->class_teacher_id);

        $invite = TeacherInvite::query()->where('email', 'ct.teacher@example.test')->firstOrFail();
        $this->assertSame($this->schoolId, (int) $invite->school_id);
        $this->assertSame($this->link->id, (int) $invite->standard_link_id);

        Mail::assertQueued(TeacherInviteLinkMail::class, function (TeacherInviteLinkMail $mail) {
            return $mail->className === 'P.3'
                && $mail->schoolName === 'Teacher Invite School';
        });
    }

    public function test_add_teacher_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'dup@example.test',
            'status' => 'active',
        ]);

        $result = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Dup Teacher',
            'email' => 'dup@example.test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertStringContainsString('already exists', $result['message']);
        Mail::assertNothingQueued();
    }

    public function test_add_teacher_matches_class_name_variants_and_not_another_school(): void
    {
        $yearId = AcademicYear::query()->where('school_id', $this->schoolId)->value('id');
        $standardId = Standard::query()->where('school_id', $this->schoolId)->value('id');
        $section = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'P.7',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $yearId,
            'standard_id' => $standardId,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $otherId = DB::table('schools')->insertGetId([
            'name' => 'Other Class School',
            'slug' => 'other-class-school',
            'email' => 'other-class@example.test',
            'phone' => '+254700000099',
            'status' => 1,
            'registration_country' => 'Kenya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherYear = AcademicYear::create([
            'school_id' => $otherId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
            'status' => 1,
        ]);
        $otherStandard = Standard::create([
            'school_id' => $otherId,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $otherSection = Section::create([
            'school_id' => $otherId,
            'name' => 'S.4',
            'status' => 1,
        ]);
        $otherLink = StandardLink::create([
            'school_id' => $otherId,
            'academic_year_id' => $otherYear->id,
            'standard_id' => $otherStandard->id,
            'section_id' => $otherSection->id,
            'status' => 1,
        ]);

        foreach (['p7', 'P7', 'P 7', 'P.7'] as $index => $typed) {
            $email = "variant-{$index}@example.test";
            $result = ToshiActionService::addTeacher($this->admin, [
                'name' => 'Variant Teacher',
                'email' => $email,
                'class' => $typed,
            ]);

            $this->assertTrue($result['success'], $typed.': '.($result['message'] ?? ''));
            $this->assertSame($link->id, (int) $result['standardLink_id']);
            $invite = TeacherInvite::query()->where('email', $email)->first();
            $this->assertNotNull($invite);
            $this->assertSame($this->schoolId, (int) $invite->school_id);
            $this->assertSame($link->id, (int) $invite->standard_link_id);
            $this->assertNotSame($otherLink->id, (int) $invite->standard_link_id);
        }

        $missing = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Missing Teacher',
            'email' => 'missing-class@example.test',
            'class' => 'Grade 9',
        ]);
        $this->assertFalse($missing['success']);
        $this->assertStringContainsString('Grade 9', $missing['message']);
        $this->assertDatabaseMissing('teacher_invites', ['email' => 'missing-class@example.test']);

        $partial = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Partial Teacher',
            'email' => 'partial-class@example.test',
            'class' => '7',
        ]);
        $this->assertFalse($partial['success']);
        $this->assertDatabaseMissing('teacher_invites', ['email' => 'partial-class@example.test']);

        $cross = ToshiActionService::addTeacher($this->admin, [
            'name' => 'Cross Teacher',
            'email' => 'cross-class@example.test',
            'class' => 'S 4',
        ]);
        $this->assertFalse($cross['success']);
        $encoded = (string) json_encode($cross);
        $this->assertStringNotContainsString('Other Class School', $encoded);
        $this->assertStringNotContainsString((string) $otherLink->id, $encoded);
        $this->assertSame(0, TeacherInvite::query()->where('standard_link_id', $otherLink->id)->count());
        $this->assertDatabaseMissing('teacher_invites', ['email' => 'cross-class@example.test']);
    }
}
