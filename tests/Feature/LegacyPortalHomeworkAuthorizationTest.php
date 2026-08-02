<?php

namespace Tests\Feature;

use App\Events\Notification\ClassNotificationEvent;
use App\Events\Notification\SingleNotificationEvent;
use App\Events\SinglePushEvent;
use App\Events\StandardPushEvent;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Homework;
use App\Models\HomeworkApproval;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentHomework;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LegacyPortalHomeworkAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolA;

    private int $schoolB;

    private int $academicYearA;

    private int $academicYearB;

    private int $standardLinkA;

    private int $standardLinkB;

    private int $subjectA;

    private int $subjectB;

    private User $siteAdmin;

    private User $adminA;

    private User $adminB;

    private User $teacherAssigned;

    private User $teacherUnassigned;

    private User $studentOwner;

    private Homework $homeworkA;

    private Homework $homeworkB;

    private StudentHomework $submissionA;

    private StudentHomework $submissionB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        require_once __DIR__.'/../Support/activity_stub.php';

        Event::fake([
            SingleNotificationEvent::class,
            SinglePushEvent::class,
            StandardPushEvent::class,
            ClassNotificationEvent::class,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = DB::table('schools')->insertGetId([
            'name' => 'HW School A',
            'slug' => 'hw-school-a',
            'email' => 'hw-a@test.sch.ug',
            'phone' => '+256700000311',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolB = DB::table('schools')->insertGetId([
            'name' => 'HW School B',
            'slug' => 'hw-school-b',
            'email' => 'hw-b@test.sch.ug',
            'phone' => '+256700000322',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearA = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolA,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearB = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolB,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $standardA = Standard::create(['school_id' => $this->schoolA, 'name' => 'P1', 'order' => 1, 'status' => 1]);
        $sectionA = Section::create(['school_id' => $this->schoolA, 'name' => 'A', 'status' => 1]);
        $linkA = StandardLink::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standard_id' => $standardA->id,
            'section_id' => $sectionA->id,
            'no_of_students' => 10,
            'status' => 1,
        ]);
        $this->standardLinkA = $linkA->id;

        $standardB = Standard::create(['school_id' => $this->schoolB, 'name' => 'P1', 'order' => 1, 'status' => 1]);
        $sectionB = Section::create(['school_id' => $this->schoolB, 'name' => 'A', 'status' => 1]);
        $linkB = StandardLink::create([
            'school_id' => $this->schoolB,
            'academic_year_id' => $this->academicYearB,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'no_of_students' => 10,
            'status' => 1,
        ]);
        $this->standardLinkB = $linkB->id;

        $subjectA = Subject::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standard_id' => $standardA->id,
            'section_id' => $sectionA->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);
        $this->subjectA = $subjectA->id;

        $subjectB = Subject::create([
            'school_id' => $this->schoolB,
            'academic_year_id' => $this->academicYearB,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);
        $this->subjectB = $subjectB->id;

        $this->siteAdmin = $this->makeUser(1, $this->schoolA, 'siteadmin.hw@test.sch.ug', 'Site Admin');
        $this->adminA = $this->makeUser(3, $this->schoolA, 'admin.a.hw@test.sch.ug', 'Admin A');
        $this->adminB = $this->makeUser(3, $this->schoolB, 'admin.b.hw@test.sch.ug', 'Admin B');
        $this->teacherAssigned = $this->makeUser(5, $this->schoolA, 'teacher.assigned.hw@test.sch.ug', 'Teacher Assigned');
        $this->teacherUnassigned = $this->makeUser(5, $this->schoolA, 'teacher.unassigned.hw@test.sch.ug', 'Teacher Unassigned');
        $this->studentOwner = $this->makeUser(6, $this->schoolA, 'student.owner.hw@test.sch.ug', 'Student Owner');

        Teacherlink::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standardLink_id' => $this->standardLinkA,
            'subject_id' => $this->subjectA,
            'teacher_id' => $this->teacherAssigned->id,
        ]);

        $this->homeworkA = Homework::create([
            'school_id' => $this->schoolA,
            'academic_year_id' => $this->academicYearA,
            'standardLink_id' => $this->standardLinkA,
            'subject_id' => $this->subjectA,
            'teacher_id' => $this->teacherAssigned->id,
            'description' => 'School A homework',
            'attachment' => null,
            'date' => '2026-08-01',
            'created_by' => $this->teacherAssigned->id,
        ]);

        $this->homeworkB = Homework::create([
            'school_id' => $this->schoolB,
            'academic_year_id' => $this->academicYearB,
            'standardLink_id' => $this->standardLinkB,
            'subject_id' => $this->subjectB,
            'teacher_id' => $this->adminB->id,
            'description' => 'School B homework',
            'attachment' => null,
            'date' => '2026-08-01',
            'created_by' => $this->adminB->id,
        ]);

        HomeworkApproval::create([
            'homework_id' => $this->homeworkA->id,
            'status' => 'pending',
        ]);

        HomeworkApproval::create([
            'homework_id' => $this->homeworkB->id,
            'status' => 'pending',
        ]);

        $this->submissionA = StudentHomework::create([
            'homework_id' => $this->homeworkA->id,
            'user_id' => $this->studentOwner->id,
            'attachment' => [1 => 'uploads/a.pdf'],
            'submitted_on' => '2026-08-01',
            'status' => 'unchecked',
        ]);

        $peerB = $this->makeUser(6, $this->schoolB, 'student.b.hw@test.sch.ug', 'Student B');
        $this->submissionB = StudentHomework::create([
            'homework_id' => $this->homeworkB->id,
            'user_id' => $peerB->id,
            'attachment' => [1 => 'uploads/b.pdf'],
            'submitted_on' => '2026-08-01',
            'status' => 'unchecked',
        ]);
    }

    public function test_homework_manage_gate_ug3_own_school_and_denies_other(): void
    {
        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::allows('homework-manage', $this->homeworkA));
        $this->assertTrue(Gate::denies('homework-manage', $this->homeworkB));

        $this->actingAs($this->adminB);
        $this->assertTrue(Gate::allows('homework-manage', $this->homeworkB));
        $this->assertTrue(Gate::denies('homework-manage', $this->homeworkA));
    }

    public function test_homework_manage_gate_allows_ug1_cross_school(): void
    {
        $this->actingAs($this->siteAdmin);
        $this->assertTrue(Gate::allows('homework-manage', $this->homeworkA));
        $this->assertTrue(Gate::allows('homework-manage', $this->homeworkB));
    }

    public function test_homework_manage_gate_denies_ug5(): void
    {
        $this->actingAs($this->teacherAssigned);
        $this->assertTrue(Gate::denies('homework-manage', $this->homeworkA));
    }

    public function test_ug3_cannot_approve_other_school_homework(): void
    {
        $this->actingAs($this->adminA);

        $this->post('/admin/homework/approve/'.$this->homeworkB->id, [
            'principal_comments' => 'Nope',
        ])->assertForbidden();

        $this->assertDatabaseHas('homework_approvals', [
            'homework_id' => $this->homeworkB->id,
            'status' => 'pending',
        ]);
    }

    public function test_ug3_can_approve_own_school_homework(): void
    {
        $this->actingAs($this->adminA);

        $this->post('/admin/homework/approve/'.$this->homeworkA->id, [
            'principal_comments' => 'Good work',
        ])->assertOk();

        $this->assertDatabaseHas('homework_approvals', [
            'homework_id' => $this->homeworkA->id,
            'status' => 'approved',
        ]);
    }

    public function test_ug1_can_approve_cross_school_homework(): void
    {
        $this->actingAs($this->siteAdmin);

        $this->post('/admin/homework/approve/'.$this->homeworkB->id, [
            'principal_comments' => 'Site ok',
        ])->assertOk();

        $this->assertDatabaseHas('homework_approvals', [
            'homework_id' => $this->homeworkB->id,
            'status' => 'approved',
        ]);
    }

    public function test_ug3_cannot_destroy_other_school_homework(): void
    {
        $this->actingAs($this->adminA);

        $this->get('/admin/homework/delete/'.$this->homeworkB->id)
            ->assertForbidden();

        $this->assertDatabaseHas('homeworks', [
            'id' => $this->homeworkB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_ug1_can_destroy_cross_school_homework(): void
    {
        $this->actingAs($this->siteAdmin);

        $this->get('/admin/homework/delete/'.$this->homeworkB->id)
            ->assertOk();

        $this->assertSoftDeleted('homeworks', [
            'id' => $this->homeworkB->id,
        ]);
    }

    public function test_student_homework_review_ug3_school_scoped(): void
    {
        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::allows('studentHomework-review', $this->submissionA));
        $this->assertTrue(Gate::denies('studentHomework-review', $this->submissionB));
        $this->assertTrue(Gate::allows('studentHomework-review', $this->homeworkA));
        $this->assertTrue(Gate::denies('studentHomework-review', $this->homeworkB));
    }

    public function test_ug5_review_allows_assigned_and_denies_unassigned(): void
    {
        $this->actingAs($this->teacherAssigned);
        $this->assertTrue(Gate::allows('studentHomework-review', $this->submissionA));
        $this->assertTrue(Gate::allows('studentHomework-review', $this->homeworkA));

        $this->actingAs($this->teacherUnassigned);
        $this->assertTrue(Gate::denies('studentHomework-review', $this->submissionA));
        $this->assertTrue(Gate::denies('studentHomework-review', $this->homeworkA));
    }

    public function test_ug5_review_denies_other_school_even_if_linked_locally(): void
    {
        $this->actingAs($this->teacherAssigned);
        $this->assertTrue(Gate::denies('studentHomework-review', $this->submissionB));
    }

    public function test_ug3_cannot_update_other_school_student_homework(): void
    {
        $this->actingAs($this->adminA);

        $this->post('/admin/studenthomework/edit/'.$this->submissionB->id, [
            'comments' => 'Cross school',
        ])->assertForbidden();

        $this->assertDatabaseHas('student_homework', [
            'id' => $this->submissionB->id,
            'status' => 'unchecked',
        ]);
    }

    public function test_ug5_assigned_can_update_student_homework(): void
    {
        $this->actingAs($this->teacherAssigned);

        $this->post('/teacher/studenthomework/edit/'.$this->submissionA->id, [
            'comments' => 'Checked ok',
            'id' => $this->submissionA->id,
        ])->assertOk();

        $this->assertDatabaseHas('student_homework', [
            'id' => $this->submissionA->id,
            'status' => 'checked',
        ]);
    }

    public function test_ug5_unassigned_cannot_update_student_homework(): void
    {
        $unchecked = StudentHomework::create([
            'homework_id' => $this->homeworkA->id,
            'user_id' => $this->studentOwner->id,
            'attachment' => [1 => 'uploads/c.pdf'],
            'submitted_on' => '2026-08-02',
            'status' => 'unchecked',
        ]);

        $this->actingAs($this->teacherUnassigned);

        $this->post('/teacher/studenthomework/edit/'.$unchecked->id, [
            'comments' => 'Should fail',
            'id' => $unchecked->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('student_homework', [
            'id' => $unchecked->id,
            'status' => 'unchecked',
        ]);
    }

    public function test_student_homework_owner_gate_unchanged_for_ug6(): void
    {
        $this->actingAs($this->studentOwner);
        $this->assertTrue(Gate::allows('studentHomework', $this->submissionA));
        $this->assertTrue(Gate::denies('studentHomework-review', $this->submissionA));

        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::denies('studentHomework', $this->submissionA));
    }

    public function test_legacy_homework_gate_still_used_for_teacher_school_scope(): void
    {
        $this->actingAs($this->teacherAssigned);
        $this->assertTrue(Gate::allows('homework', $this->homeworkA));
        $this->assertTrue(Gate::denies('homework', $this->homeworkB));
    }

    private function makeUser(int $usergroupId, int $schoolId, string $email, string $name): User
    {
        $user = User::factory()->create([
            'school_id' => $schoolId,
            'usergroup_id' => $usergroupId,
            'email' => $email,
            'name' => $name,
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $user->id,
            'school_id' => $schoolId,
            'usergroup_id' => $usergroupId,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name)[1] ?? 'User',
        ]);

        return $user;
    }
}
