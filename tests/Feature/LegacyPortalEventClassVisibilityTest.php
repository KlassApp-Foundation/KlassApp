<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Events;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LegacyPortalEventClassVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private int $otherSchoolId;

    private int $academicYearId;

    private int $classAId;

    private int $classBId;

    private User $admin;

    private User $teacher;

    private User $accountant;

    private User $receptionist;

    private User $studentA;

    private User $studentB;

    private Events $schoolWideEvent;

    private Events $classAEvent;

    private Events $classBEvent;

    private Events $otherSchoolEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Visibility School',
            'slug' => 'visibility-school',
            'email' => 'visibility@test.sch.ug',
            'phone' => '+256700000333',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherSchoolId = DB::table('schools')->insertGetId([
            'name' => 'Other Visibility School',
            'slug' => 'other-visibility-school',
            'email' => 'other-visibility@test.sch.ug',
            'phone' => '+256700000334',
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

        DB::table('academic_years')->insert([
            'school_id' => $this->otherSchoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $standardA = Standard::create([
            'school_id' => $this->schoolId,
            'name' => 'P1',
            'order' => 1,
            'status' => 1,
        ]);
        $standardB = Standard::create([
            'school_id' => $this->schoolId,
            'name' => 'P2',
            'order' => 2,
            'status' => 1,
        ]);
        Standard::create([
            'school_id' => $this->otherSchoolId,
            'name' => 'P1',
            'order' => 1,
            'status' => 1,
        ]);

        $sectionA = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'A',
            'status' => 1,
        ]);
        $sectionB = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'B',
            'status' => 1,
        ]);

        $this->classAId = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standardA->id,
            'section_id' => $sectionA->id,
            'no_of_students' => 1,
            'status' => 1,
        ])->id;

        $this->classBId = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'no_of_students' => 1,
            'status' => 1,
        ])->id;

        $this->admin = $this->makeUser(3, 'admin.vis@test.sch.ug', 'Admin Vis');
        $this->teacher = $this->makeUser(5, 'teacher.vis@test.sch.ug', 'Teacher Vis');
        $this->accountant = $this->makeUser(11, 'accountant.vis@test.sch.ug', 'Accountant Vis');
        $this->receptionist = $this->makeUser(10, 'receptionist.vis@test.sch.ug', 'Receptionist Vis');
        $this->studentA = $this->makeUser(6, 'student.a.vis@test.sch.ug', 'Student A');
        $this->studentB = $this->makeUser(6, 'student.b.vis@test.sch.ug', 'Student B');

        StudentAcademic::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'user_id' => $this->studentA->id,
            'standardLink_id' => $this->classAId,
            'academic_status' => 'pass',
        ]);
        StudentAcademic::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'user_id' => $this->studentB->id,
            'standardLink_id' => $this->classBId,
            'academic_status' => 'pass',
        ]);

        $this->schoolWideEvent = $this->makeEvent(
            schoolId: $this->schoolId,
            academicYearId: $this->academicYearId,
            title: 'School Wide Holiday',
            selectType: 'school',
            standardId: null,
        );
        $this->classAEvent = $this->makeEvent(
            schoolId: $this->schoolId,
            academicYearId: $this->academicYearId,
            title: 'Class A Event',
            selectType: 'class',
            standardId: $this->classAId,
        );
        $this->classBEvent = $this->makeEvent(
            schoolId: $this->schoolId,
            academicYearId: $this->academicYearId,
            title: 'Class B Event',
            selectType: 'class',
            standardId: $this->classBId,
        );
        $this->otherSchoolEvent = $this->makeEvent(
            schoolId: $this->otherSchoolId,
            academicYearId: DB::table('academic_years')->where('school_id', $this->otherSchoolId)->value('id'),
            title: 'Other School Event',
            selectType: 'school',
            standardId: null,
        );
    }

    public function test_five_roles_can_read_school_wide_event_via_event_gate(): void
    {
        foreach ([$this->admin, $this->teacher, $this->accountant, $this->receptionist, $this->studentA] as $user) {
            $this->actingAs($user);
            $this->assertTrue(Gate::allows('event', $this->schoolWideEvent), $user->email);
            $this->assertTrue(Gate::denies('event', $this->otherSchoolEvent), $user->email);
        }
    }

    public function test_staff_roles_can_read_class_scoped_events_via_details(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/events/details/'.$this->classBEvent->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Class B Event']);

        $this->actingAs($this->teacher)
            ->get('/teacher/events/details/'.$this->classBEvent->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Class B Event']);

        $this->actingAs($this->accountant)
            ->get('/accountant/events/details/'.$this->classBEvent->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Class B Event']);

        $this->actingAs($this->receptionist)
            ->get('/receptionist/events/details/'.$this->classBEvent->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Class B Event']);
    }

    public function test_student_can_read_own_class_and_school_wide_events(): void
    {
        $this->actingAs($this->studentA);

        $this->get('/student/events/details/'.$this->classAEvent->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Class A Event']);

        $this->get('/student/events/details/'.$this->schoolWideEvent->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'School Wide Holiday']);
    }

    public function test_student_cannot_read_other_class_scoped_event(): void
    {
        $this->actingAs($this->studentA);

        $this->get('/student/events/details/'.$this->classBEvent->id)
            ->assertForbidden();
    }

    public function test_student_cannot_read_other_school_event(): void
    {
        $this->actingAs($this->studentA);

        $this->get('/student/events/details/'.$this->otherSchoolEvent->id)
            ->assertForbidden();
    }

    public function test_event_gate_definition_remains_school_id_only(): void
    {
        $this->actingAs($this->studentA);
        $this->assertTrue(Gate::allows('event', $this->classBEvent));
        $this->assertTrue(Gate::denies('event', $this->otherSchoolEvent));
    }

    private function makeUser(int $usergroupId, string $email, string $name): User
    {
        $user = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => $usergroupId,
            'email' => $email,
            'name' => $name,
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->schoolId,
            'usergroup_id' => $usergroupId,
            'firstname' => $name,
            'lastname' => 'User',
        ]);

        return $user;
    }

    private function makeEvent(
        int $schoolId,
        int $academicYearId,
        string $title,
        string $selectType,
        ?int $standardId,
    ): Events {
        $event = new Events([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'select_type' => $selectType,
            'standard_id' => $standardId,
            'title' => $title,
            'description' => $title,
            'category' => 'holidays',
            'start_date' => '2026-08-20 08:00:00',
            'end_date' => '2026-08-20 17:00:00',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $event->batch = '';
        $event->color = $selectType === 'class' ? 'blue' : 'green';
        $event->save();

        return $event->fresh();
    }
}
