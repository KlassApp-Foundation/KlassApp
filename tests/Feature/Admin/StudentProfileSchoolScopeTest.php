<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Support\StudentProfileTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentProfileSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $adminA;

    private User $studentA;

    private User $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = $this->school('Profile A');
        $this->schoolB = $this->school('Profile B');
        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
        ]);
        $builtA = $this->student($this->schoolA, 'Amina', 'Nakato', 'KLS0380007', 'P.4');
        $builtB = $this->student($this->schoolB, 'Other', 'School', 'KLS0390001', 'P.4');
        $this->studentA = $builtA['user'];
        $this->studentB = $builtB['user'];
    }

    public function test_deferred_tabs_are_registered_and_not_shown(): void
    {
        $this->assertSame(['health', 'documents', 'notes'], StudentProfileTabs::DEFERRED);
        $this->assertSame(
            ['overview', 'academics', 'attendance', 'fees', 'parents'],
            StudentProfileTabs::VISIBLE
        );

        $this->actingAs($this->adminA)
            ->get('/admin/students/'.$this->studentA->id)
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Academics')
            ->assertSee('Attendance')
            ->assertSee('Fees')
            ->assertSee('Parents and guardians')
            ->assertDontSee('data-tab="health"', false)
            ->assertDontSee('data-tab="documents"', false)
            ->assertDontSee('data-tab="notes"', false)
            ->assertDontSee('Health and support')
            ->assertSee('No attendance this term')
            ->assertSee('No exam this term')
            ->assertSee('No fees on record');
    }

    public function test_old_name_address_redirects_only_for_one_student_in_this_school(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.student.show'));

        $this->actingAs($this->adminA)
            ->get('/admin/student/show/'.$this->studentA->name)
            ->assertRedirect('/admin/students/'.$this->studentA->id);

        $this->actingAs($this->adminA)
            ->get('/admin/student/show/'.$this->studentB->name)
            ->assertNotFound();

        User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 6,
            'name' => $this->studentA->name,
        ]);

        $this->actingAs($this->adminA)
            ->get('/admin/student/show/'.$this->studentA->name)
            ->assertNotFound();
    }

    public function test_another_schools_student_is_not_visible_or_deactivated(): void
    {
        $this->actingAs($this->adminA)
            ->get('/admin/students/'.$this->studentB->id)
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->post('/admin/students/'.$this->studentB->id.'/deactivate')
            ->assertNotFound();

        $this->assertSame('active', $this->studentB->fresh()->status);
    }

    public function test_profile_uses_this_schools_records_and_deactivate_flags_inactive(): void
    {
        $yearId = AcademicYear::where('school_id', $this->schoolA->id)->value('id');
        $term = AcademicTerm::where('school_id', $this->schoolA->id)->first();
        $link = StandardLink::where('school_id', $this->schoolA->id)->first();
        $teacher = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 5,
        ]);
        $subject = Subject::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearId,
            'standard_id' => $link->standard_id,
            'section_id' => $link->section_id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
        ]);
        $examType = ExamType::create([
            'name' => 'End of term',
            'code' => 'EOT',
            'contributes_to_report_total' => true,
        ]);
        $exam = new Exam([
            'school_id' => $this->schoolA->id,
            'standard_id' => $link->standard_id,
            'section_id' => $link->section_id,
            'academic_year_id' => $yearId,
            'academic_term_id' => $term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 'done',
        ]);
        $exam->exam_type_id = $examType->id;
        $exam->save();
        Marks::create([
            'student_id' => $this->studentA->id,
            'teacher_id' => $teacher->id,
            'school_id' => $this->schoolA->id,
            'subject_id' => $subject->id,
            'exam_id' => $exam->id,
            'section_id' => $link->section_id,
            'marks' => 82,
            'grade' => 'D1',
        ]);
        SchoolGradingSystem::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => $link->standard_id,
            'grade' => 'D1',
            'points' => 1,
            'min_score' => 80,
            'max_score' => 100,
            'remark' => 'Distinction',
        ]);

        Attendance::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearId,
            'standardLink_id' => $link->id,
            'user_id' => $this->studentA->id,
            'date' => '2026-02-02 08:00:00',
            'session' => 'forenoon',
            'status' => 1,
            'recorded_by' => $this->adminA->id,
        ]);
        Attendance::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearId,
            'standardLink_id' => $link->id,
            'user_id' => $this->studentA->id,
            'date' => '2026-02-03 08:00:00',
            'session' => 'forenoon',
            'status' => 0,
            'recorded_by' => $this->adminA->id,
        ]);
        Attendance::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => AcademicYear::where('school_id', $this->schoolB->id)->value('id'),
            'user_id' => $this->studentB->id,
            'date' => '2026-02-02 08:00:00',
            'session' => 'forenoon',
            'status' => 0,
            'recorded_by' => $this->adminA->id,
        ]);

        FeesCategories::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => $link->standard_id,
            'name' => 'Tuition',
            'amount' => 100000,
        ]);
        FeePayment::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->studentA->id,
            'amount' => 40000,
            'paid_on' => '2026-02-10',
            'recorded_by' => $this->adminA->id,
        ]);
        FeePayment::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $this->studentB->id,
            'amount' => 999999,
            'paid_on' => '2026-02-10',
            'recorded_by' => $this->adminA->id,
        ]);

        $parent = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 7,
            'mobile_no' => '700111222',
        ]);
        Userprofile::create([
            'user_id' => $parent->id,
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 7,
            'firstname' => 'Grace',
            'lastname' => 'Nakato',
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $this->schoolA->id,
            'parent_id' => $parent->id,
            'student_id' => $this->studentA->id,
            'status' => 1,
        ]);
        WhatsAppUser::create([
            'phone' => '+256700111222',
            'user_id' => $parent->id,
            'school_id' => $this->schoolA->id,
            'opted_in' => true,
        ]);

        $this->actingAs($this->adminA)
            ->get('/admin/students/'.$this->studentA->id)
            ->assertOk()
            ->assertSee('Amina Nakato')
            ->assertSee('P.4')
            ->assertSee('KLS0380007')
            ->assertSee('50%')
            ->assertSee('1 absence')
            ->assertSee('82')
            ->assertSee('D1')
            ->assertSee('60,000')
            ->assertSee('Partly paid')
            ->assertDontSee('999,999')
            ->assertSee('Deactivate');

        $this->actingAs($this->adminA)
            ->get('/admin/students/'.$this->studentA->id.'?tab=academics')
            ->assertOk()
            ->assertSee('MATHEMATICS')
            ->assertSee('82');

        $this->actingAs($this->adminA)
            ->get('/admin/students/'.$this->studentA->id.'?tab=parents')
            ->assertOk()
            ->assertSee('Grace Nakato')
            ->assertSee('Opted in');

        $this->actingAs($this->adminA)
            ->get('/admin/students')
            ->assertOk()
            ->assertSee('/admin/students/'.$this->studentA->id, false)
            ->assertDontSee('/admin/students/'.$this->studentB->id, false);

        $this->actingAs($this->adminA)
            ->post('/admin/students/'.$this->studentA->id.'/deactivate')
            ->assertRedirect('/admin/students/'.$this->studentA->id);

        $this->assertSame('inactive', $this->studentA->fresh()->status);
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'email' => uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
    }

    /**
     * @return array{user: User, link: StandardLink}
     */
    private function student(School $school, string $first, string $last, string $kls, string $class): array
    {
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term I',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => $class,
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => strtolower($first),
            'registration_number' => $kls,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'firstname' => $first,
            'lastname' => $last,
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $user->id,
            'standardLink_id' => $link->id,
            'klassapp_student_id' => $kls,
        ]);

        return ['user' => $user, 'link' => $link];
    }
}
