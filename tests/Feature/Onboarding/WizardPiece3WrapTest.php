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

/**
 * Piece 3 wrap — residual polish after gaps 4–7.
 */
class WizardPiece3WrapTest extends TestCase
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
            'name' => 'Wrap School',
            'email' => 'wrap@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'wrap-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Wrap Admin',
            'email' => 'admin@wrap.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Wrap',
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
            ->set('schoolName', 'Wrap Academy')
            ->call('next')
            ->set('studentSize', '100-300 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-WRAP')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next');
    }

    private function goToStepKey(object $component, string $key): void
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search($key, $keys, true);
        $this->assertNotFalse($index, "step {$key} missing");
        $component->call('goToStep', $index);
        $component->call('goToStep', $index);
    }

    public function test_uneb_reg_field_only_shown_for_candidate_classes(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);
        $this->goToStepKey($component, 'students');

        $p1 = Section::where('school_id', $this->school->id)
            ->whereRaw('LOWER(name) LIKE ?', ['%p.1%'])
            ->orWhere(function ($q) {
                $q->where('school_id', $this->school->id)
                    ->whereRaw('LOWER(name) LIKE ?', ['%primary one%']);
            })
            ->orderBy('id')
            ->first()
            ?? Section::where('school_id', $this->school->id)->orderBy('id')->first();

        $p7 = Section::where('school_id', $this->school->id)
            ->get()
            ->first(fn ($s) => \App\Services\OnboardingEngine::isCandidateClass((string) $s->name));

        $this->assertNotNull($p1);

        $component->set('studentClass', $p1->name);
        $html = $component->html();
        $this->assertStringNotContainsString('data-testid="wizard-student-board-reg"', $html);

        if ($p7) {
            $component
                ->set('studentClass', $p7->name)
                ->set('studentBoardRegNumber', 'U9999/001');
            $this->assertStringContainsString('data-testid="wizard-student-board-reg"', $component->html());

            $component->set('studentClass', $p1->name);
            $this->assertSame('', $component->get('studentBoardRegNumber'));
            $this->assertStringNotContainsString('data-testid="wizard-student-board-reg"', $component->html());
        }
    }

    public function test_wrap_blade_contract_mentions_terms_deferral_and_uneb_gate(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php'));
        $this->assertStringContainsString('wizard-terms-intro', $blade);
        $this->assertStringContainsString('Dates can be adjusted later in admin settings', $blade);
        $this->assertStringContainsString('isCandidateClass', $blade);
        $this->assertStringContainsString('wizard-student-uneb-wrap', $blade);
    }
}
