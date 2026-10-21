<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ClassStructureService;
use App\Services\OnboardingNameListExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class WizardClassTeacherUploadTest extends TestCase
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
            'name' => 'Class Teacher Upload School',
            'email' => 'class-teachers@test.sch.ug',
            'phone' => '0700000091',
            'slug' => 'class-teacher-upload-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Class Teacher Admin',
            'email' => 'admin@class-teachers.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Class',
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
            ->set('schoolName', 'Class Teacher Upload Academy')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-CT-UPLOAD')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next');

        $this->assertSame(
            'teachers',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }

    public function test_template_download_returns_csv_with_example_rows(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.class-teachers.upload-template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $body = $response->getContent();
        $this->assertStringContainsString('class,stream,name,email,phone', $body);
        $this->assertStringContainsString('Primary One,A,Grace Namukasa,grace@school.ug,0772123456', $body);
        $this->assertStringContainsString('Primary One,,John Ssali,john@school.ug,0772234567', $body);
        $this->assertStringContainsString('P1,East,Amina Kato,amina@school.ug,0772345678', $body);

        $tmp = tempnam(sys_get_temp_dir(), 'ct-template');
        file_put_contents($tmp, $body);
        $rows = app(OnboardingNameListExtractor::class)->extractNamesFromFile($tmp, 'csv');
        @unlink($tmp);

        $grace = collect($rows)->firstWhere('name', 'Grace Namukasa');
        $this->assertNotNull($grace);
        $this->assertSame('Primary One', $grace['class']);
        $this->assertSame('A', $grace['stream']);
        $this->assertSame('grace@school.ug', $grace['email']);
        $this->assertSame('0772123456', $grace['phone']);
        $this->assertSame(2, (int) $grace['row']);

        $blade = (string) file_get_contents(resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php'));
        $teachers = substr($blade, (int) strpos($blade, "@elseif(\$stepKey === 'teachers')"));
        $this->assertStringContainsString("route('admin.class-teachers.upload-template')", $teachers);
        $this->assertStringContainsString('Upload class teachers', $teachers);
        $this->assertStringContainsString('data-testid="wizard-class-teacher-upload"', $teachers);
        $this->assertStringContainsString('data-testid="wizard-teacher-upload"', $teachers);
    }

    public function test_valid_rows_are_saved_and_assigned(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $year = AcademicYear::where('school_id', $this->school->id)->where('status', 1)->first();
        $primaryTwo = Section::where('school_id', $this->school->id)->where('name', 'Primary Two')->first();
        $this->assertNotNull($year);
        $this->assertNotNull($primaryTwo);
        app(ClassStructureService::class)->addStream($this->school, $year, $primaryTwo, 'East');

        $csv = implode("\n", [
            'class,stream,name,email,phone',
            'Primary Two,East,Grace Namukasa,grace-ct@upload.sch.ug,0772123456',
            'Primary One,,John Ssali,john-ct@upload.sch.ug,0772234567',
            '',
        ]);
        $file = UploadedFile::fake()->createWithContent('class-teachers.csv', $csv);

        $component
            ->set('classTeacherUpload', $file)
            ->assertSet('classTeacherUploadReport', '2 class teachers added, 0 skipped')
            ->assertSee('Grace Namukasa')
            ->assertSee('John Ssali')
            ->assertSee('Primary Two')
            ->assertSee('East')
            ->assertSee('+256772123456')
            ->assertSee('+256772234567');

        $grace = User::where('email', 'grace-ct@upload.sch.ug')->first();
        $john = User::where('email', 'john-ct@upload.sch.ug')->first();
        $this->assertNotNull($grace);
        $this->assertNotNull($john);
        $this->assertSame((int) $this->school->id, (int) $grace->school_id);
        $this->assertSame(5, (int) $grace->usergroup_id);
        $this->assertSame('active', $grace->status);
        $this->assertSame('+256772123456', $grace->mobile_no);
        $this->assertSame('+256772234567', $john->mobile_no);

        $stream = Section::where('school_id', $this->school->id)->where('name', 'Primary Two East')->first();
        $base = Section::where('school_id', $this->school->id)->where('name', 'Primary One')->first();
        $primaryTwoFresh = Section::where('school_id', $this->school->id)->where('name', 'Primary Two')->first();
        $this->assertNotNull($stream);
        $this->assertNotNull($base);
        $this->assertSame((int) $grace->id, (int) $stream->class_teacher_id);
        $this->assertSame((int) $john->id, (int) $base->class_teacher_id);
        $this->assertNull($primaryTwoFresh->class_teacher_id);

        $streamLink = StandardLink::where('school_id', $this->school->id)->where('section_id', $stream->id)->first();
        $baseLink = StandardLink::where('school_id', $this->school->id)->where('section_id', $base->id)->first();
        $this->assertSame((int) $grace->id, (int) $streamLink->class_teacher_id);
        $this->assertSame((int) $john->id, (int) $baseLink->class_teacher_id);
        $this->assertSame(0, Teacherlink::where('school_id', $this->school->id)->whereIn('teacher_id', [$grace->id, $john->id])->count());

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        $this->assertSame(0, TeacherInvite::count());
    }

    public function test_unknown_class_is_skipped_with_a_reason(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $csv = implode("\n", [
            'class,stream,name,email,phone',
            'Primary One,,Kept Teacher,kept-ct@upload.sch.ug,0772000101',
            'Not A Real Class,,Skipped Teacher,skipped-ct@upload.sch.ug,0772000102',
            'Primary One,Nope,Stream Skip,stream-skip@upload.sch.ug,0772000103',
            ',A,,missing-ct@upload.sch.ug,0772000104',
            'Primary One,,Bad Email,not-an-email,0772000105',
            '',
        ]);
        $file = UploadedFile::fake()->createWithContent('class-teachers-mixed.csv', $csv);

        $component
            ->set('classTeacherUpload', $file)
            ->assertSet('classTeacherUploadReport', '1 class teacher added, 4 skipped')
            ->assertSee('Kept Teacher')
            ->assertSee('Row 3: unknown class')
            ->assertSee('Row 4: unknown stream')
            ->assertSee('Row 5: missing name')
            ->assertSee('Row 6: bad email')
            ->assertDontSee('Skipped Teacher');

        $kept = User::where('email', 'kept-ct@upload.sch.ug')->first();
        $this->assertNotNull($kept);
        $primaryOne = Section::where('school_id', $this->school->id)->where('name', 'Primary One')->first();
        $link = StandardLink::where('school_id', $this->school->id)->where('section_id', $primaryOne->id)->first();
        $this->assertSame((int) $kept->id, (int) $primaryOne->class_teacher_id);
        $this->assertSame((int) $kept->id, (int) $link->class_teacher_id);
        $this->assertNull(User::where('email', 'skipped-ct@upload.sch.ug')->first());
        $this->assertNull(User::where('email', 'stream-skip@upload.sch.ug')->first());
        $this->assertNull(User::where('email', 'missing-ct@upload.sch.ug')->first());
        $this->assertNull(User::where('email', 'not-an-email')->first());

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        $this->assertSame(0, TeacherInvite::count());
    }

    public function test_p1_and_primary_1_both_resolve(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $year = AcademicYear::where('school_id', $this->school->id)->where('status', 1)->first();
        $primaryOne = Section::where('school_id', $this->school->id)->where('name', 'Primary One')->first();
        $this->assertNotNull($primaryOne);
        app(ClassStructureService::class)->addStream($this->school, $year, $primaryOne, 'East');
        $before = Section::where('school_id', $this->school->id)->count();

        $csv = implode("\n", [
            'class,stream,name,email,phone',
            'P1,,Amina Kato,p1-ct@upload.sch.ug,0772000201',
            'Primary 1,East,Grace Namukasa,primary1-ct@upload.sch.ug,0772000202',
            '',
        ]);
        $file = UploadedFile::fake()->createWithContent('class-teachers-aliases.csv', $csv);

        $component
            ->set('classTeacherUpload', $file)
            ->assertSet('classTeacherUploadReport', '2 class teachers added, 0 skipped')
            ->assertSee('Amina Kato')
            ->assertSee('Grace Namukasa')
            ->assertSee('East');

        $p1 = User::where('email', 'p1-ct@upload.sch.ug')->first();
        $primary = User::where('email', 'primary1-ct@upload.sch.ug')->first();
        $this->assertNotNull($p1);
        $this->assertNotNull($primary);

        $base = Section::where('school_id', $this->school->id)->where('name', 'Primary One')->first();
        $stream = Section::where('school_id', $this->school->id)->where('name', 'Primary One East')->first();
        $this->assertSame((int) $p1->id, (int) $base->class_teacher_id);
        $this->assertSame((int) $primary->id, (int) $stream->class_teacher_id);
        $this->assertSame(
            (int) $p1->id,
            (int) StandardLink::where('school_id', $this->school->id)->where('section_id', $base->id)->value('class_teacher_id')
        );
        $this->assertSame(
            (int) $primary->id,
            (int) StandardLink::where('school_id', $this->school->id)->where('section_id', $stream->id)->value('class_teacher_id')
        );
        $this->assertSame($before, Section::where('school_id', $this->school->id)->count());
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'P1')->first());
    }

    public function test_duplicate_teacher_email_reuses_the_teacher(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $csv = implode("\n", [
            'class,stream,name,email,phone',
            'Primary One,,Ada First,ada-ct@upload.sch.ug,0772000301',
            'Primary Two,,Ada Second,ada-ct@upload.sch.ug,0772000302',
            'Primary Three,,Phone Again,,0772000301',
            '',
        ]);
        $file = UploadedFile::fake()->createWithContent('class-teachers-duplicate.csv', $csv);

        $component
            ->set('classTeacherUpload', $file)
            ->assertSet('classTeacherUploadReport', '3 class teachers added, 0 skipped')
            ->assertSee('Ada First')
            ->assertSee('Primary One')
            ->assertSee('Primary Two')
            ->assertSee('Primary Three');

        $teachers = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->get();
        $this->assertCount(1, $teachers);
        $teacher = $teachers->first();
        $this->assertSame('ada-ct@upload.sch.ug', $teacher->email);
        $this->assertSame('Ada First', $teacher->name);
        $this->assertSame('+256772000301', $teacher->mobile_no);

        foreach (['Primary One', 'Primary Two', 'Primary Three'] as $className) {
            $section = Section::where('school_id', $this->school->id)->where('name', $className)->first();
            $this->assertNotNull($section, $className);
            $this->assertSame((int) $teacher->id, (int) $section->class_teacher_id);
            $this->assertSame(
                (int) $teacher->id,
                (int) StandardLink::where('school_id', $this->school->id)->where('section_id', $section->id)->value('class_teacher_id')
            );
        }

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        $this->assertSame(0, TeacherInvite::count());
    }
}
