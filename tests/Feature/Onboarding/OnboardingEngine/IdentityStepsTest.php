<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IdentityStepsTest extends TestCase
{
    use RefreshDatabase;

    private function createCountry(string $name, string $shortName): Country
    {
        return Country::create([
            'name' => $name,
            'short_name' => $shortName,
            'iso_code' => $shortName,
            'tel_prefix' => '+256',
            'status' => 1,
            'order' => 1,
        ]);
    }

    private function createSchool(string $name, array $overrides = []): School
    {
        return School::create(array_merge([
            'name' => $name,
            'email' => strtolower(Str::slug($name)) . '@test.sch.ug',
            'phone' => '+256700' . random_int(100000, 999999),
            'slug' => Str::slug($name),
            'status' => 1,
            'toshi_enabled' => 0,
        ], $overrides));
    }

    public function test_save_school_name_updates_name_and_slug_on_rename(): void
    {
        $school = $this->createSchool('Old Name');

        $result = app(OnboardingEngine::class)->saveSchoolName($school, 'New School Name');

        $this->assertSame($school->id, $result->id);
        $this->assertSame('New School Name', $result->fresh()->name);
        $this->assertSame('new-school-name', $result->fresh()->slug);
    }

    public function test_save_school_name_rejects_placeholder_name(): void
    {
        $school = $this->createSchool('Old Name');

        try {
            app(OnboardingEngine::class)->saveSchoolName($school, "Admin's School");
            $this->fail('Expected ValidationException for placeholder name.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }
    }

    public function test_save_school_name_rejects_short_name_below_min_three(): void
    {
        $school = $this->createSchool('Old Name');

        try {
            app(OnboardingEngine::class)->saveSchoolName($school, 'AB');
            $this->fail('Expected ValidationException for name shorter than 3 characters.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }
    }

    public function test_save_school_name_resolves_duplicate_name_with_suffix(): void
    {
        $firstSchool = $this->createSchool('Test School One');
        $secondSchool = $this->createSchool('Another School');

        app(OnboardingEngine::class)->saveSchoolName($secondSchool, 'Test School One');

        $this->assertNotSame('Test School One', $secondSchool->fresh()->name);
        $this->assertStringStartsWith('Test School One-', $secondSchool->fresh()->name);
        $this->assertSame(Str::slug($secondSchool->fresh()->name), $secondSchool->fresh()->slug);
    }

    public function test_save_country_persists_registration_country_and_country_id(): void
    {
        $this->createCountry('Uganda', 'UG');
        $school = $this->createSchool('Country Test School');

        app(OnboardingEngine::class)->saveCountry($school, 'Uganda');

        $this->assertSame('Uganda', $school->fresh()->registration_country);
        $this->assertNotNull($school->fresh()->country_id);
    }

    public function test_save_country_rejects_empty_value(): void
    {
        $school = $this->createSchool('Country Test School');

        try {
            app(OnboardingEngine::class)->saveCountry($school, '   ');
            $this->fail('Expected ValidationException for empty country.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('country', $e->errors());
        }
    }

    public function test_save_curriculum_persists_valid_choice(): void
    {
        $school = $this->createSchool('Curriculum Test School');

        app(OnboardingEngine::class)->saveCurriculum($school, 'UNEB');

        $this->assertSame('uneb', $school->fresh()->curriculum);
    }

    public function test_save_curriculum_rejects_invalid_value(): void
    {
        $school = $this->createSchool('Curriculum Test School');

        try {
            app(OnboardingEngine::class)->saveCurriculum($school, 'not-a-curriculum');
            $this->fail('Expected ValidationException for invalid curriculum.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('curriculum', $e->errors());
        }
    }

    public function test_save_curriculum_does_not_change_toshi_enabled(): void
    {
        $school = $this->createSchool('Curriculum Test School', ['toshi_enabled' => 0]);

        app(OnboardingEngine::class)->saveCurriculum($school, 'cambridge');

        $this->assertSame(0, $school->fresh()->toshi_enabled);
    }

    public function test_save_emis_persists_for_ugandan_school(): void
    {
        $school = $this->createSchool('Emis Test School', ['registration_country' => 'Uganda']);

        app(OnboardingEngine::class)->saveEmis($school, 'EMIS-123456');

        $this->assertSame('EMIS-123456', $school->fresh()->ministry_code);
    }

    public function test_save_emis_throws_for_empty_code_when_uganda(): void
    {
        $school = $this->createSchool('Emis Test School', ['registration_country' => 'Uganda']);

        try {
            app(OnboardingEngine::class)->saveEmis($school, '   ');
            $this->fail('Expected ValidationException for empty EMIS code.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('ministryCode', $e->errors());
        }
    }

    public function test_save_emis_is_noop_for_non_ugandan_school(): void
    {
        $school = $this->createSchool('Emis Test School', ['registration_country' => 'Kenya']);

        app(OnboardingEngine::class)->saveEmis($school, 'EMIS-123');

        $this->assertSame('', $school->fresh()->ministry_code ?? '');
    }

    public function test_save_uneb_center_persists_value_and_empty_string(): void
    {
        $school = $this->createSchool('Uneb Test School');

        app(OnboardingEngine::class)->saveUnebCenter($school, 'UG1234567');
        $this->assertSame('UG1234567', $school->fresh()->uneb_center_number);

        app(OnboardingEngine::class)->saveUnebCenter($school, '');
        $this->assertSame('', $school->fresh()->uneb_center_number);

        app(OnboardingEngine::class)->saveUnebCenter($school, null);
        $this->assertSame('', $school->fresh()->uneb_center_number);
    }

    public function test_save_academic_year_creates_with_named_year_defaults(): void
    {
        $school = $this->createSchool('Academic Year Test School');

        $year = app(OnboardingEngine::class)->saveAcademicYear($school, '2026');

        $this->assertSame(1, AcademicYear::where('school_id', $school->id)->count());
        $this->assertSame('2026', $year->fresh()->name);
        $this->assertSame('2026-01-01', $year->fresh()->start_date->toDateString());
        $this->assertSame('2026-12-31', $year->fresh()->end_date->toDateString());
        $this->assertSame('Current Academic Year', $year->fresh()->description);
    }

    public function test_save_academic_year_persists_custom_dates(): void
    {
        $school = $this->createSchool('Academic Year Test School');

        $year = app(OnboardingEngine::class)->saveAcademicYear($school, '2025/2026', '2025-02-01', '2025-11-30', 'Senior Year');

        $this->assertSame('2025-02-01', $year->fresh()->start_date->toDateString());
        $this->assertSame('2025-11-30', $year->fresh()->end_date->toDateString());
        $this->assertSame('Senior Year', $year->fresh()->description);
    }

    public function test_save_academic_year_is_idempotent_and_updates_when_asked(): void
    {
        $school = $this->createSchool('Academic Year Test School');

        $first = app(OnboardingEngine::class)->saveAcademicYear($school, '2026', '2026-01-01', '2026-12-31');
        $second = app(OnboardingEngine::class)->saveAcademicYear($school, '2026', '2026-02-01', '2026-11-30');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('2026-02-01', $second->fresh()->start_date->toDateString());
        $this->assertSame('2026-11-30', $second->fresh()->end_date->toDateString());
    }

    public function test_save_academic_year_rejects_end_before_start(): void
    {
        $school = $this->createSchool('Academic Year Test School');

        try {
            app(OnboardingEngine::class)->saveAcademicYear($school, '2026', '2026-06-01', '2026-01-01');
            $this->fail('Expected ValidationException for end before start.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('academicYearEnd', $e->errors());
        }
    }
}
