<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\Message;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppPendingNotification;
use App\Models\WhatsAppPendingParentLink;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Rows are copied from docs/demo-data/sunrise-primary-and-nursery-school/student-upload-template.csv.
 * Joan reuses Herbert Ssekitoleko's phone in local form: that file has one child per number,
 * and siblings have to share one parent. Rose's phone is left blank to cover a name with no phone.
 */
class StudentParentImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        Storage::fake('local');

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Sunrise Import School',
            'email' => 'sunrise-import@example.com',
            'phone' => '+256700000001',
            'slug' => 'sunrise-import',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2027',
            'description' => 'Current Academic Year',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'nursery',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Baby Class',
            'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Sunrise Admin',
            'email' => 'admin@sunrise-import.example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Sunrise',
            'lastname' => 'Admin',
            'status' => 'active',
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

    public function test_student_upload_stores_parent_name_and_phone_on_the_parent(): void
    {
        Queue::fake();
        Notification::fake();
        Http::fake();
        Mail::fake();

        $otherSchool = School::create([
            'name' => 'Other Import School',
            'email' => 'other-import@example.com',
            'phone' => '+256700000002',
            'slug' => 'other-import',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);
        $otherParent = User::create([
            'school_id' => $otherSchool->id,
            'usergroup_id' => 7,
            'name' => 'Other School Parent',
            'email' => 'other-parent@example.com',
            'password' => bcrypt('password'),
            'mobile_no' => '+256700000400',
            'email_verified' => 1,
        ]);

        $csv = implode("\n", [
            'Name,Class,Stream,Gender,Parent Name,Parent Phone,School Student ID,LIN,UNEB Reg No.,Date of Birth',
            'Richard Ssekitoleko,Baby Class,,male,Herbert Ssekitoleko,+256700000400,SUN-2027-001,,,2023-12-11',
            'Joan Ssekitoleko,Baby Class,,female,Herbert Ssekitoleko,0700000400,SUN-2027-901,,,2023-08-01',
            'Nathan Opio,Baby Class,,male,Agnes Opio,+256700000401,SUN-2027-002,,,2023-09-09',
            'Rose Kasozi,Baby Class,,female,Lawrence Kasozi,,SUN-2027-003,,,2023-06-14',
            '',
        ]);

        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $studentsIndex = null;
        foreach ($component->instance()->steps as $index => $step) {
            if (($step['key'] ?? '') === 'students') {
                $studentsIndex = $index;
                break;
            }
        }
        $this->assertNotNull($studentsIndex);

        $component
            ->call('goToStep', $studentsIndex)
            ->set('studentUpload', UploadedFile::fake()->createWithContent('student-upload-template.csv', $csv))
            ->assertCount('studentDrafts', 4)
            ->assertSet('studentDrafts.0.parent', 'Herbert Ssekitoleko')
            ->assertSet('studentDrafts.0.parent_phone', '+256700000400')
            ->assertSet('studentDrafts.1.parent_phone', '+256700000400')
            ->call('next')
            ->assertSet('errorMessage', '');

        $richard = $this->student('Richard Ssekitoleko');
        $joan = $this->student('Joan Ssekitoleko');
        $nathan = $this->student('Nathan Opio');
        $rose = $this->student('Rose Kasozi');

        $herbert = $this->linkedParent($richard);
        $this->assertSame('Herbert Ssekitoleko', $herbert->name);
        $this->assertSame('+256700000400', $herbert->mobile_no);
        $this->assertSame((int) $this->school->id, (int) $herbert->school_id);
        $this->assertSame(7, (int) $herbert->usergroup_id);
        $this->assertSame($herbert->id, $this->linkedParent($joan)->id);
        $this->assertNotSame($otherParent->id, $herbert->id);
        $this->assertSame(
            1,
            User::query()
                ->where('school_id', $this->school->id)
                ->where('usergroup_id', 7)
                ->where('status', 'active')
                ->where('mobile_no', '+256700000400')
                ->count()
        );

        $agnes = $this->linkedParent($nathan);
        $this->assertSame('Agnes Opio', $agnes->name);
        $this->assertSame('+256700000401', $agnes->mobile_no);
        $this->assertNotSame($herbert->id, $agnes->id);

        $lawrence = $this->linkedParent($rose);
        $this->assertSame('Lawrence Kasozi', $lawrence->name);
        $this->assertNull($lawrence->mobile_no);

        $this->assertSame(3, User::query()->where('school_id', $this->school->id)->where('usergroup_id', 7)->count());
        $this->assertFalse(
            StudentParentLink::query()->where('parent_id', $otherParent->id)->exists()
        );

        foreach ([$richard, $joan, $nathan, $rose] as $student) {
            $student->load('userprofile');
            $this->assertNull($student->mobile_no);
            $this->assertNull($student->userprofile->alternate_no);
        }

        Queue::assertNothingPushed();
        Notification::assertNothingSent();
        Http::assertNothingSent();
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        $this->assertSame(0, WhatsAppUser::query()->count());
        $this->assertSame(0, WhatsAppPendingNotification::query()->count());
        $this->assertSame(0, WhatsAppPendingParentLink::query()->count());
        $this->assertSame(0, Message::query()->count());
    }

    private function student(string $name): User
    {
        $student = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->where('name', $name)
            ->first();
        $this->assertNotNull($student, $name);

        return $student;
    }

    private function linkedParent(User $student): User
    {
        $link = StudentParentLink::query()
            ->where('school_id', $this->school->id)
            ->where('student_id', $student->id)
            ->where('status', 1)
            ->first();
        $this->assertNotNull($link, $student->name);

        $parent = User::query()
            ->where('id', $link->parent_id)
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 7)
            ->where('status', 'active')
            ->first();
        $this->assertNotNull($parent, $student->name);

        return $parent;
    }
}
