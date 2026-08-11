<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManualWizardBulkTeachersStudentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        Storage::fake('local');

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
            'name' => "Bulk's School",
            'email' => 'bulk@test.sch.ug',
            'phone' => '0700000044',
            'slug' => 'bulk-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Bulk Admin',
            'email' => 'admin@bulk.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Bulk',
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
            ->set('schoolName', 'Bulk Upload Academy')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-BULK')
            ->call('next')
            ->call('next') // uneb
            ->call('next') // academic year seeds classes/subjects/grading
            ;
    }

    public function test_students_step_exists_after_teachers_in_checklist(): void
    {
        $keys = array_keys(OnboardingStepsService::ALL_STEPS);
        $ti = array_search('teachers', $keys, true);
        $si = array_search('students', $keys, true);
        $this->assertNotFalse($ti);
        $this->assertNotFalse($si);
        $this->assertSame($ti + 1, $si);
        $this->assertContains('teachers', OnboardingStepsService::OPTIONAL_STEPS);
        $this->assertContains('students', OnboardingStepsService::OPTIONAL_STEPS);
    }

    public function test_teacher_csv_upload_and_paste_build_running_list(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $csv = "Name,Email,Subjects,Classes,Phone\nAda Teacher,ada@bulk.sch.ug,Math,P1,+256700111222\n";
        $file = UploadedFile::fake()->createWithContent('teachers.csv', $csv);

        $component
            ->set('teacherUpload', $file)
            ->assertCount('teacherDrafts', 1)
            ->set('teacherPaste', "Ben Teacher\nCara Teacher")
            ->call('applyTeacherPaste')
            ->assertCount('teacherDrafts', 3)
            ->call('removeTeacherDraft', 1)
            ->assertCount('teacherDrafts', 2);
    }

    public function test_teacher_one_at_a_time_and_students_bulk_persist_with_ids(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $component
            ->set('teacherName', 'One By One')
            ->set('teacherEmail', 'obo@bulk.sch.ug')
            ->call('addTeacherDraft')
            ->set('teacherPaste', 'Paste Teacher')
            ->call('applyTeacherPaste')
            ->call('next'); // persist teachers → students

        $this->assertSame(2, Teacherlink::where('school_id', $this->school->id)->count());

        $csv = "Name,Class,Stream,Parent Name,Parent Phone\nAmina Student,P1,A,Parent A,+256700333444\nBrian Student,P1,B,Parent B,+256700555666\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $component
            ->set('studentUpload', $file)
            ->assertCount('studentDrafts', 2)
            ->set('studentName', 'Solo Student')
            ->set('studentClass', 'P1')
            ->call('addStudentDraft')
            ->assertCount('studentDrafts', 3)
            ->call('next');

        $students = User::where('school_id', $this->school->id)->where('usergroup_id', 6)->get();
        $this->assertCount(3, $students);
        foreach ($students as $student) {
            $this->assertNotNull($student->registration_number);
            $this->assertMatchesRegularExpression('/^KLS\d{7}$/', $student->registration_number);
            $academic = StudentAcademic::where('user_id', $student->id)->first();
            $this->assertNotNull($academic);
            $this->assertSame($student->registration_number, $academic->klassapp_student_id);
        }
    }

    public function test_skipping_teachers_and_students_still_reaches_plan(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $component
            ->call('skipOptionalStep') // teachers
            ->assertSee('Students')
            ->call('skipOptionalStep') // students
            ->call('next') // terms
            ->call('next') // fees
            ->set('whatsappPhone', '+256700777888')
            ->call('next');

        $this->assertSame('plan_selection', $component->instance()->steps[$component->instance()->stepIndex]['key'] ?? null);
        $this->assertSame(0, Teacherlink::where('school_id', $this->school->id)->count());
        $this->assertSame(0, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $blocking = OnboardingStepsService::incompleteSteps($this->school->fresh(), $this->admin->id);
        $blocking = array_values(array_filter($blocking, fn ($s) => ! in_array($s['key'], OnboardingStepsService::OPTIONAL_STEPS, true)));
        $this->assertSame(['plan_selection'], array_column($blocking, 'key'), json_encode(array_column($blocking, 'key')));
    }

    public function test_template_assets_exist(): void
    {
        $this->assertFileExists(public_path('templates/teacher-upload-template.xlsx'));
        $this->assertFileExists(public_path('templates/student-upload-template.xlsx'));
    }
}
