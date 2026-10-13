<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Services\People\PeopleListQuery;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\TeacherInvite;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * One list pattern for students, teachers and parents.
 * Queries stay inside the signed-in school.
 */
class PeopleListTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $adminA;

    private AcademicYear $yearA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = $this->school('People List A');
        $this->schoolB = $this->school('People List B');
        $this->yearA = $this->year($this->schoolA);
        $this->year($this->schoolB);
        $this->adminA = $this->person($this->schoolA, 3, 'Ada Admin', 'ada.admin@people-a.test');
    }

    public function test_three_lists_share_one_component_and_hide_delete(): void
    {
        $this->person($this->schoolA, 6, 'Amina Pupil', 'amina.pupil@people-a.test');
        $this->person($this->schoolA, 5, 'Tom Teacher', 'tom.teacher@people-a.test', [
            'email_verified' => 0,
        ]);
        $this->person($this->schoolA, 7, 'Grace Parent', 'grace.parent@people-a.test', [
            'mobile_no' => '+256700111222',
        ]);

        $students = $this->actingAs($this->adminA)->get('/admin/students');
        $teachers = $this->actingAs($this->adminA)->get('/admin/teachers');
        $parents = $this->actingAs($this->adminA)->get('/admin/parents');

        $students->assertOk();
        $teachers->assertOk();
        $parents->assertOk();

        $students->assertSee('data-people-list="students"', false);
        $teachers->assertSee('data-people-list="teachers"', false);
        $parents->assertSee('data-people-list="parents"', false);

        $students->assertSee('Search by name, KLS number', false);
        $teachers->assertSee('Search by name, email', false);
        $parents->assertSee('Search by name, phone', false);

        $students->assertSee('No class', false);
        $students->assertSee('No parent', false);
        $teachers->assertSee('Not yet invited', false);
        $teachers->assertSee('Invite pending', false);
        $teachers->assertSee('Class teachers', false);
        $parents->assertSee('On WhatsApp', false);
        $parents->assertSee('Not opted in', false);
        $parents->assertSee('Never logged in', false);

        $students->assertSee('KLS number', false);
        $teachers->assertSee('Class teacher of', false);
        $parents->assertSee('Last login', false);

        $students->assertSee('data-people-select', false);
        $students->assertSee('aria-pressed="false"', false);
        $students->assertSee('data-people-check-col', false);
        $students->assertSee('>Export<', false);
        $students->assertDontSee('>Message parents<', false);
        $teachers->assertDontSee('>Send invites<', false);
        $parents->assertSee('Send WhatsApp opt-in', false);

        $students->assertSee('Loading students', false);
        $teachers->assertSee('Loading teachers', false);
        $parents->assertSee('Loading parents', false);

        $students->assertSee('data-people-bulk', false);
        $students->assertDontSee('>Delete<', false);
        $teachers->assertDontSee('>Delete<', false);
        $parents->assertDontSee('>Delete<', false);

        $students->assertDontSee('name="alphabet"', false);
        $students->assertDontSee('>EAST<', false);
        $students->assertDontSee('Starts with', false);
        $teachers->assertDontSee('Starts with', false);
    }

    public function test_student_row_shows_kls_class_and_parent_and_links_to_the_profile(): void
    {
        $stream = $this->stream($this->schoolA, $this->yearA, 'Primary 5', 'Blue');
        $student = $this->person($this->schoolA, 6, 'Amina Pupil', 'amina.row@people-a.test');
        StudentAcademic::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'user_id' => $student->id,
            'standardLink_id' => $stream->id,
            'klassapp_student_id' => 'KLS0070042',
        ]);
        $parent = $this->person($this->schoolA, 7, 'Grace Parent', 'grace.row@people-a.test');
        StudentParentLink::create([
            'school_id' => $this->schoolA->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        $html = $this->actingAs($this->adminA)->get('/admin/students')->assertOk()->getContent();

        $this->assertStringContainsString('KLS0070042', $html);
        $this->assertStringContainsString('Primary 5', $html);
        $this->assertStringContainsString('Grace Parent', $html);
        $this->assertStringContainsString('/admin/students/'.$student->id, $html);
        $this->assertStringContainsString('View profile', $html);
        $this->assertStringContainsString('Move to class', $html);
        $this->assertStringContainsString('Message parent', $html);
        $this->assertStringNotContainsString('>Delete<', $html);
    }

    public function test_teacher_row_shows_subjects_class_teacher_and_invite_state(): void
    {
        $stream = $this->stream($this->schoolA, $this->yearA, 'Primary 5', 'Blue');
        $teacher = $this->person($this->schoolA, 5, 'Tom Teacher', 'tom.row@people-a.test', [
            'email_verified' => 0,
        ]);
        $stream->update(['class_teacher_id' => $teacher->id]);
        $subject = Subject::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'standard_id' => $stream->standard_id,
            'section_id' => $stream->section_id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
        ]);
        Teacherlink::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'standardLink_id' => $stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        TeacherInvite::create([
            'school_id' => $this->schoolA->id,
            'email' => $teacher->email,
            'token_hash' => hash('sha256', 'pending-token'),
            'role' => 'teacher',
            'expires_at' => now()->addDay(),
        ]);

        $html = $this->actingAs($this->adminA)->get('/admin/teachers')->assertOk()->getContent();

        $this->assertStringContainsString('Mathematics', $html);
        $this->assertStringContainsString('Primary 5', $html);
        $this->assertStringContainsString('Invited', $html);
        $this->assertStringContainsString('/admin/teacher/show/'.$teacher->name, $html);
        $this->assertStringContainsString('Send invite', $html);
        $this->assertStringContainsString('Assign classes', $html);
    }

    public function test_parent_row_shows_children_phone_whatsapp_and_last_login(): void
    {
        $parent = $this->person($this->schoolA, 7, 'Grace Parent', 'grace.login@people-a.test', [
            'mobile_no' => '+256700333444',
        ]);
        $child = $this->person($this->schoolA, 6, 'Amina Pupil', 'amina.child@people-a.test');
        StudentParentLink::create([
            'school_id' => $this->schoolA->id,
            'parent_id' => $parent->id,
            'student_id' => $child->id,
            'status' => 1,
        ]);
        WhatsAppUser::create([
            'phone' => '+256700333444',
            'user_id' => $parent->id,
            'school_id' => $this->schoolA->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);
        ActivityLog::create([
            'log_name' => 'login',
            'description' => 'login',
            'causer_id' => $parent->id,
            'causer_type' => User::class,
            'school_id' => $this->schoolA->id,
        ]);

        $html = $this->actingAs($this->adminA)->get('/admin/parents')->assertOk()->getContent();

        $this->assertStringContainsString('Amina Pupil', $html);
        $this->assertStringContainsString('+256700333444', $html);
        $this->assertStringContainsString('Opted in', $html);
        $this->assertStringContainsString('/admin/parent/show/'.$parent->name, $html);
        $this->assertStringContainsString('Link a child', $html);
        $this->assertStringContainsString('data-last-login="seen"', $html);
    }

    public function test_filters_search_and_empty_states(): void
    {
        $active = $this->person($this->schoolA, 6, 'Active Alpha', 'active.alpha@people-a.test');
        $inactive = $this->person($this->schoolA, 6, 'Inactive Beta', 'inactive.beta@people-a.test', [
            'status' => 'inactive',
        ]);
        $this->person($this->schoolA, 6, 'Exited Gamma', 'exited.gamma@people-a.test', [
            'status' => 'exit',
        ]);

        $all = $this->actingAs($this->adminA)->get('/admin/students');
        $all->assertOk();
        $all->assertSee('Active Alpha', false);
        $all->assertSee('Inactive Beta', false);
        $all->assertDontSee('Exited Gamma', false);

        $onlyActive = $this->actingAs($this->adminA)->get('/admin/students?chip=active');
        $onlyActive->assertSee('Active Alpha', false);
        $onlyActive->assertDontSee('Inactive Beta', false);

        $onlyInactive = $this->actingAs($this->adminA)->get('/admin/students?status=inactive');
        $onlyInactive->assertSee('Inactive Beta', false);
        $onlyInactive->assertDontSee('Active Alpha', false);

        $search = $this->actingAs($this->adminA)->get('/admin/students?search=Alpha');
        $search->assertSee('Active Alpha', false);
        $search->assertDontSee('Inactive Beta', false);

        $miss = $this->actingAs($this->adminA)->get('/admin/students?search=Zed');
        $miss->assertSee('No students match', false);
        $miss->assertSee('Clear filters', false);
        $miss->assertDontSee($active->email, false);

        $emptySchool = $this->person($this->schoolB, 3, 'Bea Admin', 'bea.admin@people-b.test');
        $empty = $this->actingAs($emptySchool)->get('/admin/students');
        $empty->assertSee('No students yet', false);
        $empty->assertSee('Add student', false);
        $empty->assertSee('Import a list', false);
    }

    public function test_lists_are_scoped_to_the_signed_in_school(): void
    {
        $streamA = $this->stream($this->schoolA, $this->yearA, 'Primary 5', 'Blue');
        $yearB = AcademicYear::where('school_id', $this->schoolB->id)->first();
        $streamB = $this->stream($this->schoolB, $yearB, 'Primary 6', 'Red');

        $studentA = $this->person($this->schoolA, 6, 'Alpha Pupil', 'alpha.pupil@people-a.test');
        $studentB = $this->person($this->schoolB, 6, 'Beta Pupil', 'beta.pupil@people-b.test');
        StudentAcademic::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'user_id' => $studentA->id,
            'standardLink_id' => $streamA->id,
            'klassapp_student_id' => 'KLS1110001',
        ]);
        StudentAcademic::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'user_id' => $studentB->id,
            'standardLink_id' => $streamB->id,
            'klassapp_student_id' => 'KLS2220001',
        ]);

        $teacherA = $this->person($this->schoolA, 5, 'Alpha Teacher', 'alpha.teacher@people-a.test');
        $teacherB = $this->person($this->schoolB, 5, 'Beta Teacher', 'beta.teacher@people-b.test');
        $parentA = $this->person($this->schoolA, 7, 'Alpha Parent', 'alpha.parent@people-a.test');
        $parentB = $this->person($this->schoolB, 7, 'Beta Parent', 'beta.parent@people-b.test');

        $students = $this->actingAs($this->adminA)->get('/admin/students?standard='.$streamB->id);
        $students->assertOk();
        $students->assertSee('Alpha Pupil', false);
        $students->assertDontSee('Beta Pupil', false);
        $students->assertDontSee('KLS2220001', false);
        $students->assertDontSee('Primary 6', false);

        $teachers = $this->actingAs($this->adminA)->get('/admin/teachers');
        $teachers->assertSee('Alpha Teacher', false);
        $teachers->assertDontSee('Beta Teacher', false);

        $parents = $this->actingAs($this->adminA)->get('/admin/parents');
        $parents->assertSee('Alpha Parent', false);
        $parents->assertDontSee('Beta Parent', false);

        $this->assertNotSame($teacherA->school_id, $teacherB->school_id);
        $this->assertNotSame($parentA->school_id, $parentB->school_id);
    }

    public function test_no_class_chip_keeps_only_students_without_a_class(): void
    {
        $stream = $this->stream($this->schoolA, $this->yearA, 'Primary 1', 'A');
        $enrolled = $this->person($this->schoolA, 6, 'Enrolled Child', 'enrolled.child@people-a.test');
        $classless = $this->person($this->schoolA, 6, 'Classless Child', 'classless.child@people-a.test');
        StudentAcademic::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'user_id' => $enrolled->id,
            'standardLink_id' => $stream->id,
            'klassapp_student_id' => 'KLS0070001',
        ]);

        $response = $this->actingAs($this->adminA)->get('/admin/students?chip=noclass');

        $response->assertOk();
        $response->assertSee('Classless Child', false);
        $response->assertDontSee('Enrolled Child', false);
        $response->assertSee('No class', false);
    }

    public function test_selection_column_and_avatar_size_are_switches(): void
    {
        $this->person($this->schoolA, 6, 'Amina Pupil', 'amina.switch@people-a.test');
        $this->actingAs($this->adminA);

        $list = app(PeopleListQuery::class)->students($this->adminA, request());

        $on = Blade::render('<x-people-list :list="$list" />', ['list' => $list]);
        $this->assertStringContainsString('data-people-check', $on);
        $this->assertStringContainsString('data-people-all', $on);
        $this->assertStringContainsString('data-people-bulk', $on);
        $this->assertStringContainsString('data-people-select', $on);
        $this->assertStringContainsString('people-avatar-28', $on);
        $this->assertStringContainsString('people-avatar-32', $on);
        $this->assertStringContainsString('width="32"', $on);
        $this->assertStringContainsString('width="40"', $on);

        $off = Blade::render(
            '<x-people-list :list="$list" :show-selection="false" avatar-size="md" />',
            ['list' => $list]
        );
        $this->assertStringNotContainsString('data-people-check', $off);
        $this->assertStringNotContainsString('data-people-all', $off);
        $this->assertStringNotContainsString('data-people-bulk', $off);
        $this->assertStringContainsString('Amina Pupil', $off);
        $this->assertStringContainsString('width="64"', $off);
        $this->assertStringNotContainsString('people-avatar-28', $off);
        $this->assertStringNotContainsString('people-avatar-32', $off);
        $this->assertStringNotContainsString('width="32"', $off);
        $this->assertStringNotContainsString('width="40"', $off);
    }

    public function test_student_export_follows_the_list_filters_and_stays_in_school(): void
    {
        $streamA = $this->stream($this->schoolA, $this->yearA, 'Primary 5', 'Blue', 'primary');
        $otherClass = $this->stream($this->schoolA, $this->yearA, 'Primary 6', 'Red', 'upper');
        $yearB = AcademicYear::query()->where('school_id', $this->schoolB->id)->first();
        $streamB = $this->stream($this->schoolB, $yearB, 'Primary 5', 'Blue');

        $amina = $this->person($this->schoolA, 6, 'Amina Pupil', 'amina.export@people-a.test');
        $inactive = $this->person($this->schoolA, 6, 'Idle Pupil', 'idle.export@people-a.test', ['status' => 'inactive']);
        $otherClassStudent = $this->person($this->schoolA, 6, 'Other Class Pupil', 'other.class@people-a.test');
        $exited = $this->person($this->schoolA, 6, 'Exited Pupil', 'exited.export@people-a.test', ['status' => 'exit']);
        $foreign = $this->person($this->schoolB, 6, 'Foreign Pupil', 'foreign.export@people-b.test');

        foreach ([[$amina, $streamA, 'KLS0000001'], [$inactive, $streamA, 'KLS0000002'], [$otherClassStudent, $otherClass, 'KLS0000003'], [$foreign, $streamB, 'KLS0000004']] as [$student, $stream, $kls]) {
            StudentAcademic::create([
                'school_id' => $student->school_id,
                'academic_year_id' => $stream->academic_year_id,
                'user_id' => $student->id,
                'standardLink_id' => $stream->id,
                'klassapp_student_id' => $kls,
            ]);
        }

        $page = $this->actingAs($this->adminA)->get('/admin/students?search=Amina&chip=active&standard='.$streamA->id);
        $page->assertOk();
        $page->assertSee('/admin/student/export?list=1', false);
        $page->assertSee('search=Amina', false);
        $page->assertSee('chip=active', false);
        $page->assertSee('standard='.$streamA->id, false);
        $page->assertDontSee('/admin/import" class="people-bulk-btn">Export', false);

        $all = $this->actingAs($this->adminA)->get('/admin/student/export?list=1');
        $all->assertOk();
        $body = $all->getContent();
        $this->assertStringContainsString('Amina Pupil', $body);
        $this->assertStringContainsString('KLS0000001', $body);
        $this->assertStringContainsString('Blue', $body);
        $this->assertStringContainsString('Idle Pupil', $body);
        $this->assertStringNotContainsString('Foreign Pupil', $body);
        $this->assertStringNotContainsString('KLS0000004', $body);
        $this->assertStringNotContainsString('Exited Pupil', $body);

        $searched = $this->actingAs($this->adminA)->get('/admin/student/export?list=1&search=Amina');
        $searched->assertOk();
        $this->assertStringContainsString('Amina Pupil', $searched->getContent());
        $this->assertStringNotContainsString('Idle Pupil', $searched->getContent());
        $this->assertStringNotContainsString('Foreign Pupil', $searched->getContent());

        $class = $this->actingAs($this->adminA)->get('/admin/student/export?list=1&chip=class&standard='.$streamA->id);
        $class->assertOk();
        $this->assertStringContainsString('Amina Pupil', $class->getContent());
        $this->assertStringNotContainsString('Other Class Pupil', $class->getContent());
        $this->assertStringNotContainsString('Foreign Pupil', $class->getContent());

        $foreignStandard = $this->actingAs($this->adminA)->get('/admin/student/export?list=1&chip=class&standard='.$streamB->id);
        $foreignStandard->assertOk();
        $this->assertStringContainsString('Amina Pupil', $foreignStandard->getContent());
        $this->assertStringNotContainsString('Foreign Pupil', $foreignStandard->getContent());

        $onlyInactive = $this->actingAs($this->adminA)->get('/admin/student/export?list=1&chip=inactive');
        $onlyInactive->assertOk();
        $this->assertStringContainsString('Idle Pupil', $onlyInactive->getContent());
        $this->assertStringNotContainsString('Amina Pupil', $onlyInactive->getContent());
        $this->assertStringNotContainsString('Foreign Pupil', $onlyInactive->getContent());
    }

    public function test_select_mode_row_click_toggles_instead_of_navigating(): void
    {
        $js = file_get_contents(public_path('js/people-list.js'));
        $css = file_get_contents(public_path('css/people-list.css'));

        $this->assertStringContainsString('function toggleRow', $js);
        $this->assertStringContainsString("root.classList.toggle('is-selecting'", $js);
        $this->assertStringContainsString('.people-list.is-selecting .people-name::after', $css);
        $this->assertStringContainsString('pointer-events: none', $css);
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => str()->slug($name).'-'.str()->random(4),
            'email' => str()->slug($name).'@people.test',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
    }

    private function year(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'People list year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function person(School $school, int $group, string $name, string $email, array $overrides = []): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('secret'),
            'email_verified' => 1,
            'mobile_no' => $overrides['mobile_no'] ?? null,
        ]);
        $user->forceFill([
            'status' => $overrides['status'] ?? 'active',
            'email_verified' => $overrides['email_verified'] ?? 1,
        ])->save();

        $parts = explode(' ', $name, 2);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => $parts[0],
            'lastname' => $parts[1] ?? '',
        ]);

        return $user->fresh(['userprofile']);
    }

    private function stream(School $school, AcademicYear $year, string $sectionName, string $stream, string $standardName = 'primary'): StandardLink
    {
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => $standardName,
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => $sectionName,
            'status' => 1,
        ]);

        return StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => $stream,
            'status' => 1,
        ]);
    }
}
