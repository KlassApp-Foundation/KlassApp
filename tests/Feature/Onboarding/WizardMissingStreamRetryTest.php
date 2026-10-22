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
use App\Models\StudentAcademic;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\OnboardingNameListExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sunrise class-teacher and student files name streams A and B on Primary One,
 * Three and Five. The category seeder creates the base classes only. Those six
 * streams are not unknown names — they have not been added on the Classes step.
 */
class WizardMissingStreamRetryTest extends TestCase
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
            'name' => 'Sunrise Stream School',
            'email' => 'sunrise-streams@test.sch.ug',
            'phone' => '0700000194',
            'slug' => 'sunrise-stream-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Sunrise Stream Admin',
            'email' => 'admin@sunrise-streams.sch.ug',
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
            ->set('schoolName', 'Sunrise Primary and Nursery School')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary_nursery')
            ->call('next')
            ->set('ministryCode', 'DEMO-0001')
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

    private function goToStudents(object $component): void
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search('students', $keys, true);
        $this->assertNotFalse($index);
        $component->call('goToStep', $index);
        $this->assertSame('students', $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sunriseRows(string $file): array
    {
        $path = base_path('docs/demo-data/sunrise-primary-and-nursery-school/'.$file);

        return app(OnboardingNameListExtractor::class)->extractNamesFromFile($path, 'csv');
    }

    private function upload(string $filename, string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $contents);
    }

    public function test_sunrise_class_teacher_rows_name_the_missing_streams_and_retry_saves_all_thirteen(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $year = AcademicYear::where('school_id', $this->school->id)->where('status', 1)->first();
        $this->assertNotNull($year);
        $engine = app(OnboardingEngine::class);

        $spellings = [
            ['class' => 'Primary One', 'stream' => 'A', 'row' => 1],
            ['class' => 'P1', 'stream' => 'a', 'row' => 2],
            ['class' => 'Primary  One', 'stream' => 'P1 A', 'row' => 3],
            ['class' => 'p.1', 'stream' => 'Primary One A', 'row' => 4],
            ['class' => 'primary one', 'stream' => '  A  ', 'row' => 5],
        ];
        $collapsed = $engine->collectMissingStreams($this->school, $year, $spellings);
        $this->assertCount(1, $collapsed['pairs']);
        $this->assertSame('Primary One', $collapsed['pairs'][0]['class']);
        $this->assertSame('A', $collapsed['pairs'][0]['stream']);
        $this->assertCount(5, $collapsed['skips']);

        $refused = $engine->createMissingStreams($this->school, $year, [
            ['class' => 'No Such Class', 'stream' => 'A'],
        ]);
        $this->assertSame([], $refused['created']);
        $this->assertSame('unknown class', $refused['refused'][0]['reason']);
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'No Such Class')->first());
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'No Such Class A')->first());

        $sectionsBefore = Section::where('school_id', $this->school->id)->count();
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->first());

        $path = base_path('docs/demo-data/sunrise-primary-and-nursery-school/class-teacher-upload-template.csv');
        $file = $this->upload('class-teachers.csv', (string) file_get_contents($path));

        $component
            ->set('classTeacherUpload', $file)
            ->assertSet('classTeacherUploadReport', '7 class teachers added, 6 skipped')
            ->assertSee("Row 5: Primary One, stream A doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee("Row 6: Primary One, stream B doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee("Row 8: Primary Three, stream A doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee("Row 9: Primary Three, stream B doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee("Row 11: Primary Five, stream A doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee("Row 12: Primary Five, stream B doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee('Create missing streams and retry')
            ->assertSee('Primary One, stream A')
            ->assertSee('Primary Five, stream B');

        $pairs = $component->get('classTeacherMissingStreams');
        $this->assertCount(6, $pairs);
        $this->assertSame([
            ['class' => 'Primary One', 'stream' => 'A'],
            ['class' => 'Primary One', 'stream' => 'B'],
            ['class' => 'Primary Three', 'stream' => 'A'],
            ['class' => 'Primary Three', 'stream' => 'B'],
            ['class' => 'Primary Five', 'stream' => 'A'],
            ['class' => 'Primary Five', 'stream' => 'B'],
        ], $pairs);
        $this->assertSame($sectionsBefore, Section::where('school_id', $this->school->id)->count());
        $this->assertNull(User::where('email', 'arthur.namukasa@example.com')->first());

        $component->call('createMissingStreamsAndRetry', 'class_teachers')
            ->assertSet('classTeacherUploadReport', '13 class teachers added, 0 skipped')
            ->assertSet('classTeacherMissingStreams', [])
            ->assertDontSee('Create missing streams and retry');

        $this->assertSame($sectionsBefore + 6, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(13, User::where('school_id', $this->school->id)->where('usergroup_id', 5)->count());

        $expected = [
            'Primary One A' => 'arthur.namukasa@example.com',
            'Primary One B' => 'martin.kabugo@example.com',
            'Primary Three A' => 'john.ojok@example.com',
            'Primary Three B' => 'ronald.ahimbisibwe@example.com',
            'Primary Five A' => 'wilson.kasozi@example.com',
            'Primary Five B' => 'vincent.mukasa@example.com',
            'Baby Class' => 'hellen.namusoke@example.com',
            'Primary Two' => 'ibrahim.kyomuhangi@example.com',
            'Primary Seven' => 'oscar.kyomuhangi@example.com',
        ];
        foreach ($expected as $sectionName => $email) {
            $section = Section::where('school_id', $this->school->id)->where('name', $sectionName)->first();
            $teacher = User::where('email', $email)->first();
            $this->assertNotNull($section, $sectionName);
            $this->assertNotNull($teacher, $email);
            $this->assertSame(1, Section::where('school_id', $this->school->id)->where('name', $sectionName)->count());
            $this->assertSame((int) $teacher->id, (int) $section->class_teacher_id);
            $link = StandardLink::where('school_id', $this->school->id)->where('section_id', $section->id)->first();
            $this->assertSame((int) $teacher->id, (int) $link->class_teacher_id);
        }

        $primaryOne = Section::where('school_id', $this->school->id)->where('name', 'Primary One')->first();
        $this->assertNotNull($primaryOne);
        $this->assertNull($primaryOne->class_teacher_id);

        $resolvedId = null;
        foreach ($spellings as $spelling) {
            $classified = $engine->classifyClassAndStream($this->school, $year, $spelling['class'], $spelling['stream']);
            $this->assertSame('resolved', $classified['status'], $spelling['class'].' / '.$spelling['stream']);
            $this->assertNotNull($classified['link']);
            $resolvedId ??= (int) $classified['link']->id;
            $this->assertSame($resolvedId, (int) $classified['link']->id);
        }
        $this->assertSame($sectionsBefore + 6, Section::where('school_id', $this->school->id)->count());

        $component->call('createMissingStreamsAndRetry', 'class_teachers')
            ->assertSet('classTeacherUploadReport', '13 class teachers added, 0 skipped');

        $this->assertSame($sectionsBefore + 6, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(13, User::where('school_id', $this->school->id)->where('usergroup_id', 5)->count());
        $this->assertSame(1, Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->count());
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'A')->first());
    }

    public function test_sunrise_student_rows_use_the_same_create_and_retry(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);
        $this->goToStudents($component);

        $sectionsBefore = Section::where('school_id', $this->school->id)->count();
        $path = base_path('docs/demo-data/sunrise-primary-and-nursery-school/student-upload-template.csv');
        $rows = $this->sunriseRows('student-upload-template.csv');
        $this->assertCount(120, $rows);
        $streamed = array_values(array_filter($rows, fn (array $row): bool => trim((string) $row['stream']) !== ''));
        $this->assertCount(50, $streamed);

        $file = $this->upload('students.csv', (string) file_get_contents($path));
        $component
            ->set('studentUpload', $file)
            ->assertSet('studentUploadReport', '0 students added, 50 skipped')
            ->assertCount('studentDrafts', 120)
            ->assertSee("Row {$streamed[0]['row']}: Primary One, stream A doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee('Create missing streams and retry');

        $this->assertSame([
            ['class' => 'Primary One', 'stream' => 'A'],
            ['class' => 'Primary One', 'stream' => 'B'],
            ['class' => 'Primary Three', 'stream' => 'A'],
            ['class' => 'Primary Three', 'stream' => 'B'],
            ['class' => 'Primary Five', 'stream' => 'A'],
            ['class' => 'Primary Five', 'stream' => 'B'],
        ], $component->get('studentMissingStreams'));
        $this->assertSame(0, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertSame($sectionsBefore, Section::where('school_id', $this->school->id)->count());

        $component->call('createMissingStreamsAndRetry', 'students')
            ->assertSet('studentUploadReport', '120 students added, 0 skipped')
            ->assertSet('studentMissingStreams', [])
            ->assertSet('studentDrafts', []);

        $this->assertSame(120, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertSame($sectionsBefore + 6, Section::where('school_id', $this->school->id)->count());

        $robert = User::where('school_id', $this->school->id)->where('name', 'Robert Ayebare')->first();
        $this->assertNotNull($robert);
        $linkId = StudentAcademic::where('user_id', $robert->id)->value('standardLink_id');
        $placed = StandardLink::with('section')->find($linkId);
        $this->assertSame('Primary One A', $placed?->section?->name);

        $component->call('createMissingStreamsAndRetry', 'students');

        $this->assertSame(120, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertSame($sectionsBefore + 6, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(1, Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->count());
        $this->assertSame(1, Section::where('school_id', $this->school->id)->where('name', 'Primary Five B')->count());
    }

    public function test_teacher_upload_that_names_a_stream_uses_the_same_retry(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $sectionsBefore = Section::where('school_id', $this->school->id)->count();
        $csv = implode("\n", [
            'Name,Email,Subjects,Classes,Phone',
            'Hellen Namusoke,hellen.stream@example.com,Literacy,Baby Class,+256700000310',
            'Arthur Namukasa,arthur.stream@example.com,English Language,Primary One A,+256700000313',
            'Ghost Teacher,ghost.stream@example.com,English Language,No Such Class A,+256700000399',
            '',
        ]);

        $component
            ->set('teacherUpload', $this->upload('teachers.csv', $csv))
            ->assertSet('teacherUploadReport', '2 teachers added, 1 skipped')
            ->assertSee("Row 3: Primary One, stream A doesn't exist yet. Create it in Classes, or use Create missing streams and retry.")
            ->assertSee('Create missing streams and retry');

        $this->assertSame([
            ['class' => 'Primary One', 'stream' => 'A'],
        ], $component->get('teacherMissingStreams'));
        $this->assertNull(User::where('email', 'arthur.stream@example.com')->first());
        $this->assertNotNull(User::where('email', 'hellen.stream@example.com')->first());
        $this->assertNotNull(User::where('email', 'ghost.stream@example.com')->first());
        $this->assertSame($sectionsBefore, Section::where('school_id', $this->school->id)->count());

        $component->call('createMissingStreamsAndRetry', 'teachers')
            ->assertSet('teacherMissingStreams', []);

        $this->assertSame($sectionsBefore + 1, Section::where('school_id', $this->school->id)->count());
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'No Such Class')->first());
        $this->assertNull(Section::where('school_id', $this->school->id)->where('name', 'No Such Class A')->first());

        $arthur = User::where('email', 'arthur.stream@example.com')->first();
        $stream = Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->first();
        $this->assertNotNull($arthur);
        $this->assertNotNull($stream);
        $link = StandardLink::where('school_id', $this->school->id)->where('section_id', $stream->id)->first();
        $this->assertNotNull($link);
        $this->assertTrue(
            Teacherlink::where('school_id', $this->school->id)
                ->where('teacher_id', $arthur->id)
                ->where('standardLink_id', $link->id)
                ->exists()
        );
        $this->assertSame(3, User::where('school_id', $this->school->id)->where('usergroup_id', 5)->count());

        $component->call('createMissingStreamsAndRetry', 'teachers');

        $this->assertSame($sectionsBefore + 1, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(1, Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->count());
        $this->assertSame(3, User::where('school_id', $this->school->id)->where('usergroup_id', 5)->count());
    }
}
