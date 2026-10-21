<?php

namespace Tests\Feature\Grading;

use App\Helpers\GradingHelper;
use App\Models\School;
use App\Models\Standard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function nursery_matches_exact(): void
    {
        $this->assertSame('nursery', GradingHelper::levelTypeForStandard($this->makeStandard('nursery')));
    }

    #[Test]
    public function nursery_matches_prefixed(): void
    {
        $this->assertSame('nursery', GradingHelper::levelTypeForStandard($this->makeStandard('Nursery Class')));
    }

    #[Test]
    public function primary_matches_exact(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('primary')));
    }

    #[Test]
    public function primary_matches_space_prefix(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('Primary One')));
    }

    #[Test]
    public function primary_matches_underscore_prefix(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('primary_lower')));
    }

    #[Test]
    public function primary_upper_matches_primary(): void
    {
        $this->assertSame('primary', GradingHelper::levelTypeForStandard($this->makeStandard('primary_upper')));
    }

    #[Test]
    public function o_level_matches_exact(): void
    {
        $this->assertSame('o-level', GradingHelper::levelTypeForStandard($this->makeStandard('o-level')));
    }

    #[Test]
    public function o_level_matches_space_variant(): void
    {
        $this->assertSame('o-level', GradingHelper::levelTypeForStandard($this->makeStandard('O Level')));
    }

    #[Test]
    public function senior_matches_o_level_by_default(): void
    {
        $this->assertSame('o-level', GradingHelper::levelTypeForStandard($this->makeStandard('Senior Three')));
    }

    #[Test]
    public function senior_five_matches_a_level(): void
    {
        $this->assertSame('a-level', GradingHelper::levelTypeForStandard($this->makeStandard('Senior Five')));
    }

    #[Test]
    public function senior_six_matches_a_level(): void
    {
        $this->assertSame('a-level', GradingHelper::levelTypeForStandard($this->makeStandard('Senior Six')));
    }

    #[Test]
    public function a_level_matches_exact(): void
    {
        $this->assertSame('a-level', GradingHelper::levelTypeForStandard($this->makeStandard('a-level')));
    }

    #[Test]
    public function unknown_returns_null(): void
    {
        $this->assertNull(GradingHelper::levelTypeForStandard($this->makeStandard('foobar')));
    }
}
