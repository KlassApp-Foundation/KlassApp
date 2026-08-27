<?php

namespace Tests\Feature\Grading;

use App\Helpers\GradingHelper;
use App\Models\School;
use App\Models\Standard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingHelperLevelTypeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Test School',
            'email' => 'test@example.com',
            'phone' => '+256701111111',
            'slug' => 'test-school',
            'status' => 1,
        ]);
    }

    private function makeStandard(string $name): Standard
    {
        return Standard::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'order' => 1,
            'status' => '1',
        ]);
    }

    /** @test */
    public function nursery_matches_exact(): void
    {
        $this->assertSame('nursery', GradingHelper::levelTypeForStandard($this->makeStandard('nursery')));
    }

    /** @test */
    public function nursery_matches_prefixed(): void
    {
        $this->assertSame('nursery', GradingHelper::levelTypeForStandard($this->makeStandard('Nursery Class')));
    }

    /** @test */
    public function primary_matches_exact(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('primary')));
    }

    /** @test */
    public function primary_matches_space_prefix(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('Primary One')));
    }

    /** @test */
    public function primary_matches_underscore_prefix(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('primary_lower')));
    }

    /** @test */
    public function primary_upper_matches_primary(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('primary_upper')));
    }

    /** @test */
    public function o_level_matches_exact(): void
    {
        $this->assertSame('o-level', GradingHelper::levelTypeForStandard($this->makeStandard('o-level')));
    }

    /** @test */
    public function o_level_matches_space_variant(): void
    {
        $this->assertSame('o-level', GradingHelper::levelTypeForStandard($this->makeStandard('O Level')));
    }

    /** @test */
    public function senior_matches_o_level_by_default(): void
    {
        $this->assertSame('o-level', GradingHelper::levelTypeForStandard($this->makeStandard('Senior Three')));
    }

    /** @test */
    public function senior_five_matches_a_level(): void
    {
        $this->assertSame('a-level', GradingHelper::levelTypeForStandard($this->makeStandard('Senior Five')));
    }

    /** @test */
    public function senior_six_matches_a_level(): void
    {
        $this->assertSame('a-level', GradingHelper::levelTypeForStandard($this->makeStandard('Senior Six')));
    }

    /** @test */
    public function a_level_matches_exact(): void
    {
        $this->assertSame('a-level', GradingHelper::levelTypeForStandard($this->makeStandard('a-level')));
    }

    /** @test */
    public function unknown_returns_null(): void
    {
        $this->assertNull(GradingHelper::levelTypeForStandard($this->makeStandard('foobar')));
    }
}
