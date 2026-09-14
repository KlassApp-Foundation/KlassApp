<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\OnboardingNameListExtractor;
use App\Services\StudentUploadTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Piece 3 gaps 4–7: student gender/IDs/template, teacherlinks assignment,
 * fees scoping, multi-term + current (no auto-advance after one term).
 */
class WizardOnboardingGaps47Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Gaps School',
            'email' => 'gaps@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'gaps-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Gaps Admin',
            'email' => 'admin@gaps.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Gaps',
            'lastname' => 'Admin',
        ]);

        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
    }

    private function advanceToTeachers(object $component): void
    {
        $component
            ->set('schoolName', 'Gaps Academy')
            ->call('next')
            ->set('studentSize', '100-300 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-GAPS')
            ->call('next')
            ->call('next') // uneb
            ->call('next') // academic year
            ->call('next') // structure
            ->call('next'); // subjects → teachers

        $this->assertSame(
            'teachers',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }

    private function goToStepKey(object $component, string $key): void
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search($key, $keys, true);
        $this->assertNotFalse($index, "step {$key} missing from wizard steps");
        $component->call('goToStep', $index);
        $component->call('goToStep', $index);
        $this->assertSame(
            $key,
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }

    public function test_student_upload_template_includes_gender_ids_and_dob(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $year = AcademicYear::where('school_id', $this->school->id)->first();
        $this->assertNotNull($year);

        $spreadsheet = app(StudentUploadTemplateService::class)->spreadsheet($this->school, $year);
        $headers = [];
        for ($col = 1; $col <= 9; $col++) {
            $headers[] = $spreadsheet->getActiveSheet()->getCell([$col, 1])->getValue();
        }

        $this->assertSame([
            'Name',
            'Class',
            'Stream',
            'Gender',
            'Parent Name',
            'Parent Phone',
            'School Student ID',
            'UNEB Reg No.',
            'Date of Birth',
        ], $headers);
    }

    public function test_name_list_extractor_parses_gender_and_ids(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'stu');
        file_put_contents($tmp, "Name,Class,Gender,School Student ID,UNEB Reg No.,Date of Birth\n"
            ."Amina,P.7,female,ADM-1,U1234/001,2012-01-15\n");

        $rows = app(OnboardingNameListExtractor::class)->extractNamesFromFile($tmp, 'csv');
        @unlink($tmp);

        $this->assertCount(1, $rows);
        $this->assertSame('female', $rows[0]['gender']);
        $this->assertSame('ADM-1', $rows[0]['school_student_id']);
        $this->assertSame('U1234/001', $rows[0]['board_registration_number']);
        $this->assertSame('2012-01-15', $rows[0]['date_of_birth']);
    }

    public function test_student_draft_persists_gender_and_school_id(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);
        $this->goToStepKey($component, 'students');

        $section = Section::where('school_id', $this->school->id)->orderBy('id')->first();
        $this->assertNotNull($section);

        $component
            ->set('studentName', 'Gender Student')
            ->set('studentClass', $section->name)
            ->set('studentGender', 'female')
            ->set('studentSchoolStudentId', 'ADM-GAPS-1')
            ->call('addStudentDraft')
            ->call('next');

        $student = User::where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->where('name', 'Gender Student')
            ->first();
        $this->assertNotNull($student);
        $this->assertSame('female', $student->userprofile->gender);

        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertSame('ADM-GAPS-1', $academic->school_student_id);
    }

    public function test_teacher_assignment_creates_teacherlinks_for_class_subject_pairs(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $section = Section::where('school_id', $this->school->id)->orderBy('id')->first();
        $subject = Subject::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->orderBy('id')
            ->first();
        $this->assertNotNull($section);
        $this->assertNotNull($subject);

        $component
            ->set('teacherName', 'Assigned Teacher')
            ->set('teacherEmail', 'assigned@gaps.sch.ug')
            ->set('teacherSelectedClasses', [$section->name])
            ->set('teacherSelectedSubjects', [$subject->name])
            ->call('addTeacherDraft')
            ->call('next');

        $teacher = User::where('school_id', $this->school->id)
            ->where('email', 'assigned@gaps.sch.ug')
            ->first();
        $this->assertNotNull($teacher);

        $links = Teacherlink::where('school_id', $this->school->id)
            ->where('teacher_id', $teacher->id)
            ->get();
        $this->assertCount(1, $links);
        $this->assertSame($subject->id, $links->first()->subject_id);
    }

    public function test_terms_step_prefills_three_and_requires_current_before_continue(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);
        $this->goToStepKey($component, 'terms');

        $this->assertCount(3, $component->get('termDrafts'));
        $this->assertSame('Term 1', $component->get('currentTermName'));

        // Clearing current blocks Continue — does not auto-advance after one term.
        $component->set('currentTermName', '');
        $component->call('next');
        $this->assertSame('terms', $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null);
        $this->assertNotSame('', $component->get('errorMessage'));

        $component
            ->call('markTermCurrent', 'Term 2')
            ->assertSet('currentTermName', 'Term 2')
            ->call('next');

        $this->assertEquals(3, AcademicTerm::where('school_id', $this->school->id)->count());
        $this->assertSame(
            'Term 2',
            AcademicTerm::where('school_id', $this->school->id)->where('status', 'current')->value('name')
        );
        $this->assertEquals(
            2,
            AcademicTerm::where('school_id', $this->school->id)->where('status', 'next')->count()
        );
    }

    public function test_fees_step_supports_class_scope_term_and_yearly(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);
        $this->goToStepKey($component, 'terms');
        $component->call('markTermCurrent', 'Term 1')->call('next');

        $this->assertSame('fees', $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null);

        $section = Section::where('school_id', $this->school->id)->orderBy('id')->first();
        $this->assertNotNull($section);

        $component
            ->set('feeName', 'Tuition')
            ->set('feeAmount', '250000')
            ->set('feeScope', 'whole_school')
            ->set('feeIsYearly', true)
            ->call('addFeeDraft')
            ->set('feeName', 'Exam Fee')
            ->set('feeAmount', '30000')
            ->set('feeScope', 'class')
            ->set('feeClass', $section->name)
            ->set('feeIsYearly', false)
            ->set('feeTerm', 'Term 1')
            ->call('addFeeDraft')
            ->call('next');

        $tuition = FeesCategories::where('school_id', $this->school->id)
            ->where('name', 'Tuition')
            ->whereNull('section_id')
            ->first();
        $this->assertNotNull($tuition);
        $this->assertNull($tuition->academic_term_id);

        $exam = FeesCategories::where('school_id', $this->school->id)
            ->where('name', 'Exam Fee')
            ->where('section_id', $section->id)
            ->first();
        $this->assertNotNull($exam);
        $termId = AcademicTerm::where('school_id', $this->school->id)->where('name', 'Term 1')->value('id');
        $this->assertSame($termId, $exam->academic_term_id);
    }

    public function test_engine_save_terms_honours_explicit_current_status(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        app(OnboardingEngine::class)->saveTerms($this->school, $year, [
            ['name' => 'Term 1', 'start' => '2026-02-03', 'end' => '2026-05-02', 'status' => 'next'],
            ['name' => 'Term 2', 'start' => '2026-05-26', 'end' => '2026-08-29', 'status' => 'current'],
            ['name' => 'Term 3', 'start' => '2026-09-22', 'end' => '2026-12-19', 'status' => 'next'],
        ]);

        $this->assertSame(
            'Term 2',
            AcademicTerm::where('school_id', $this->school->id)->where('status', 'current')->value('name')
        );
    }
}
