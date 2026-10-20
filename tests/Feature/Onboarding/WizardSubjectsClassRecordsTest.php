<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WizardSubjectsClassRecordsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Subjects Class Records School',
            'email' => 'subjects-classes@test.sch.ug',
            'phone' => '0700000098',
            'slug' => 'subjects-class-records-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Subjects Admin',
            'email' => 'admin@subjects-classes.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Subjects',
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

    public function test_primary_one_class_from_the_classes_step_is_selectable_on_subjects(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToSubjectsWithClass($component, 'Primary One');

        $this->assertSame('subjects', $this->stepKey($component));
        $this->assertSame('Primary One', $component->get('className'));

        $sectionId = $this->sectionIdNamed('Primary One');
        $this->assertSame($sectionId, (int) $component->get('subjectSectionId'));
        $component->assertSeeHtml('data-testid="wizard-subject-class-'.$sectionId.'"');
        $component->assertSee('Primary One');

        $component
            ->set('subjectName', 'Dance')
            ->call('next');

        $this->assertSame('', $component->get('errorMessage'));
        $this->assertNotSame('subjects', $this->stepKey($component));
        $this->assertTrue(
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $sectionId)
                ->where('name', 'Dance')
                ->whereNull('deleted_at')
                ->exists()
        );
        $this->assertFalse(
            Section::query()->where('school_id', $this->school->id)->where('name', 'P1')->exists()
        );
    }

    public function test_seeded_primary_one_is_used_when_the_classes_field_is_still_p1(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceThroughPrimaryCategory($component);

        $this->assertSame('subjects', $this->stepKey($component));
        $this->assertSame('P1', $component->get('className'));
        $this->assertSame('', $component->get('errorMessage'));

        $primaryOne = $this->sectionIdNamed('Primary One');
        $primarySeven = $this->sectionIdNamed('Primary Seven');
        $this->assertSame($primaryOne, (int) $component->get('subjectSectionId'));

        $component
            ->set('subjectName', 'Dance')
            ->call('addSubjects');

        $this->assertSame('', $component->get('errorMessage'));
        $this->assertStringNotContainsString('does not exist', (string) $component->get('subjectFlash'));
        $this->assertStringContainsString('Added to Primary One: Dance.', (string) $component->get('subjectFlash'));
        $this->assertContains('DANCE', $component->get('existingSubjectNames'));
        $component->assertSeeHtml('data-testid="wizard-subject-remove"');

        $this->assertTrue(
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $primaryOne)
                ->where('name', 'Dance')
                ->whereNull('deleted_at')
                ->exists()
        );
        $this->assertFalse(
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $primarySeven)
                ->where('name', 'Dance')
                ->whereNull('deleted_at')
                ->exists()
        );

        $component
            ->set('subjectSectionId', $primarySeven)
            ->set('subjectName', 'French')
            ->call('addSubjects');

        $this->assertSame('', $component->get('errorMessage'));
        $this->assertStringContainsString('Added to Primary Seven: French.', (string) $component->get('subjectFlash'));
        $this->assertTrue(
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $primarySeven)
                ->where('name', 'French')
                ->whereNull('deleted_at')
                ->exists()
        );
        $this->assertFalse(
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $primaryOne)
                ->where('name', 'French')
                ->whereNull('deleted_at')
                ->exists()
        );
    }

    public function test_class_stored_as_p1_still_receives_subjects(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToSubjectsWithClass($component, 'P1');

        $sectionId = $this->sectionIdNamed('P1');
        $this->assertSame($sectionId, (int) $component->get('subjectSectionId'));

        $component
            ->set('subjectName', 'Mathematics')
            ->call('addSubjects');

        $this->assertSame('', $component->get('errorMessage'));
        $this->assertStringContainsString('Added to P1: Mathematics.', (string) $component->get('subjectFlash'));
        $this->assertTrue(
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $sectionId)
                ->where('name', 'Mathematics')
                ->whereNull('deleted_at')
                ->exists()
        );
    }

    public function test_bulk_add_splits_on_commas_and_newlines_and_reports_duplicates(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToSubjectsWithClass($component, 'Primary One');

        $sectionId = $this->sectionIdNamed('Primary One');

        $component
            ->set('subjectName', "Art, Music\nScience, art,  \nMusic")
            ->call('addSubjects');

        $this->assertSame('', $component->get('errorMessage'));
        $flash = (string) $component->get('subjectFlash');
        $this->assertStringContainsString('Added to Primary One: Art, Music, Science.', $flash);
        $this->assertStringContainsString('Repeated in the list (kept once): art, Music.', $flash);

        $stored = DB::table('subjects')
            ->where('school_id', $this->school->id)
            ->where('section_id', $sectionId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $this->assertSame(['Art', 'Music', 'Science'], $stored);

        $names = $component->get('existingSubjectNames');
        $this->assertSame(['ART', 'MUSIC', 'SCIENCE'], $names);
        $component->assertSeeHtml('data-testid="wizard-subject-row"');

        $component
            ->set('subjectName', "Music, Drama\n")
            ->call('addSubjects');

        $again = (string) $component->get('subjectFlash');
        $this->assertStringContainsString('Added to Primary One: Drama.', $again);
        $this->assertStringContainsString('Already on Primary One: Music.', $again);
        $this->assertSame(
            1,
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $sectionId)
                ->where('name', 'Music')
                ->count()
        );
    }

    public function test_remove_soft_deletes_the_subject_on_that_class_and_add_restores_it(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToSubjectsWithClass($component, 'Primary One');

        $sectionId = $this->sectionIdNamed('Primary One');

        $component
            ->set('subjectName', 'Music')
            ->call('addSubjects');

        $index = array_search('MUSIC', $component->get('existingSubjectNames'), true);
        $this->assertNotFalse($index);

        $component->call('removeSubject', $index);

        $this->assertSame('', $component->get('errorMessage'));
        $this->assertStringContainsString('Removed MUSIC from Primary One.', (string) $component->get('subjectFlash'));
        $this->assertNotContains('MUSIC', $component->get('existingSubjectNames'));
        $component->assertDontSeeHtml('data-testid="wizard-subject-row"');

        $row = DB::table('subjects')
            ->where('school_id', $this->school->id)
            ->where('section_id', $sectionId)
            ->where('name', 'Music')
            ->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);

        $component
            ->set('subjectName', 'Music')
            ->call('addSubjects');

        $restored = DB::table('subjects')
            ->where('school_id', $this->school->id)
            ->where('section_id', $sectionId)
            ->where('name', 'Music')
            ->first();
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at);
        $this->assertSame(
            1,
            DB::table('subjects')
                ->where('school_id', $this->school->id)
                ->where('section_id', $sectionId)
                ->where('name', 'Music')
                ->count()
        );
        $this->assertContains('MUSIC', $component->get('existingSubjectNames'));
    }

    private function advanceToSubjectsWithClass(object $component, string $className): void
    {
        $component
            ->set('schoolName', 'Subjects Class Records School')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'other')
            ->call('next')
            ->set('ministryCode', 'EMIS-SUBJ')
            ->call('next')
            ->call('next')
            ->set('className', $className)
            ->call('next');
    }

    private function advanceThroughPrimaryCategory(object $component): void
    {
        $component
            ->set('schoolName', 'Subjects Class Records School')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-SUBJ')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next');
    }

    private function stepKey(object $component): ?string
    {
        return $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null;
    }

    private function sectionIdNamed(string $name): int
    {
        $id = Section::query()
            ->where('school_id', $this->school->id)
            ->where('name', $name)
            ->value('id');

        $this->assertNotNull($id, "Expected a class named {$name}.");

        return (int) $id;
    }
}
