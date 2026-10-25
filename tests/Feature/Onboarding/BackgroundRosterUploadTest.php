<?php

namespace Tests\Feature\Onboarding;

use App\Jobs\ProcessOnboardingUpload;
use App\Livewire\ManualOnboardingWizard;
use App\Models\OnboardingUpload;
use App\Models\School;
use App\Models\User;
use App\Services\Onboarding\OnboardingUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roster files are imported by the queue, not inside the page request. The
 * wizard shows progress, and the same file uploaded again does not create a
 * second set of people.
 */
class BackgroundRosterUploadTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->school = School::create([
            'name' => 'Background Upload School',
            'slug' => 'background-upload-school',
            'email' => 'bg@schools.test',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $this->school->forceFill(['is_test' => 1])->save();

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'BG Admin',
            'email' => 'bg-admin@schools.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $year = \App\Models\AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'bg',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = \App\Models\Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = \App\Models\Section::create(['school_id' => $this->school->id, 'name' => 'P.1', 'status' => 1]);
        \App\Models\StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->actingAs($this->admin);
    }

    private function studentsCsv(int $count, int $offset = 0): string
    {
        $lines = ['Name,Class,Stream,Parent Name,Parent Phone'];
        for ($i = 1; $i <= $count; $i++) {
            $n = $offset + $i;
            $lines[] = "Student {$n},P.1,,Parent {$n},+2567".str_pad((string) (10000000 + $n), 8, '0', STR_PAD_LEFT);
        }

        return implode("\n", $lines)."\n";
    }

    private function upload(string $csv, string $name = 'students.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $csv);
    }

    public function test_two_thousand_students_import_with_progress_and_the_page_does_not_wait(): void
    {
        $component = Livewire::test(ManualOnboardingWizard::class);

        $component->set('studentUpload', $this->upload($this->studentsCsv(2000), 'students-2000.csv'));

        $this->assertSame(2000, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $component->assertSet('studentUploadReport', '2000 students added, 0 skipped');

        $upload = OnboardingUpload::query()->latest('id')->firstOrFail();
        $this->assertSame(OnboardingUpload::STATUS_DONE, $upload->status);
        $this->assertSame(2000, $upload->total);
        $this->assertSame(2000, $upload->processed);
        $this->assertSame(2000, $upload->added);
        $this->assertSame(0, $upload->skipped);
    }

    public function test_the_same_file_three_times_adds_the_students_once(): void
    {
        $csv = $this->studentsCsv(2000);

        foreach ([1, 2, 3] as $attempt) {
            $component = Livewire::test(ManualOnboardingWizard::class);
            $component->set('studentUpload', $this->upload($csv, 'students-2000.csv'));
        }

        $this->assertSame(2000, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(1, OnboardingUpload::where('school_id', $this->school->id)->count());
    }

    public function test_a_second_file_with_overlapping_rows_does_not_duplicate_them(): void
    {
        $first = Livewire::test(ManualOnboardingWizard::class);
        $first->set('studentUpload', $this->upload($this->studentsCsv(3), 'first.csv'));

        $second = Livewire::test(ManualOnboardingWizard::class);
        $second->set('studentUpload', $this->upload($this->studentsCsv(5, 1), 'second.csv'));

        // Students 2 and 3 already exist; 4, 5 and 6 are new.
        $this->assertSame(6, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $second->assertSet('studentUploadReport', '3 students added, 2 skipped');
    }

    public function test_a_second_upload_while_the_first_is_still_queued_does_not_dispatch_again(): void
    {
        config(['onboarding.uploads.run_inline_in_tests' => false]);
        Queue::fake();

        $csv = $this->studentsCsv(5);
        $first = Livewire::test(ManualOnboardingWizard::class);
        $first->set('studentUpload', $this->upload($csv, 'same.csv'));
        $second = Livewire::test(ManualOnboardingWizard::class);
        $second->set('studentUpload', $this->upload($csv, 'same.csv'));

        Queue::assertCount(1);
        $this->assertSame(1, OnboardingUpload::where('school_id', $this->school->id)->count());
        $second->assertSet('activeUpload.status', OnboardingUpload::STATUS_QUEUED);
    }

    public function test_the_job_leaves_no_identity_on_the_guard(): void
    {
        $upload = OnboardingUpload::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'kind' => OnboardingUpload::KIND_STUDENTS,
            'original_name' => 'students.csv',
            'path' => 'onboarding-uploads/'.$this->school->id.'/guard.csv',
            'file_hash' => hash('sha256', 'guard'),
            'status' => OnboardingUpload::STATUS_QUEUED,
            'rows' => [
                ['name' => 'Guard Student', 'class' => 'P.1', 'stream' => '', 'row' => 2],
            ],
        ]);

        // A worker starts with no signed-in user, as a real queue process does.
        Auth::logout();
        $this->assertNull(Auth::user());

        (new ProcessOnboardingUpload((int) $upload->id))->handle(app(OnboardingUploadService::class));

        $this->assertSame(OnboardingUpload::STATUS_DONE, $upload->fresh()->status);
        $this->assertNull(Auth::guard()->user(), 'the job must not leave a user on the guard');
    }

    public function test_the_upload_is_queued_and_the_job_reports_its_result(): void
    {
        config(['onboarding.uploads.run_inline_in_tests' => false]);
        Queue::fake();

        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->set('studentUpload', $this->upload($this->studentsCsv(4), 'queued.csv'));

        Queue::assertPushed(ProcessOnboardingUpload::class);
        $component->assertSet('activeUpload.status', OnboardingUpload::STATUS_QUEUED);
        $this->assertSame(0, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());

        $upload = OnboardingUpload::query()->latest('id')->firstOrFail();
        (new ProcessOnboardingUpload((int) $upload->id))->handle(app(OnboardingUploadService::class));

        $this->assertSame(4, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(OnboardingUpload::STATUS_DONE, $upload->fresh()->status);
    }
}
