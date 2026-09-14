<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Mail\TeacherInviteMail;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ClassStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class WizardStructureClassTeacherTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

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
            'name' => 'Structure Wizard School',
            'email' => 'structure-wizard@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'structure-wizard-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Structure Admin',
            'email' => 'admin@structure-wizard.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Structure',
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

    private function advanceToStructure(object $component): void
    {
        $component
            ->set('schoolName', 'Structure Wizard School')
            ->call('next')
            ->set('studentSize', 'Under 100 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-STRUCT')
            ->call('next')
            ->call('next') // uneb centre
            ->call('next'); // academic year → structure checkpoint
    }

    public function test_after_academic_year_lands_on_structure_checkpoint(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToStructure($component);

        $this->assertSame(
            'standards',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
        $component->assertSee('Structure & Class Teachers');
        $this->assertNotEmpty($component->get('structureClasses'));
        $component->assertSeeHtml('data-testid="wizard-structure-step"');
    }

    public function test_next_with_no_actions_advances_past_structure(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToStructure($component);

        $component->call('next');

        $key = $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null;
        // Structure → subjects checkpoint (seeded subjects still get a review land).
        $this->assertSame('subjects', $key);
        $this->assertSame('', $component->get('errorMessage'));
        $component->assertSeeHtml('data-testid="wizard-subjects-seeded"');
    }

    public function test_add_structure_stream_creates_name_encoded_section(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToStructure($component);

        $classes = $component->get('structureClasses');
        $primary = collect($classes)->first(
            fn (array $row) => str_contains((string) $row['name'], 'Primary One')
                || (string) $row['name'] === 'P.1'
                || str_starts_with((string) $row['name'], 'P1')
        );
        if ($primary === null) {
            $primary = $classes[0];
        }

        $sectionId = (int) $primary['section_id'];
        $baseName = (string) $primary['name'];

        $component
            ->set('structureStreamDrafts.'.$sectionId, 'East')
            ->call('addStructureStream', $sectionId)
            ->assertSet('errorMessage', '');

        $this->assertTrue(
            Section::query()
                ->where('school_id', $this->school->id)
                ->where('name', $baseName.' East')
                ->exists()
        );
        $this->assertTrue(
            Section::query()
                ->whereKey($sectionId)
                ->exists(),
            'Base class must remain after adding a stream'
        );

        $updated = collect($component->get('structureClasses'))
            ->firstWhere('section_id', $sectionId);
        $labels = array_column($updated['streams'] ?? [], 'label');
        $this->assertContains('East', $labels);
    }

    public function test_invite_structure_class_teacher_assigns_ct(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToStructure($component);

        $classes = $component->get('structureClasses');
        $target = $classes[0];
        $sectionId = (int) $target['section_id'];

        $component
            ->set('structureCtDrafts.'.$sectionId, [
                'email' => 'ct-east@structure-wizard.sch.ug',
                'existing_teacher_id' => '',
                'name' => 'Grace CT',
                'phone' => '0777000111',
            ])
            ->call('inviteStructureClassTeacher', $sectionId)
            ->assertSet('errorMessage', '');

        $link = StandardLink::query()
            ->where('school_id', $this->school->id)
            ->where('section_id', $sectionId)
            ->first();
        $this->assertNotNull($link?->class_teacher_id);

        $teacher = User::find($link->class_teacher_id);
        $this->assertSame('ct-east@structure-wizard.sch.ug', $teacher->email);
        $this->assertSame(5, (int) $teacher->usergroup_id);

        Mail::assertQueued(TeacherInviteMail::class);
    }

    public function test_students_step_defaults_stream_when_class_has_streams(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToStructure($component);

        $classes = $component->get('structureClasses');
        $primary = $classes[0];
        $sectionId = (int) $primary['section_id'];
        $baseName = (string) $primary['name'];

        $component
            ->set('structureStreamDrafts.'.$sectionId, 'West')
            ->call('addStructureStream', $sectionId);

        $studentIdx = null;
        foreach ($component->instance()->steps as $i => $step) {
            if (($step['key'] ?? '') === 'students') {
                $studentIdx = $i;
                break;
            }
        }
        $this->assertNotNull($studentIdx);
        $component->call('goToStep', $studentIdx);

        $this->assertSame(
            'students',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        $component
            ->set('studentClass', $baseName)
            ->assertSet('studentStream', 'West');

        $component->assertSeeHtml('Base class (no stream)');
        $this->assertTrue($component->get('schoolHasStreams'));
    }

    public function test_structure_snapshot_lists_base_and_streams(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToStructure($component);

        $year = \App\Models\AcademicYear::where('school_id', $this->school->id)->first();
        $this->assertNotNull($year);

        $snap = app(ClassStructureService::class)->structureSnapshot($this->school, $year);
        $this->assertNotEmpty($snap);
        $this->assertArrayHasKey('section_id', $snap[0]);
        $this->assertArrayHasKey('streams', $snap[0]);
        $this->assertArrayHasKey('class_teacher_id', $snap[0]);
    }
}
