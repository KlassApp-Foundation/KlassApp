<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression: complete-mode selectPlan() must advance to Review/commitAll,
 * not detectMissingSteps() which only sees the DB and loops on draft-only data.
 */
class ToshiCompleteModePlanAdvancesToReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Plan $plan;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
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

        $this->plan = Plan::create([
            'name' => 'freemium',
            'display_name' => 'Freemium',
            'amount' => 0,
            'no_of_students' => 100,
            'no_of_users' => 5,
            'cycle' => 365,
            'is_active' => 1,
            'order' => 1,
        ]);

        // Genuinely new school: SaaS-style placeholder name, curriculum/category set,
        // but NO teachers/students/terms/fees in DB yet (those live in Toshi draft).
        $this->school = School::create([
            'name' => "Ada's School",
            'email' => 'ada.signup@example.test',
            'phone' => '+256700119901',
            'slug' => 'adas-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'registration_country' => 'Uganda',
            'ministry_code' => 'EMIS-PLAN-REVIEW',
            'uneb_center_number' => '',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Ada Admin',
            'email' => 'ada.admin@example.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Ada',
            'lastname' => 'Admin',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);
    }

    /** @test */
    public function complete_mode_plan_selection_reaches_review_and_commits_draft_data(): void
    {
        $this->actingAs($this->admin);

        $this->assertFalse(
            OnboardingStepsService::isStepComplete('plan_selection', $this->school),
            'plan must be incomplete before selectPlan'
        );
        $this->assertSame(0, Teacherlink::where('school_id', $this->school->id)->count());
        $this->assertSame(0, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(0, AcademicTerm::where('school_id', $this->school->id)->count());
        $this->assertSame(0, FeesCategories::where('school_id', $this->school->id)->count());

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolName', 'Ada Primary School')
            ->set('schoolCountry', 'Uganda')
            ->set('curriculum', 'uneb')
            ->set('academicYearLabel', (string) date('Y'))
            ->set('actionStep', 'onboarding_plan_selection')
            // Draft collected during complete-mode chat (not yet in DB).
            ->set('standards', [
                ['name' => 'P1'],
                ['name' => 'P2'],
            ])
            ->set('subjects', [
                'P1' => ['Mathematics', 'English'],
                'P2' => ['Science'],
            ])
            ->set('teacherList', ['Jane Teacher', 'John Teacher'])
            ->set('teacherLinks', [])
            ->set('studentList', ['Alice Student', 'Bob Student'])
            ->set('terms', [
                ['name' => 'Term 1', 'start' => date('Y').'-02-01', 'end' => date('Y').'-04-30'],
                ['name' => 'Term 2', 'start' => date('Y').'-05-01', 'end' => date('Y').'-08-15'],
                ['name' => 'Term 3', 'start' => date('Y').'-09-01', 'end' => date('Y').'-12-05'],
            ])
            ->set('fees', [])
            ->set('actionData', [
                'fees' => [
                    ['name' => 'Tuition', 'amount' => 150000],
                ],
                'students' => [],
                'teachers' => [],
            ])
            // Drop mount-time detectMissingSteps chatter so we only assert
            // what selectPlan itself produces.
            ->set('messages', []);

        $component->call('selectPlan', $this->plan->id);

        $reviewIdx = array_search('review', $component->get('steps'), true);
        $this->assertNotFalse($reviewIdx);
        $this->assertSame(
            (int) $reviewIdx,
            (int) $component->get('step'),
            'selectPlan must land on Review — not jump back via detectMissingSteps'
        );
        $this->assertNull($component->get('actionStep'));
        $this->assertNotEmpty(
            $component->get('reviewData'),
            'Review summary must be built so Confirm/commitAll is reachable'
        );

        $messages = collect($component->get('messages'))->pluck('text')->implode("\n");
        $this->assertDoesNotMatchRegularExpression(
            '/I found\s+\*\*\d+\*\*\s+thing/i',
            $messages,
            'detectMissingSteps checklist must not run after plan selection'
        );
        $this->assertStringContainsString('Review the summary below', $messages);

        $this->assertDatabaseHas('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $this->plan->id,
        ]);

        $component->call('confirmOnboarding');

        $this->assertTrue(
            (bool) ($component->get('reviewData')['committed'] ?? false),
            'Review confirm must mark committed'
        );

        $this->assertGreaterThanOrEqual(
            2,
            User::where('school_id', $this->school->id)->where('usergroup_id', 5)->count(),
            'Draft teachers must be committed'
        );
        $this->assertGreaterThanOrEqual(
            2,
            User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count(),
            'Draft students must be committed'
        );
        $this->assertGreaterThanOrEqual(
            3,
            AcademicTerm::where('school_id', $this->school->id)->count(),
            'Draft terms must be committed'
        );
        $this->assertGreaterThanOrEqual(
            1,
            FeesCategories::where('school_id', $this->school->id)->count(),
            'Draft fees must be committed'
        );
        $this->assertNotNull(CurrentPlan::where('school_id', $this->school->id)->first());
        $this->assertSame('Ada Primary School', $this->school->fresh()->name);
    }
}
