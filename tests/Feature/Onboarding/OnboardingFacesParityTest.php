<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Onboarding\Country\CountryProfiles;
use App\Onboarding\Country\SchoolTypePreset;
use App\Onboarding\Steps\StepIssueCatalog;
use App\Onboarding\Steps\StepIssueException;
use App\Onboarding\Steps\StepRegistry;
use App\Onboarding\Steps\Steps\StandardsStep;
use App\Onboarding\Steps\Steps\SubjectsStep;
use App\Onboarding\Steps\Steps\TermsStep;
use App\Services\SchoolCategorySeeder;
use Tests\Support\Onboarding\SchoolSnapshot;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\Attributes\UnitTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
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

        // The unfinished-item tests only record a reason. They do not touch the database.
        if ($this->name() === 'test_unfinished_item_is_marked_incomplete') {
            return;
        }

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

    /**
     * One PHPUnit result per gap, so a run shows these as incomplete rather than as passes.
     */
    #[UnitTest]
    #[DataProvider('unfinishedItemProvider')]
    public function test_unfinished_item_is_marked_incomplete(string $key, string $reason): void
    {
        $this->assertNotSame('', trim($key));
        $this->assertNotSame('', trim($reason));
        $this->markTestIncomplete($reason);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function unfinishedItemProvider(): array
    {
        $cases = [];
        foreach (OnboardingParityHarness::incompleteSteps() as $key => $reason) {
            $cases[$key] = [$key, $reason];
        }

        return $cases;
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
        $this->assertArrayNotHasKey('onboarding_progress', $incomplete);
        $this->assertArrayNotHasKey('onboarding_completion', $incomplete);
        $this->assertArrayNotHasKey('school_type_preset', $incomplete);
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

    public function test_progress_summaries_and_completion_state_match_across_faces(): void
    {
        $fixture = SunriseHilltopFixtures::sunrise();
        [$wizardSchool, $toshiSchool] = $this->runBothFaces($fixture);

        $wizard = $this->harness->progressSummary($wizardSchool);
        $toshi = $this->harness->progressSummary($toshiSchool);
        $wizardDone = $this->harness->completionSummary($wizardSchool);
        $toshiDone = $this->harness->completionSummary($toshiSchool);

        $this->assertSame($wizard['total'], $toshi['total']);
        $this->assertSame($wizard['done'] + 1, $toshi['done']);
        $this->assertSame($wizard['total'] - 1, $wizard['done']);
        $this->assertSame($toshi['total'], $toshi['done']);
        $this->assertCount($wizard['done'], $wizard['summary']);
        $this->assertCount($toshi['done'], $toshi['summary']);
        $this->assertSame('whatsapp_verify', $wizard['current_key']);
        $this->assertNull($toshi['current_key']);

        $this->assertSame(
            $this->comparableSummary($wizard['summary']),
            $this->comparableSummary($toshi['summary']),
        );
        $this->assertContains('School name: '.$fixture->schoolName.' Wizard', $wizard['summary']);
        $this->assertContains('School name: '.$fixture->schoolName.' Toshi', $toshi['summary']);
        $this->assertContains('EMIS: '.$fixture->emis.'-W', $wizard['summary']);
        $this->assertContains('EMIS: '.$fixture->emis.'-T', $toshi['summary']);
        $this->assertContains('Classes: 10 classes, 6 streams', $wizard['summary']);
        $this->assertContains('Classes: 10 classes, 6 streams', $toshi['summary']);
        $this->assertContains('Teachers: skipped for later', $wizard['summary']);
        $this->assertContains('Teachers: skipped for later', $toshi['summary']);
        $this->assertContains('Students: 2 students', $wizard['summary']);
        $this->assertContains('Students: 2 students', $toshi['summary']);
        $this->assertContains('UNEB centre: blank', $wizard['summary']);
        $this->assertContains('Academic year: 2027, 2027-01-01 to 2027-12-31', $wizard['summary']);
        $this->assertContains('Terms: Term 1, Term 2, Term 3, current Term 1', $wizard['summary']);

        foreach ($wizard['summary'] as $line) {
            $this->assertStringStartsNotWith('WhatsApp:', $line);
        }
        $toshiWhatsapp = array_values(array_filter(
            $toshi['summary'],
            fn (string $line): bool => str_starts_with($line, 'WhatsApp:')
        ));
        $this->assertCount(1, $toshiWhatsapp);
        $this->assertStringContainsString('256700000301', preg_replace('/\D+/', '', $toshiWhatsapp[0]) ?? '');

        $this->assertTrue($wizardDone['finished']);
        $this->assertFalse($toshiDone['finished']);
        $this->assertSame([], $wizardDone['blocking']);
        $this->assertSame([], $toshiDone['blocking']);
        $this->assertSame($wizard['done'], $wizardDone['done']);
        $this->assertSame($toshi['current_key'], $toshiDone['current_key']);
        $this->assertNull($this->harness->workbookDecision($wizardSchool, 'standards', []));
    }

    public function test_school_type_preset_acceptance_matches_typing_it(): void
    {
        $this->assertArrayNotHasKey('school_type_preset', OnboardingParityHarness::incompleteSteps());

        foreach (['primary', 'o_a_level'] as $category) {
            $accepted = $this->presetSchool($category, 'accepted');
            $typed = $this->presetSchool($category, 'typed');
            $preset = SchoolTypePreset::forSchool($accepted);
            $this->assertNotNull($preset);
            $this->assertSame(
                SchoolCategorySeeder::expectedSectionNames($category),
                $preset->classNames(),
            );

            app(StandardsStep::class)->save($typed, $preset->standardsAnswer());
            app(SubjectsStep::class)->save($typed->fresh(), $preset->subjectsAnswer());
            app(TermsStep::class)->save($typed->fresh(), $preset->terms(
                CountryProfiles::forCountry('Uganda'),
                2027,
            ));

            app(StandardsStep::class)->save($accepted, 'use_this');
            app(SubjectsStep::class)->save($accepted->fresh(), 'use_this');
            app(TermsStep::class)->save($accepted->fresh(), 'use_this');

            $this->assertSame(
                SchoolSnapshot::capture($typed->fresh())->comparable(),
                SchoolSnapshot::capture($accepted->fresh())->comparable(),
            );
        }
    }

    private function presetSchool(string $category, string $face): \App\Models\School
    {
        $seed = $this->harness->seedSchool(
            'Preset '.$category.' '.$face.' School',
            'preset-'.$category.'-'.$face.'@example.com',
            'preset.'.$category.'.'.$face.'@example.com',
            'Preset Admin',
            '+256700000321',
        );
        $school = $seed['school'];
        $school->forceFill([
            'student_size' => 'Up to 500',
            'registration_country' => 'Uganda',
            'curriculum' => 'uneb',
            'school_category' => $category,
            'ministry_code' => strtoupper(substr($category, 0, 6)).'-'.substr($face, 0, 1),
            'uneb_center_number' => '',
        ])->save();

        \App\Models\AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2027',
            'description' => 'Current Academic Year',
            'start_date' => '2027-01-01 00:00:00',
            'end_date' => '2027-12-31 23:59:59',
            'status' => 1,
        ]);
        SchoolCategorySeeder::seed($school->fresh());

        return $school->fresh();
    }

    /**
     * Name, EMIS and WhatsApp are the lines the two faces are allowed to differ on.
     *
     * @param  list<string>  $summary
     * @return list<string>
     */
    private function comparableSummary(array $summary): array
    {
        return array_values(array_filter(
            $summary,
            fn (string $line): bool => ! str_starts_with($line, 'School name:')
                && ! str_starts_with($line, 'EMIS:')
                && ! str_starts_with($line, 'WhatsApp:')
        ));
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

        // Zero teachers because both faces skip the step. A name and email would
        // create the users on both; only the Toshi v2 class and subject links would drop.
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
