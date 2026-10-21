<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherInvite;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingNameListExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class WizardTeacherUploadResultTest extends TestCase
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
            'name' => 'Upload Result School',
            'email' => 'upload@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'upload-result-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Upload Admin',
            'email' => 'admin@upload.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Upload',
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
            ->set('schoolName', 'Upload Result Academy')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-UPLOAD')
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

    public function test_valid_teacher_file_adds_teachers_and_lists_them(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $section = (string) Section::where('school_id', $this->school->id)->orderBy('id')->value('name');
        $subject = (string) Subject::where('school_id', $this->school->id)->orderBy('id')->value('name');
        $this->assertNotSame('', $section);
        $this->assertNotSame('', $subject);

        $component
            ->set('teacherName', 'Cara By Hand')
            ->set('teacherEmail', 'cara-hand@upload.sch.ug')
            ->call('addTeacherDraft')
            ->assertSee('Cara By Hand');

        $csv = "Name,Email,Subjects,Classes,Phone\n"
            ."Ned Teacher,ned-valid@upload.sch.ug,{$subject},Not A Real Class,+256700222003\n"
            ."Ada Teacher,ada-valid@upload.sch.ug,{$subject},{$section},+256700222001\n"
            ."Ben Teacher,ben-valid@upload.sch.ug,{$subject},{$section},+256700222002\n";
        $file = UploadedFile::fake()->createWithContent('teachers.csv', $csv);

        $component
            ->set('teacherUpload', $file)
            ->assertSet('teacherUploadReport', '3 teachers added, 0 skipped')
            ->assertSee('Ada Teacher')
            ->assertSee('Ben Teacher')
            ->assertSee('Ned Teacher')
            ->assertSee('Cara By Hand')
            ->assertSee("class 'Not A Real Class' was not found");

        $saved = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();
        $this->assertSame(
            ['Ned Teacher', 'Ada Teacher', 'Ben Teacher'],
            $saved->pluck('name')->all()
        );
        $this->assertSame(0, User::where('school_id', '!=', $this->school->id)->where('usergroup_id', 5)->count());

        $ada = $saved->firstWhere('email', 'ada-valid@upload.sch.ug');
        $this->assertNotNull($ada);
        $this->assertTrue(
            Teacherlink::where('school_id', $this->school->id)->where('teacher_id', $ada->id)->exists()
        );
        $this->assertFalse(
            Teacherlink::where('school_id', $this->school->id)
                ->where('teacher_id', $saved->firstWhere('email', 'ned-valid@upload.sch.ug')->id)
                ->exists()
        );

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        $this->assertSame(0, TeacherInvite::count());

        $serial = UploadedFile::fake()->createWithContent(
            'serial.csv',
            "S/N,Teacher's Name,Email,Phone\n1,Serial Teacher,serial@upload.sch.ug,+256700222004\n"
        );
        $component
            ->set('teacherUpload', $serial)
            ->assertSet('teacherUploadReport', '1 teacher added, 0 skipped')
            ->assertSee('Serial Teacher');
        $this->assertTrue(
            User::where('school_id', $this->school->id)->where('email', 'serial@upload.sch.ug')->where('status', 'active')->exists()
        );
    }

    public function test_mixed_teacher_file_reports_added_and_skipped_with_reasons(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $csv = implode("\n", [
            'Name,Email,Phone',
            'Good One,good-one@upload.sch.ug,+256700111001',
            ',missing-name@upload.sch.ug,+256700111002',
            'Bad Email,not-an-email,+256700111003',
            'Good Two,good-two@upload.sch.ug,+256700111004',
            'Copy,good-one@upload.sch.ug,+256700111005',
            'Taken,admin@upload.sch.ug,+256700111006',
            'Bad Phone,bad-phone@upload.sch.ug,12345',
            'Same Phone,same-phone@upload.sch.ug,+256700111001',
            '',
        ]);
        $file = UploadedFile::fake()->createWithContent('teachers-mixed.csv', $csv);

        $component
            ->set('teacherUpload', $file)
            ->assertSet('teacherUploadReport', '2 teachers added, 6 skipped')
            ->assertSee('Good One')
            ->assertSee('Good Two')
            ->assertSee('Row 3: missing name')
            ->assertSee('Row 4: invalid email')
            ->assertSee('Row 6: duplicate email')
            ->assertSee('Row 7: already exists')
            ->assertSee('Row 8: invalid phone')
            ->assertSee('Row 9: duplicate phone');

        $names = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('id')
            ->pluck('name')
            ->all();
        $this->assertSame(['Good One', 'Good Two'], $names);
        $this->assertNull(User::where('email', 'bad-phone@upload.sch.ug')->first());
        $this->assertNull(User::where('email', 'missing-name@upload.sch.ug')->first());

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_teacher_template_headings_round_trip(): void
    {
        $path = public_path('templates/teacher-upload-template.xlsx');
        $this->assertFileExists($path);

        $sheet = IOFactory::load($path)->getSheetByName('Teacher Upload');
        $this->assertNotNull($sheet);
        $headers = array_map(fn ($cell) => trim((string) $cell), $sheet->toArray(null, true, true, false)[0]);
        $this->assertSame(['Name', 'Email', 'Subjects', 'Classes', 'Phone'], array_slice($headers, 0, 5));

        $rows = app(OnboardingNameListExtractor::class)->extractNamesFromFile($path, 'xlsx');
        $john = collect($rows)->firstWhere('name', 'John Ssali');
        $this->assertNotNull($john);
        $this->assertSame('jssali@school.ug', $john['email']);
        $this->assertSame('+256701234567', $john['phone']);
        $this->assertSame('Mathematics', $john['subjects']);
        $this->assertSame('Primary Five', $john['classes']);
        $this->assertSame(2, (int) $john['row']);

        Mail::fake();
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $section = (string) Section::where('school_id', $this->school->id)->orderBy('id')->value('name');
        $subject = (string) Subject::where('school_id', $this->school->id)->orderBy('id')->value('name');
        $csv = "Name,Email,Subjects,Classes,Phone\nRound Trip,round-trip@upload.sch.ug,{$subject},{$section},+256700333010\n";
        $file = UploadedFile::fake()->createWithContent('teacher-upload-template.csv', $csv);

        $component
            ->set('teacherUpload', $file)
            ->assertSet('teacherUploadReport', '1 teacher added, 0 skipped')
            ->assertSee('Round Trip');

        $teacher = User::where('email', 'round-trip@upload.sch.ug')->first();
        $this->assertNotNull($teacher);
        $this->assertSame($this->school->id, (int) $teacher->school_id);
        $this->assertSame(5, (int) $teacher->usergroup_id);
        $this->assertSame('active', $teacher->status);
        $this->assertSame('+256700333010', $teacher->mobile_no);
        $this->assertTrue(
            Teacherlink::where('school_id', $this->school->id)->where('teacher_id', $teacher->id)->exists()
        );
        Mail::assertNothingSent();
    }
}
