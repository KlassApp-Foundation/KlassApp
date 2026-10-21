<?php

namespace Tests\Feature\Onboarding;

use App\Events\SchoolOnboardingFinished;
use App\Livewire\AgentToshi;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\FinishSchoolOnboarding;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Toshi's confirm must finish onboarding the same way the wizard's confirm does.
 */
class FinishSchoolOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Event::fake([SchoolOnboardingFinished::class]);
        Mail::fake();
        Notification::fake();
        Bus::fake();
        Queue::fake();
    }

    public function test_toshi_completion_sets_onboarding_finished_at_and_the_same_follow_ups_as_the_wizard(): void
    {
        [$toshiAdmin, $toshiSchool] = $this->readySchool('toshi-finish');

        $this->actingAs($toshiAdmin);

        Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $toshiSchool->id)
            ->set('scope', 'school')
            ->set('schoolName', $toshiSchool->name)
            ->call('confirmOnboarding')
            ->assertSet('step', 99)
            ->assertSet('mode', 'preview');

        $toshiSchool->refresh();
        $this->assertNotNull($toshiSchool->onboarding_finished_at);
        $this->assertTrue(OnboardingStepsService::isOnboardingFinished($toshiSchool));
        Event::assertDispatched(
            SchoolOnboardingFinished::class,
            fn (SchoolOnboardingFinished $event) => (int) $event->school->id === (int) $toshiSchool->id
        );

        [$wizardAdmin, $wizardSchool] = $this->readySchool('wizard-finish');
        $this->actingAs($wizardAdmin);

        Livewire::test(ManualOnboardingWizard::class)
            ->call('confirmReview')
            ->assertSet('finished', true)
            ->assertDispatched('manual-onboarding-finished');

        $wizardSchool->refresh();
        $this->assertNotNull($wizardSchool->onboarding_finished_at);
        $this->assertTrue(OnboardingStepsService::isOnboardingFinished($wizardSchool));
        Event::assertDispatched(
            SchoolOnboardingFinished::class,
            fn (SchoolOnboardingFinished $event) => (int) $event->school->id === (int) $wizardSchool->id
        );

        Mail::assertNothingSent();
        Notification::assertNothingSent();
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
    }

    public function test_wizard_confirm_review_still_finishes_once(): void
    {
        [$admin, $school] = $this->readySchool('wizard-same');
        $this->actingAs($admin);

        Livewire::test(ManualOnboardingWizard::class)
            ->call('confirmReview')
            ->assertSet('finished', true)
            ->assertDispatched('manual-onboarding-finished');

        $school->refresh();
        $this->assertNotNull($school->onboarding_finished_at);
        $this->assertTrue(OnboardingStepsService::isOnboardingFinished($school));
        Event::assertDispatchedTimes(SchoolOnboardingFinished::class, 1);
    }

    public function test_finishing_a_second_time_does_not_duplicate_follow_ups(): void
    {
        [$admin, $school] = $this->readySchool('second-finish');
        $this->actingAs($admin);

        $action = app(FinishSchoolOnboarding::class);
        $this->assertTrue($action->finish($school));
        $school->refresh();
        $finishedAt = $school->onboarding_finished_at;
        $updatedAt = $school->updated_at;

        $this->travel(5)->minutes();

        $this->assertFalse($action->finish($school->fresh()));
        $school->refresh();
        $this->assertTrue($finishedAt->eq($school->onboarding_finished_at));
        $this->assertTrue($updatedAt->eq($school->updated_at));
        Event::assertDispatchedTimes(SchoolOnboardingFinished::class, 1);

        Livewire::test(ManualOnboardingWizard::class)
            ->call('confirmReview')
            ->assertSet('finished', true)
            ->assertNotDispatched('manual-onboarding-finished');

        $school->refresh();
        $this->assertTrue($finishedAt->eq($school->onboarding_finished_at));
        Event::assertDispatchedTimes(SchoolOnboardingFinished::class, 1);

        Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $school->id)
            ->set('scope', 'school')
            ->set('schoolName', $school->name)
            ->call('confirmOnboarding');

        $school->refresh();
        $this->assertTrue($finishedAt->eq($school->onboarding_finished_at));
        Event::assertDispatchedTimes(SchoolOnboardingFinished::class, 1);

        Mail::assertNothingSent();
        Notification::assertNothingSent();
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
    }

    /**
     * @return array{0: User, 1: School}
     */
    private function readySchool(string $slug): array
    {
        $school = School::create([
            'name' => 'Ready '.ucfirst($slug),
            'email' => $slug.'@test.sch.ug',
            'phone' => '0700'.substr(md5($slug), 0, 6),
            'slug' => $slug,
            'status' => 1,
            'curriculum' => 'cambridge',
            'registration_country' => 'Kenya',
            'student_size' => 'Up to 500',
            'toshi_mode' => 'onboarding',
            'toshi_enabled' => 0,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Ready Admin',
            'email' => 'admin@'.$slug.'.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $phase = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P1',
            'status' => '1',
        ]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);
        Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'name' => 'Maths',
        ]);
        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->startOfYear()->addMonths(4),
            'status' => 'current',
        ]);
        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $phase->id,
            'name' => 'Tuition',
            'amount' => 1000,
        ]);
        WhatsAppUser::create([
            'phone' => '+2567'.substr(preg_replace('/\D/', '', md5($slug)), 0, 8),
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'verified_at' => now(),
            'opted_in' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Freemium '.$slug,
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
        CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);

        return [$admin, $school];
    }
}
