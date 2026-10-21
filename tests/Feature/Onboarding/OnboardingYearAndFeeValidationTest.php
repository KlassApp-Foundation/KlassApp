<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Onboarding\Steps\OnboardingStepReview;
use App\Onboarding\Steps\ToshiOnboardingV2Driver;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Term defaults follow the academic year being set up, and Toshi v2 refuses
 * a year reply or a fee reply that cannot be saved as given.
 */
class OnboardingYearAndFeeValidationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'UTC'));

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        config(['toshi.onboarding_v2' => true]);

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

        $this->school = School::create([
            'name' => "Ada's School",
            'email' => 'year-fee@test.sch.ug',
            'phone' => '0700000101',
            'slug' => 'year-fee',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Ada Admin',
            'email' => 'admin@year-fee.sch.ug',
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_toshi_v2_default_terms_use_the_academic_year(): void
    {
        $school = $this->schoolReadyForTerms('2027');
        $driver = app(ToshiOnboardingV2Driver::class);

        $prompt = $driver->promptNext($school, $this->admin->id);
        $this->assertSame('terms', $prompt['key']);

        $result = $driver->handleReply($school, 'defaults', $this->admin->id);

        $this->assertSame('advanced', $result['status'], (string) ($result['hint'] ?? ''));

        $terms = AcademicTerm::where('school_id', $school->id)->orderBy('id')->get();
        $this->assertCount(3, $terms);
        $this->assertSame('2027-02-02', $terms[0]->starts_on->format('Y-m-d'));
        $this->assertSame('2027-05-08', $terms[0]->ends_on->format('Y-m-d'));
        $this->assertSame('2027-05-26', $terms[1]->starts_on->format('Y-m-d'));
        $this->assertSame('2027-08-28', $terms[1]->ends_on->format('Y-m-d'));
        $this->assertSame('2027-09-14', $terms[2]->starts_on->format('Y-m-d'));
        $this->assertSame('2027-12-04', $terms[2]->ends_on->format('Y-m-d'));
        $this->assertTrue($terms->every(fn (AcademicTerm $term) => $term->starts_on->year === 2027));
    }

    public function test_wizard_terms_prefill_uses_the_academic_year(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToAcademicYear($component);

        $component
            ->set('academicYearStart', '2027-01-06')
            ->set('academicYearEnd', '2027-12-04')
            ->call('next');

        $year = AcademicYear::where('school_id', $this->school->id)->first();
        $this->assertNotNull($year);
        $this->assertSame('2027', (string) $year->name);

        $this->goToStepKey($component, 'terms');

        $drafts = $component->get('termDrafts');
        $this->assertCount(3, $drafts);
        $this->assertSame('2027-02-03', $drafts[0]['start']);
        $this->assertSame('2027-05-02', $drafts[0]['end']);
        $this->assertSame('2027-05-26', $drafts[1]['start']);
        $this->assertSame('2027-08-29', $drafts[1]['end']);
        $this->assertSame('2027-09-22', $drafts[2]['start']);
        $this->assertSame('2027-12-19', $drafts[2]['end']);

        $component->call('next');

        $saved = AcademicTerm::where('school_id', $this->school->id)->orderBy('id')->get();
        $this->assertCount(3, $saved);
        $this->assertSame('2027-02-03', $saved[0]->starts_on->format('Y-m-d'));
        $this->assertSame('2027-12-19', $saved[2]->ends_on->format('Y-m-d'));
    }

    public function test_toshi_v2_academic_year_accepts_a_year_and_treats_yes_as_the_suggestion(): void
    {
        $driver = app(ToshiOnboardingV2Driver::class);
        $school = $this->schoolAt('academic_year');

        $this->assertSame('academic_year', $driver->promptNext($school, $this->admin->id)['key']);

        $rejected = $driver->handleReply($school, 'yes please', $this->admin->id);
        $this->assertSame('rejected', $rejected['status']);
        $this->assertSame('academic_year', $rejected['key']);
        $this->assertStringContainsString('2026', (string) $rejected['hint']);
        $this->assertStringContainsString('2026/2027', (string) $rejected['hint']);
        $this->assertStringContainsString('yes', strtolower((string) $rejected['hint']));
        $this->assertNull(AcademicYear::where('school_id', $school->id)->first());

        $plain = $driver->handleReply($school->fresh(), '2027', $this->admin->id);
        $this->assertSame('advanced', $plain['status'], (string) ($plain['hint'] ?? ''));
        $this->assertSame('2027', (string) AcademicYear::where('school_id', $school->id)->value('name'));

        AcademicYear::where('school_id', $school->id)->delete();

        $span = $driver->handleReply($school->fresh(), '2027/2028', $this->admin->id);
        $this->assertSame('advanced', $span['status'], (string) ($span['hint'] ?? ''));
        $this->assertSame('2027/2028', (string) AcademicYear::where('school_id', $school->id)->value('name'));

        AcademicYear::where('school_id', $school->id)->delete();

        $confirmed = $driver->handleReply($school->fresh(), 'yes', $this->admin->id);
        $this->assertSame('advanced', $confirmed['status'], (string) ($confirmed['hint'] ?? ''));
        $this->assertSame('2026', (string) AcademicYear::where('school_id', $school->id)->value('name'));
    }

    public function test_toshi_v2_fee_without_an_amount_is_rejected(): void
    {
        $school = $this->schoolReadyForFees();
        $driver = app(ToshiOnboardingV2Driver::class);

        $this->assertSame('fees', $driver->promptNext($school, $this->admin->id)['key']);

        $rejected = $driver->handleReply($school, 'Tuition', $this->admin->id);

        $this->assertSame('rejected', $rejected['status']);
        $this->assertSame('fees', $rejected['key']);
        $this->assertStringContainsString('Tuition', (string) $rejected['hint']);
        $this->assertStringContainsString('amount', strtolower((string) $rejected['hint']));
        $this->assertSame(0, FeesCategories::where('school_id', $school->id)->count());

        $saved = $driver->handleReply($school->fresh(), 'Tuition 250000', $this->admin->id);
        $this->assertSame('advanced', $saved['status'], (string) ($saved['hint'] ?? ''));

        $fee = FeesCategories::where('school_id', $school->id)->where('name', 'Tuition')->first();
        $this->assertNotNull($fee);
        $this->assertEquals(250000, (float) $fee->amount);
    }

    private function schoolAt(string $target): School
    {
        $school = $this->school->fresh();
        $school->forceFill([
            'name' => 'Year Academy',
            'student_size' => 'Up to 500',
            'registration_country' => 'Kenya',
            'curriculum' => 'other',
        ])->save();

        if ($target === 'academic_year') {
            return $school->fresh();
        }

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2027',
            'description' => 'Current Academic Year',
            'start_date' => '2027-01-01 00:00:00',
            'end_date' => '2027-12-31 23:59:59',
            'status' => 1,
        ]);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        $link = StandardLink::where('school_id', $school->id)->first();
        $this->assertNotNull($link);

        Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $link->standard_id,
            'section_id' => $link->section_id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
        ]);

        OnboardingStepReview::mark($school, 'standards');
        OnboardingStepReview::mark($school, 'subjects');
        OnboardingStepsService::markStepSkipped($school, 'teachers');
        OnboardingStepsService::markStepSkipped($school, 'students');

        if ($target === 'fees') {
            AcademicTerm::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'name' => 'Term 1',
                'starts_on' => '2027-02-02',
                'ends_on' => '2027-05-08',
                'status' => 'current',
            ]);
        }

        return $school->fresh();
    }

    private function schoolReadyForTerms(string $yearName): School
    {
        $school = $this->schoolAt('terms');
        AcademicYear::where('school_id', $school->id)->update(['name' => $yearName]);

        return $school->fresh();
    }

    private function schoolReadyForFees(): School
    {
        return $this->schoolAt('fees');
    }

    private function advanceToAcademicYear(object $component): void
    {
        $component
            ->set('schoolName', 'Year Academy')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-YEAR')
            ->call('next')
            ->call('next');

        $this->assertSame(
            'academic_year',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }

    private function goToStepKey(object $component, string $key): void
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search($key, $keys, true);
        $this->assertNotFalse($index, "step {$key} missing from wizard steps");
        $component->call('goToStep', $index);
        $this->assertSame(
            $key,
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }
}
