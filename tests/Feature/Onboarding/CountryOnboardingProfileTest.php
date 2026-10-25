<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Onboarding\Country\CountryOnboardingProfile;
use App\Onboarding\Country\CountryProfiles;
use App\Onboarding\Country\UgandaOnboardingProfile;
use App\Onboarding\Steps\Steps\TermsStep;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Uganda keeps today's onboarding behaviour. Kenya, India and Brazil are not
 * registered yet; a profile can be plugged in without changing the steps.
 */
class CountryOnboardingProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CountryProfiles::reset();
    }

    protected function tearDown(): void
    {
        CountryProfiles::reset();
        parent::tearDown();
    }

    public function test_uganda_keeps_emis_uneb_ugx_and_the_wizard_term_dates(): void
    {
        $profile = CountryProfiles::forCountry('Uganda');

        $this->assertSame('uganda', $profile->key());
        $this->assertTrue($profile->requiresMinistryCode());
        $this->assertSame('EMIS / Ministry code', $profile->ministryCodeLabel());
        $this->assertSame('UGX', $profile->currency());
        $this->assertSame('uneb', $profile->defaultCurriculum());
        $this->assertTrue(OnboardingStepsService::isUganda('Uganda'));
        $this->assertTrue(OnboardingStepsService::isUganda('ug'));
        $this->assertTrue(OnboardingStepsService::isUganda('UG'));
        $this->assertSame([
            ['name' => 'Term 1', 'start' => '2031-02-03', 'end' => '2031-05-02'],
            ['name' => 'Term 2', 'start' => '2031-05-26', 'end' => '2031-08-29'],
            ['name' => 'Term 3', 'start' => '2031-09-22', 'end' => '2031-12-19'],
        ], $profile->defaultTerms(2031));
    }

    public function test_kenya_india_and_brazil_stay_on_the_fallback(): void
    {
        foreach (['Kenya', 'India', 'Brazil', 'Tanzania', '', null] as $country) {
            $profile = CountryProfiles::forCountry($country);

            $this->assertSame('generic', $profile->key(), (string) $country);
            $this->assertFalse($profile->requiresMinistryCode(), (string) $country);
            $this->assertNull($profile->currency(), (string) $country);
            $this->assertSame('uneb', $profile->defaultCurriculum(), (string) $country);
            $this->assertFalse(OnboardingStepsService::isUganda(is_string($country) ? $country : null));
            $this->assertSame(
                (new UgandaOnboardingProfile())->defaultTerms(2031),
                $profile->defaultTerms(2031),
                (string) $country,
            );
        }
    }

    public function test_a_plugged_in_profile_is_used_for_its_country_only(): void
    {
        CountryProfiles::register(new KenyaPlugInProfile());

        $kenya = $this->school('Kenya');
        $this->year($kenya, '2031');
        app(TermsStep::class)->save($kenya, 'defaults');

        $kenyaTerms = AcademicTerm::where('school_id', $kenya->id)->orderBy('id')->get();
        $this->assertCount(1, $kenyaTerms);
        $this->assertSame('Kenya Term', $kenyaTerms[0]->name);
        $this->assertSame('2031-01-08', $kenyaTerms[0]->starts_on->format('Y-m-d'));
        $this->assertSame('2031-04-10', $kenyaTerms[0]->ends_on->format('Y-m-d'));

        $uganda = $this->school('Uganda');
        $this->year($uganda, '2031');
        app(TermsStep::class)->save($uganda, 'defaults');

        $ugandaTerms = AcademicTerm::where('school_id', $uganda->id)->orderBy('id')->get();
        $this->assertCount(3, $ugandaTerms);
        $this->assertSame('2031-02-03', $ugandaTerms[0]->starts_on->format('Y-m-d'));
        $this->assertSame('2031-12-19', $ugandaTerms[2]->ends_on->format('Y-m-d'));
        $this->assertSame('UGX', CountryProfiles::forCountry('Uganda')->currency());
        $this->assertSame('KES', CountryProfiles::forCountry('Kenya')->currency());
    }

    public function test_emis_follows_the_profile_not_a_hardcoded_country_name(): void
    {
        $kenya = $this->school('Kenya');
        $this->assertArrayNotHasKey('emis', OnboardingStepsService::applicableSteps($kenya));
        $this->assertTrue(OnboardingStepsService::isEmisComplete($kenya));

        $uganda = $this->school('Uganda');
        $this->assertArrayHasKey('emis', OnboardingStepsService::applicableSteps($uganda));
        $this->assertFalse(OnboardingStepsService::isEmisComplete($uganda));

        CountryProfiles::register(new class implements CountryOnboardingProfile
        {
            public function key(): string
            {
                return 'wakanda';
            }

            public function matches(?string $country): bool
            {
                return strcasecmp(trim((string) $country), 'Wakanda') === 0;
            }

            public function requiresMinistryCode(): bool
            {
                return true;
            }

            public function ministryCodeLabel(): string
            {
                return 'Ministry code';
            }

            public function currency(): ?string
            {
                return null;
            }

            public function defaultCurriculum(): string
            {
                return 'uneb';
            }

            public function defaultTerms(int $year): array
            {
                return [];
            }
        });

        $wakanda = $this->school('Wakanda');
        $this->assertFalse(OnboardingStepsService::isUganda('Wakanda'));
        $this->assertArrayHasKey('emis', OnboardingStepsService::applicableSteps($wakanda));
        $this->assertFalse(OnboardingStepsService::isEmisComplete($wakanda));

        $wakanda->ministry_code = 'WK-1';
        $wakanda->save();
        $this->assertTrue(OnboardingStepsService::isEmisComplete($wakanda->fresh()));
    }

    public function test_playbook_keeps_the_year_placeholders_and_the_real_date_message(): void
    {
        $playbook = file_get_contents(base_path('docs/onboarding/playbook.md'));
        $this->assertNotFalse($playbook);
        $this->assertStringContainsString('{year}', $playbook);
        $this->assertStringContainsString('{next}', $playbook);
        $this->assertStringContainsString('The academic year end is not a valid date.', $playbook);
        $this->assertStringNotContainsString('must be a valid date', $playbook);
        $this->assertStringContainsString('Exams and SchoolPay are not part of onboarding.', $playbook);
    }

    private function school(string $country): School
    {
        $uid = uniqid();

        return School::create([
            'name' => 'Profile School '.$uid,
            'email' => "profile-{$uid}@test.sch.ug",
            'phone' => '07'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'slug' => 'profile-school-'.$uid,
            'status' => 1,
            'registration_country' => $country,
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);
    }

    private function year(School $school, string $name): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => $name,
            'description' => 'Current Academic Year',
            'start_date' => $name.'-01-01 00:00:00',
            'end_date' => $name.'-12-31 23:59:59',
            'status' => 1,
        ]);
    }
}

/**
 * Stand-in for the Kenya profile. It is not registered in production.
 */
final class KenyaPlugInProfile implements CountryOnboardingProfile
{
    public function key(): string
    {
        return 'kenya';
    }

    public function matches(?string $country): bool
    {
        return strcasecmp(trim((string) $country), 'Kenya') === 0;
    }

    public function requiresMinistryCode(): bool
    {
        return false;
    }

    public function ministryCodeLabel(): string
    {
        return 'NEMIS';
    }

    public function currency(): ?string
    {
        return 'KES';
    }

    public function defaultCurriculum(): string
    {
        return 'uneb';
    }

    public function defaultTerms(int $year): array
    {
        return [
            ['name' => 'Kenya Term', 'start' => $year.'-01-08', 'end' => $year.'-04-10'],
        ];
    }
}
