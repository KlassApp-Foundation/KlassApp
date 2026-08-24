<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

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
}
