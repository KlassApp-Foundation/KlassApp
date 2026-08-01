<?php

namespace Tests\Feature;

use App\Events\Notification\SingleNotificationEvent;
use App\Events\SinglePushEvent;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Assignment;
use App\Models\Homework;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentAssignment;
use App\Models\StudentHomework;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LegacyPortalIdorOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $academicYearId;

    private int $standardLinkId;

    private User $teacher;

    private User $owner;

    private User $peer;

    private Assignment $assignment;

    private Homework $homework;

    private StudentAssignment $ownerAssignment;

    private StudentHomework $ownerHomework;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        Event::fake([SingleNotificationEvent::class, SinglePushEvent::class]);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'IDOR School',
            'slug' => 'idor-school',
            'email' => 'idor@test.sch.ug',
            'phone' => '+256700000099',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $standard = Standard::create([
            'school_id' => $this->schoolId,
            'name' => '1',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'A',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'no_of_students' => 2,
            'status' => 1,
        ]);
        $this->standardLinkId = $link->id;

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.idor@test.sch.ug',
            'name' => 'IDOR Teacher',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->teacher->id,
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'firstname' => 'Teach',
            'lastname' => 'Er',
        ]);

        $this->owner = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'owner.idor@test.sch.ug',
            'name' => 'Owner Student',
            'status' => 'active',
        ]);
        $this->peer = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'peer.idor@test.sch.ug',
            'name' => 'Peer Student',
            'status' => 'active',
        ]);

        foreach ([$this->owner, $this->peer] as $student) {
            Userprofile::factory()->create([
                'user_id' => $student->id,
                'school_id' => $this->schoolId,
                'usergroup_id' => 6,
                'firstname' => $student->name,
                'lastname' => 'Student',
            ]);
            StudentAcademic::create([
                'school_id' => $this->schoolId,
                'academic_year_id' => $this->academicYearId,
                'user_id' => $student->id,
                'standardLink_id' => $this->standardLinkId,
                'academic_status' => 'pass',
            ]);
        }

        $subject = Subject::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->assignment = Assignment::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'IDOR Assignment',
            'description' => 'Submit work',
            'attachment' => '',
            'marks' => 10,
            'assigned_date' => '2026-07-01',
            'submission_date' => '2026-08-15',
            'status' => 'ongoing',
        ]);

        $this->homework = Homework::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standardLink_id' => $this->standardLinkId,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'description' => 'IDOR homework',
            'attachment' => null,
            'date' => '2026-08-01',
            'created_by' => $this->teacher->id,
        ]);

        $this->ownerAssignment = StudentAssignment::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->owner->id,
            'assignment_file' => [1 => 'uploads/owner-assignment.pdf'],
            'submitted_on' => '2026-08-01',
            'status' => 'submitted',
        ]);

        $this->ownerHomework = StudentHomework::create([
            'homework_id' => $this->homework->id,
            'user_id' => $this->owner->id,
            'attachment' => [1 => 'uploads/owner-homework.pdf'],
            'submitted_on' => '2026-08-01',
            'status' => 'unchecked',
        ]);
    }

    public function test_gates_deny_peer_and_allow_owner(): void
    {
        $this->actingAs($this->peer);
        $this->assertTrue(Gate::denies('studentassignment', $this->ownerAssignment));
        $this->assertTrue(Gate::denies('studentHomework', $this->ownerHomework));

        $this->actingAs($this->owner);
        $this->assertTrue(Gate::allows('studentassignment', $this->ownerAssignment));
        $this->assertTrue(Gate::allows('studentHomework', $this->ownerHomework));
    }

    public function test_portal_peer_blocked_on_assignment_show_and_destroy(): void
    {
        $this->actingAs($this->peer);

        $this->get('/student/assignment/show/edit/list/'.$this->ownerAssignment->id)
            ->assertForbidden();

        $this->get('/student/assignment/delete/'.$this->ownerAssignment->id)
            ->assertForbidden();

        $this->assertDatabaseHas('student_assignments', [
            'id' => $this->ownerAssignment->id,
            'deleted_at' => null,
        ]);
    }

    public function test_portal_owner_can_show_and_destroy_assignment(): void
    {
        $this->actingAs($this->owner);

        $this->get('/student/assignment/show/edit/list/'.$this->ownerAssignment->id)
            ->assertOk()
            ->assertJsonStructure(['assignment_file', 'obtained_marks', 'comments']);

        $this->get('/student/assignment/delete/'.$this->ownerAssignment->id)
            ->assertOk()
            ->assertJsonStructure(['success']);

        $this->assertSoftDeleted('student_assignments', [
            'id' => $this->ownerAssignment->id,
        ]);
    }

    public function test_portal_peer_blocked_on_homework_show_and_destroy(): void
    {
        $this->actingAs($this->peer);

        $this->get('/student/homework/show/'.$this->ownerHomework->id)
            ->assertForbidden();

        $this->get('/student/homework/delete/'.$this->ownerHomework->id)
            ->assertForbidden();

        $this->assertDatabaseHas('student_homework', [
            'id' => $this->ownerHomework->id,
            'deleted_at' => null,
        ]);
    }

    public function test_portal_owner_can_show_and_destroy_homework(): void
    {
        $this->actingAs($this->owner);

        $this->get('/student/homework/show/'.$this->ownerHomework->id)
            ->assertOk()
            ->assertJsonStructure(['description', 'attachment_file']);

        $this->get('/student/homework/delete/'.$this->ownerHomework->id)
            ->assertOk()
            ->assertJsonStructure(['success']);

        $this->assertSoftDeleted('student_homework', [
            'id' => $this->ownerHomework->id,
        ]);
    }

    public function test_api_peer_blocked_on_assignment_show_and_destroy_even_with_owner_student_id(): void
    {
        $this->actingAs($this->peer, 'sanctum');

        $this->getJson('/api/v2/assignment/show/'.$this->owner->id.'/'.$this->ownerAssignment->id)
            ->assertForbidden();

        $this->getJson('/api/v2/assignment/delete/'.$this->ownerAssignment->id.'/'.$this->owner->id)
            ->assertForbidden();

        $this->assertDatabaseHas('student_assignments', [
            'id' => $this->ownerAssignment->id,
            'deleted_at' => null,
        ]);
    }

    public function test_api_owner_can_show_and_destroy_assignment(): void
    {
        $this->actingAs($this->owner, 'sanctum');

        $this->getJson('/api/v2/assignment/show/'.$this->peer->id.'/'.$this->ownerAssignment->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v2/assignment/delete/'.$this->ownerAssignment->id.'/'.$this->peer->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('student_assignments', [
            'id' => $this->ownerAssignment->id,
        ]);
    }

    public function test_api_peer_cannot_read_owner_homework_submission_via_route_student_id(): void
    {
        $this->actingAs($this->peer, 'sanctum');

        $response = $this->getJson('/api/v2/homework/show/'.$this->owner->id.'/'.$this->homework->id);

        $response->assertOk();
        $this->assertNotSame(
            $this->ownerHomework->id,
            $response->json('data.studentHomeworkId'),
            'Peer must not receive owner submission via forged student_id'
        );
    }

    public function test_api_peer_blocked_on_homework_destroy_even_with_owner_student_id(): void
    {
        $this->actingAs($this->peer, 'sanctum');

        $this->getJson('/api/v2/homework/delete/'.$this->ownerHomework->id.'/'.$this->owner->id)
            ->assertForbidden();

        $this->assertDatabaseHas('student_homework', [
            'id' => $this->ownerHomework->id,
            'deleted_at' => null,
        ]);
    }

    public function test_api_owner_can_show_and_destroy_homework(): void
    {
        $this->actingAs($this->owner, 'sanctum');

        $this->getJson('/api/v2/homework/show/'.$this->peer->id.'/'.$this->homework->id)
            ->assertOk()
            ->assertJsonPath('data.studentHomeworkId', $this->ownerHomework->id);

        $this->getJson('/api/v2/homework/delete/'.$this->ownerHomework->id.'/'.$this->peer->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('student_homework', [
            'id' => $this->ownerHomework->id,
        ]);
    }
}
