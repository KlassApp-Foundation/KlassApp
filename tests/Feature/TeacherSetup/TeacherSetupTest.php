<?php

namespace Tests\Feature\TeacherSetup;

use App\Livewire\TeacherSetup\TeacherClassSetup;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\StaffLinkRequest;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\TeacherSetup\TeacherSetupLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Personal teacher links, the staff link, and "Set up my class".
 */
class TeacherSetupTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $admin;

    private StandardLink $link;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Setup School',
            'slug' => 'setup-school',
            'email' => 'setup@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Setup Admin',
            'email' => 'setup-admin@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Class Teacher',
            'email' => 'class-teacher@schools.test',
            'mobile_no' => '+256772000111',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2027',
            'description' => 'setup',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P.1 A', 'status' => 1]);
        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
            'class_teacher_id' => $this->teacher->id,
        ]);
        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'status' => 1,
        ]);
    }

    public function test_a_personal_link_sets_the_password_and_cannot_be_reused(): void
    {
        $token = app(TeacherSetupLinkService::class)->issueFor($this->teacher, $this->admin);

        $this->get('/teacher/setup/'.$token)->assertOk()->assertSee('Set your password');

        $this->post('/teacher/setup/'.$token, [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->post('/teacher/setup/'.$token, [
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
        ])->assertRedirect('/teacher/class-setup');

        $this->assertTrue(Hash::check('StrongPass1', $this->teacher->fresh()->password));
        $this->assertAuthenticatedAs($this->teacher->fresh());

        // Single use.
        $this->get('/teacher/setup/'.$token)->assertSee('doesn’t work');
    }

    public function test_the_staff_link_matches_a_teacher_by_email_and_queues_a_stranger(): void
    {
        Mail::fake();
        $service = app(TeacherSetupLinkService::class);
        $staff = $service->issueStaffLink($this->school, $this->admin);

        $this->post('/school/staff/'.$staff, ['email' => 'class-teacher@schools.test'])
            ->assertOk()
            ->assertSee('Check your email to finish setting up')
            ->assertDontSee('/teacher/setup/');

        $this->post('/school/staff/'.$staff, ['name' => 'Stranger', 'email' => 'stranger@example.com'])
            ->assertOk()
            ->assertSee('Waiting for your admin');

        $this->assertSame(1, StaffLinkRequest::where('school_id', $this->school->id)->where('status', 'pending')->count());
    }

    public function test_the_admin_sees_progress_creates_a_staff_link_and_approves_a_request(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/class-setup')
            ->assertOk()
            ->assertSee('P.1 A')
            ->assertSee('Class Teacher');

        $this->post('/admin/class-setup/staff-link')->assertRedirect();
        $this->assertNotNull(session('staff_link_url'));

        $request = StaffLinkRequest::create([
            'school_id' => $this->school->id,
            'name' => 'New Teacher',
            'email' => 'new-teacher@schools.test',
            'phone' => '+256772000222',
            'status' => 'pending',
        ]);

        $this->post('/admin/class-setup/requests/'.$request->id.'/approve')->assertRedirect();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertDatabaseHas('users', [
            'school_id' => $this->school->id,
            'email' => 'new-teacher@schools.test',
            'usergroup_id' => 5,
        ]);
        $this->assertNotNull(session('teacher_setup_url'));
    }

    public function test_the_class_page_confirms_subjects_and_subject_teachers(): void
    {
        $other = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Maths Teacher',
            'email' => 'maths-teacher@schools.test',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(TeacherClassSetup::class)
            ->assertSet('selectedLinkId', $this->link->id)
            ->set('subjectIds', [$this->subject->id])
            ->call('saveSubjects')
            ->set('subjectTeacher.'.$this->subject->id, (string) $other->id)
            ->call('saveSubjectTeachers');

        $this->assertDatabaseHas('class_teacher_links', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->link->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $this->assertDatabaseHas('class_teacher_links', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->link->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $other->id,
        ]);
        $this->assertSame(2, Teacherlink::where('standardLink_id', $this->link->id)->count());
    }
}
