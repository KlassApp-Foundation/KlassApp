<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests that AgentToshi::commitAll() delegates content-step persistence
 * (standards, subjects, terms, fees) to OnboardingEngine, fixing two real bugs:
 *
 * Bug 1 — Single-Standard mapping for mixed-level schools:
 *   The old code created ONE $phase Standard (named after $this->schoolType,
 *   e.g. "primary") and mapped ALL classes to it. For a mixed-level school
 *   (e.g. nursery + primary + o-level), this made subjects/fees invisible to
 *   tiers that didn't match the one Standard. The engine creates per-class
 *   tier Standards (nursery, primary, o-level, a-level) correctly.
 *
 * Bug 2 — SchoolCategorySeeder never ran for Toshi:
 *   The old code never called SchoolCategorySeeder, so canonical defaults
 *   (core subjects, Standard rows for each tier) were missing. The engine
 *   runs it when school_category is set.
 */
class ToshiContentStepDelegationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Delegation Test School',
            'email' => 'delegation@test.sch.ug',
            'phone' => '0700000001',
            'slug' => 'delegation-test-school',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Delegation Admin',
            'email' => 'admin@delegation.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'Current Academic Year',
        ]);
    }

    /** @test */
    public function toshi_creates_per_class_tier_standards_not_single_phase(): void
    {
        // Bug 1 regression: mixed-level school must get SEPARATE tier Standards,
        // not one $phase mapped to everything.

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('standards', [
            ['name' => 'Baby Class'],
            ['name' => 'P1'],
            ['name' => 'S1'],
        ]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('studentList', []);
        $component->set('actionData', []);
        $component->call('commit');

        // Three DISTINCT tier Standards should exist: nursery, primary, o-level
        $standardNames = Standard::where('school_id', $this->school->id)
            ->pluck('name')
            ->sort()
            ->values()
            ->toArray();

        $this->assertContains('nursery', $standardNames,
            'A nursery class must create a nursery Standard, not map to a single phase');
        $this->assertContains('primary', $standardNames,
            'A P1 class must create a primary Standard');
        $this->assertContains('o-level', $standardNames,
            'An S1 class must create an o-level Standard, not map to the primary phase');

        // Each StandardLink must reference the CORRECT tier Standard
        $babyLink = StandardLink::where('school_id', $this->school->id)
            ->whereHas('section', fn($q) => $q->where('name', 'Baby Class'))
            ->first();
        $this->assertNotNull($babyLink, 'Baby Class section must have a StandardLink');

        $babyStandard = Standard::find($babyLink->standard_id);
        $this->assertEquals('nursery', $babyStandard->name,
            'Bug 1 regression: Baby Class StandardLink must reference nursery tier, not primary');

        $s1Link = StandardLink::where('school_id', $this->school->id)
            ->whereHas('section', fn($q) => $q->where('name', 'S1'))
            ->first();
        $this->assertNotNull($s1Link, 'S1 section must have a StandardLink');

        $s1Standard = Standard::find($s1Link->standard_id);
        $this->assertEquals('o-level', $s1Standard->name,
            'Bug 1 regression: S1 StandardLink must reference o-level tier, not primary');
    }

    /** @test */
    public function toshi_delegates_subjects_to_engine(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('standards', [
            ['name' => 'P1'],
            ['name' => 'P2'],
        ]);
        $component->set('subjects', [
            'P1' => ['Mathematics', 'English'],
            'P2' => ['Science'],
        ]);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('studentList', []);
        $component->set('actionData', []);
        $component->call('commit');

        // Subjects created via OnboardingEngine::saveSubjects (which uses
        // LIKE matching for stream sections and firstOrCreate for idempotency).
        // Use DB::table() raw queries because Subject model has a getNameAttribute
        // accessor that uppercases names, making Eloquent where('name', ...) unreliable.
        $p1Section = Section::where('school_id', $this->school->id)
            ->where('name', 'P1')->first();
        $this->assertNotNull($p1Section);

        $p1Math = DB::table('subjects')
            ->where('school_id', $this->school->id)
            ->where('section_id', $p1Section->id)
            ->where('name', 'Mathematics')
            ->first();
        $this->assertNotNull($p1Math, 'P1 Mathematics must be created via engine');

        $p1English = DB::table('subjects')
            ->where('school_id', $this->school->id)
            ->where('section_id', $p1Section->id)
            ->where('name', 'English')
            ->first();
        $this->assertNotNull($p1English, 'P1 English must be created via engine');

        $p2Section = Section::where('school_id', $this->school->id)
            ->where('name', 'P2')->first();
        $this->assertNotNull($p2Section);

        $p2Science = DB::table('subjects')
            ->where('school_id', $this->school->id)
            ->where('section_id', $p2Section->id)
            ->where('name', 'Science')
            ->first();
        $this->assertNotNull($p2Science, 'P2 Science must be created via engine');
    }

    /** @test */
    public function toshi_delegates_terms_to_engine(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('standards', [['name' => 'P1']]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', [
            ['name' => 'Term 1', 'start' => '2026-02-01', 'end' => '2026-05-01'],
            ['name' => 'Term 2', 'start' => '2026-05-20', 'end' => '2026-08-30'],
        ]);
        $component->set('studentList', []);
        $component->set('actionData', []);
        $component->call('commit');

        // Terms created via OnboardingEngine::saveTerms (firstOrCreate, idempotent)
        $t1 = AcademicTerm::where('school_id', $this->school->id)
            ->where('name', 'Term 1')->first();
        $this->assertNotNull($t1, 'Term 1 must be created via engine');
        $this->assertEquals('2026-02-01', $t1->starts_on->toDateString());

        $t2 = AcademicTerm::where('school_id', $this->school->id)
            ->where('name', 'Term 2')->first();
        $this->assertNotNull($t2, 'Term 2 must be created via engine');
        $this->assertEquals('2026-05-20', $t2->starts_on->toDateString());
    }

    /** @test */
    public function toshi_delegates_fees_to_engine_whole_school_spread(): void
    {
        // Bug 1 regression (known bug pattern #7): whole-school fees must be spread
        // across ALL grading tiers (one row per Standard with section_id=NULL),
        // not scoped to a single arbitrary Standard.

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('standards', [
            ['name' => 'P1'],
            ['name' => 'S1'],
        ]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('fees', ['Tuition', 'Uniform']); // string[] of names
        $component->set('studentList', []);
        $component->set('actionData', []);
        $component->call('commit');

        // Fees must be spread: one row per Standard per fee name, section_id=NULL
        $standards = Standard::where('school_id', $this->school->id)->get();
        $this->assertGreaterThanOrEqual(2, $standards->count(),
            'A mixed-level school must have at least 2 tier Standards');

        foreach ($standards as $standard) {
            foreach (['Tuition', 'Uniform'] as $feeName) {
                $fee = FeesCategories::where('school_id', $this->school->id)
                    ->where('standard_id', $standard->id)
                    ->where('name', $feeName)
                    ->whereNull('section_id')
                    ->first();
                $this->assertNotNull($fee,
                    "Fee '{$feeName}' must exist for Standard '{$standard->name}' with section_id=NULL");
            }
        }
    }

    /** @test */
    public function toshi_complete_mode_also_delegates_to_engine(): void
    {
        // Verify the complete-mode (update/resume) path also delegates
        // to OnboardingEngine, not the old inline Standard::create code.

        $this->actingAs($this->admin);

        // First pass: create-mode commit to establish the school
        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('standards', [['name' => 'P1']]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', [
            ['name' => 'Term 1', 'start' => '2026-02-01', 'end' => '2026-05-01'],
        ]);
        $component->set('fees', ['Tuition']);
        $component->set('studentList', []);
        $component->set('actionData', []);
        $component->call('commit');

        // Verify all content steps were created
        $this->assertEquals(1, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertEquals(1, AcademicTerm::where('school_id', $this->school->id)->where('name', 'Term 1')->count());
        $this->assertGreaterThanOrEqual(1, FeesCategories::where('school_id', $this->school->id)->where('name', 'Tuition')->count());

        // Verify per-class tier mapping: P1 maps to 'primary' Standard
        $p1Link = StandardLink::where('school_id', $this->school->id)
            ->whereHas('section', fn($q) => $q->where('name', 'P1'))
            ->first();
        $this->assertNotNull($p1Link);
        $p1Standard = Standard::find($p1Link->standard_id);
        $this->assertEquals('primary', $p1Standard->name,
            'P1 StandardLink must map to primary tier, not a single arbitrary phase');
    }
}
