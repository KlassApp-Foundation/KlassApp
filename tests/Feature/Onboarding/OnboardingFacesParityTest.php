<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Onboarding\Steps\StepIssueCatalog;
use App\Onboarding\Steps\StepIssueException;
use App\Onboarding\Steps\StepRegistry;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Onboarding\OnboardingParityHarness;
use Tests\Support\Onboarding\OnboardingSchoolFixture;
use Tests\Support\Onboarding\SunriseHilltopFixtures;
use Tests\TestCase;

/**
 * Same Sunrise or Hilltop input through the wizard and the Toshi v2 driver.
 * Snapshots must match. Steps that are not on the shared engine yet stay incomplete.
 */
class OnboardingFacesParityTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingParityHarness $harness;

    private string $previousTimezone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousTimezone = date_default_timezone_get();
        date_default_timezone_set('Africa/Kampala');
        config(['app.timezone' => 'Africa/Kampala']);
        Carbon::setTestNow(Carbon::parse('2026-10-25 12:00:00', 'Africa/Kampala'));

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        config(['toshi.onboarding_v2' => true]);

        $this->partialMock(WhatsAppOnboardingOtpService::class, function ($mock): void {
            $mock->shouldReceive('deliver')->andReturn([
                'sent' => true,
                'result' => ['status' => 'success'],
            ]);
        });

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
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

        $this->harness = new OnboardingParityHarness;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set($this->previousTimezone);

        parent::tearDown();
    }

    public function test_sunrise_wizard_and_toshi_save_the_same_snapshot(): void
    {
        $this->assertFacesMatch(SunriseHilltopFixtures::sunrise());
    }

    public function test_hilltop_wizard_and_toshi_save_the_same_snapshot(): void
    {
        $this->assertFacesMatch(SunriseHilltopFixtures::hilltop());
    }

    public function test_registry_steps_are_either_proved_or_incomplete(): void
    {
        $registry = array_map(
            fn ($step): string => $step->key(),
            app(StepRegistry::class)->all()
        );
        $proved = OnboardingParityHarness::provedSteps();
        $incomplete = OnboardingParityHarness::incompleteSteps();
        $incompleteRegistry = array_values(array_intersect($registry, array_keys($incomplete)));

        $this->assertSame([], array_values(array_intersect($proved, $incompleteRegistry)));
        $this->assertEqualsCanonicalizing($registry, array_merge($proved, $incompleteRegistry));

        foreach ($incomplete as $key => $reason) {
            $this->assertNotSame('', trim($reason), $key.' needs a reason');
        }

        $this->assertArrayHasKey('checklist_workbook', $incomplete);
        $this->assertArrayHasKey('onboarding_progress', $incomplete);
        $this->assertArrayHasKey('onboarding_completion', $incomplete);
        $this->assertArrayHasKey('school_type_preset', $incomplete);
        $this->assertArrayHasKey('confirm_before_write', $incomplete);
    }

    public function test_typed_roster_lines_are_rejected_by_all_issues_not_issues(): void
    {
        $school = $this->harness->seedSchool(
            "Roster Check's School",
            'roster-check@example.com',
            'roster-admin@example.com',
            'Roster Admin',
            '+256700000010',
        )['school'];

        $lines = [
            'teachers' => 'Hellen Namusoke, hellen.namusoke@example.com',
            'students' => 'Richard Ssekitoleko, Baby Class',
        ];

        foreach ($lines as $key => $line) {
            $step = app(StepRegistry::class)->byKey($key);
            $this->assertNotNull($step);
            $this->assertNotSame([], $step->allIssues($school, $line), $key.' allIssues missed the typed line');

            $issuesSawIt = false;
            try {
                $normalized = $step->normalize($line);
                $issuesSawIt = $step->issues($school, $normalized) !== [];
            } catch (StepIssueException) {
                $issuesSawIt = false;
            }

            $this->assertFalse($issuesSawIt, $key.' normalize rejects the typed line, so issues() never reports it');
        }
    }

    public function test_fee_rows_keep_every_issue_and_faces_compare_the_first(): void
    {
        $school = $this->harness->seedSchool(
            "Fee Check's School",
            'fee-check@example.com',
            'fee-admin@example.com',
            'Fee Admin',
            '+256700000011',
        )['school'];

        $step = app(StepRegistry::class)->byKey('fees');
        $this->assertNotNull($step);

        $issues = $step->allIssues($school, [
            ['name' => '', 'amount' => 10],
            ['name' => 'Lunch Term 1', 'amount' => 0],
        ]);

        $this->assertCount(2, $issues);
        $this->assertSame(0, $issues[0]->row);
        $this->assertSame(1, $issues[1]->row);

        $shown = $this->harness->shownFeeIssues($issues);
        $this->assertCount(1, $shown);
        $this->assertSame($issues[0]->row, $shown[0]->row);
        $this->assertSame($issues[0]->cause, $shown[0]->cause);
    }

    public function test_wizard_and_toshi_accept_the_same_school_name_and_size(): void
    {
        $school = $this->harness->seedSchool(
            "Accept Check's School",
            'accept-check@example.com',
            'accept-admin@example.com',
            'Accept Admin',
            '+256700000012',
        )['school'];

        $cases = [
            'school_name' => [
                '' => false,
                'Sunrise Primary and Nursery School' => true,
            ],
            'student_size' => [
                'huge' => false,
                'Up to 500' => true,
            ],
        ];

        foreach ($cases as $key => $answers) {
            foreach ($answers as $raw => $expected) {
                $decision = $this->harness->acceptance($school->fresh(), $key, $raw);
                $this->assertNull($decision['workbook'], 'The workbook ingester is not in the engine yet.');
                $this->assertSame($expected, $decision['wizard'], $key.' wizard '.json_encode($raw));
                $this->assertSame($expected, $decision['toshi'], $key.' toshi '.json_encode($raw));
            }
        }
    }

    public function test_academic_year_fixture_stays_2027_after_the_clock_is_pinned(): void
    {
        $this->assertSame(2026, (int) now()->year);

        $cause = StepIssueCatalog::academicYearCause();
        $this->assertStringContainsString((string) date('Y'), $cause);

        $sunrise = SunriseHilltopFixtures::sunrise();
        $hilltop = SunriseHilltopFixtures::hilltop();
        $this->assertSame('2027', $sunrise->academicYearName);
        $this->assertSame('2027', $hilltop->academicYearName);
        $this->assertNotSame((string) now()->year, $sunrise->academicYearName);
    }

    public function test_progress_and_completion_summaries_match_across_faces(): void
    {
        $fixture = SunriseHilltopFixtures::sunrise();
        [$wizardSchool, $toshiSchool] = $this->runBothFaces($fixture);

        $this->assertNotNull($wizardSchool->fresh()->onboarding_finished_at);
        $this->assertNull($toshiSchool->fresh()->onboarding_finished_at);

        $this->assertSame(
            $this->harness->progressSummary($wizardSchool),
            $this->harness->progressSummary($toshiSchool),
        );
        $this->assertFalse($this->harness->progressSummary($wizardSchool)['available']);
        $this->assertSame(
            $this->harness->completionSummary($wizardSchool),
            $this->harness->completionSummary($toshiSchool),
        );
        $this->assertFalse($this->harness->completionSummary($wizardSchool)['available']);
        $this->assertNull($this->harness->workbookDecision($wizardSchool, 'standards', []));
    }

    private function assertFacesMatch(OnboardingSchoolFixture $fixture): void
    {
        [$wizardSchool, $toshiSchool, $wizardSnapshot, $toshiSnapshot] = $this->runBothFaces($fixture, true);

        $this->assertSame($wizardSnapshot->comparable(), $toshiSnapshot->comparable());
        $this->harness->assertAnchored($fixture, $wizardSnapshot->comparable());

        $this->assertNotSame($wizardSnapshot->identity()['name'], $toshiSnapshot->identity()['name']);
        $this->assertNotSame($wizardSnapshot->identity()['emis'], $toshiSnapshot->identity()['emis']);
        $this->assertNotSame($wizardSnapshot->identity()['admin_email'], $toshiSnapshot->identity()['admin_email']);
        $this->assertStringContainsString($fixture->schoolName, $wizardSnapshot->identity()['name']);
        $this->assertStringContainsString($fixture->schoolName, $toshiSnapshot->identity()['name']);
        $this->assertSame($fixture->emis.'-W', $wizardSnapshot->identity()['emis']);
        $this->assertSame($fixture->emis.'-T', $toshiSnapshot->identity()['emis']);

        $this->assertSame(0, User::query()->where('school_id', $wizardSchool->id)->where('usergroup_id', 5)->count());
        $this->assertSame(0, User::query()->where('school_id', $toshiSchool->id)->where('usergroup_id', 5)->count());
        $this->assertNotSame([], $fixture->teachers);
    }

    /**
     * @return array{0: \App\Models\School, 1: \App\Models\School, 2?: \Tests\Support\Onboarding\SchoolSnapshot, 3?: \Tests\Support\Onboarding\SchoolSnapshot}
     */
    private function runBothFaces(OnboardingSchoolFixture $fixture, bool $withSnapshots = false): array
    {
        $local = strstr($fixture->adminEmail, '@', true);
        $wizard = $this->harness->seedSchool(
            ucfirst($fixture->key)." Wizard's School",
            $fixture->key.'-wizard@example.com',
            $local.'.wizard@example.com',
            $fixture->adminName,
            $fixture->adminPhone,
        );
        $toshi = $this->harness->seedSchool(
            ucfirst($fixture->key)." Toshi's School",
            $fixture->key.'-toshi@example.com',
            $local.'.toshi@example.com',
            $fixture->adminName,
            $fixture->adminPhone,
        );

        $wizardSnapshot = $this->harness->runWizard(
            $this,
            $wizard['admin'],
            $fixture,
            $fixture->schoolName.' Wizard',
            $fixture->emis.'-W',
        );
        $toshiSnapshot = $this->harness->runToshi(
            $toshi['school'],
            $toshi['admin'],
            $fixture,
            $fixture->schoolName.' Toshi',
            $fixture->emis.'-T',
        );

        if ($withSnapshots) {
            return [$wizard['school']->fresh(), $toshi['school']->fresh(), $wizardSnapshot, $toshiSnapshot];
        }

        return [$wizard['school']->fresh(), $toshi['school']->fresh()];
    }
}
