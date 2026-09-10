<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WizardSubjectsCheckpointTest extends TestCase
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
            'name' => 'Subjects Checkpoint School',
            'email' => 'subjects-checkpoint@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'subjects-checkpoint-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Subjects Admin',
            'email' => 'admin@subjects-checkpoint.sch.ug',
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

    private function advanceToSubjects(object $component): void
    {
        $component
            ->set('schoolName', 'Subjects Checkpoint School')
            ->call('next')
            ->set('studentSize', 'Under 100 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-SUBJ')
            ->call('next')
            ->call('next') // uneb
            ->call('next') // academic year → structure
            ->call('next'); // structure → subjects checkpoint
    }

    public function test_after_structure_lands_on_subjects_checkpoint_when_seeded(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToSubjects($component);

        $this->assertSame(
            'subjects',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
        $this->assertTrue(Subject::where('school_id', $this->school->id)->exists());
        $this->assertNotEmpty($component->get('existingSubjectNames'));
        $component->assertSeeHtml('data-testid="wizard-subjects-seeded"');
        $component->assertSee('Subjects already set up');
    }

    public function test_subjects_next_with_blank_add_field_is_noop_when_seeded(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToSubjects($component);

        $before = Subject::where('school_id', $this->school->id)->count();

        $component->set('subjectName', '')->call('next');

        $this->assertSame($before, Subject::where('school_id', $this->school->id)->count());
        $this->assertSame('', $component->get('errorMessage'));
        $key = $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null;
        $this->assertNotSame('subjects', $key);
    }

    public function test_phone_like_teacher_name_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToSubjects($component);

        $component
            ->set('subjectName', '')
            ->call('next')
            ->set('teacherName', '+256700111001')
            ->call('addTeacherDraft');

        $this->assertStringContainsString('phone number', $component->get('errorMessage'));
        $this->assertSame([], $component->get('teacherDrafts'));
    }
}
