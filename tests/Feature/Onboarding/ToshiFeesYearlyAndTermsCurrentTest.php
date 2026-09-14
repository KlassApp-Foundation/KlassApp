<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
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

class ToshiFeesYearlyAndTermsCurrentTest extends TestCase
{
    use RefreshDatabase;

    private const TERMS_STEP = 12;

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

        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 100, 'no_of_users' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Yearly Fee Test School',
            'email' => 'yearly-fee@test.sch.ug',
            'phone' => '0700111222',
            'slug' => 'yearly-fee-test-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Yearly Admin',
            'email' => 'yearly.admin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => (string) date('Y'),
            'description' => 'Current Academic Year',
            'type' => 'Current Academic Year',
            'status' => 1,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
    }

    public function test_toshi_fee_yearly_checkbox_clears_term_and_persists_without_term(): void
    {
        $this->actingAs($this->admin);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => AcademicYear::where('school_id', $this->school->id)->value('id'),
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->startOfYear()->addMonths(3),
        ]);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolName', $this->school->name)
            ->set('step', 13) // fees
            ->call('showFeeFormFn')
            ->assertSeeHtml('data-testid="toshi-fee-yearly"')
            ->set('feeFormName', 'Annual Library')
            ->set('feeFormAmount', '120000')
            ->set('feeFormTerm', 'Term I')
            ->set('feeFormIsYearly', true)
            ->call('saveFee');

        $fees = $component->get('actionData')['fees'] ?? [];
        $this->assertCount(1, $fees);
        $this->assertTrue((bool) ($fees[0]['is_yearly'] ?? false));
        $this->assertSame('', (string) ($fees[0]['term'] ?? 'x'));

        $component->call('doneFees')->call('confirmOnboarding');

        $fee = FeesCategories::where('school_id', $this->school->id)
            ->where('name', 'Annual Library')
            ->first();

        $this->assertNotNull($fee);
        $this->assertNull($fee->academic_term_id, 'Yearly fee must not bind to a term');
        $this->assertEquals(120000.0, (float) $fee->amount);
    }

    public function test_toshi_terms_mark_current_persists_explicit_status(): void
    {
        $this->actingAs($this->admin);

        $year = (int) date('Y');
        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolName', $this->school->name)
            ->set('step', self::TERMS_STEP)
            ->set('terms', [
                ['name' => 'Term I', 'start' => "{$year}-02-01", 'end' => "{$year}-04-30"],
                ['name' => 'Term II', 'start' => "{$year}-05-01", 'end' => "{$year}-08-31"],
                ['name' => 'Term III', 'start' => "{$year}-09-01", 'end' => "{$year}-12-31"],
            ])
            ->set('substep', 1)
            ->set('awaitingConfirm', true)
            ->call('confirmYes')
            ->assertSet('showTermCurrentPicker', true)
            ->assertSeeHtml('data-testid="toshi-term-current-picker"')
            ->call('markTermCurrent', 'Term II')
            ->assertSet('currentTermName', 'Term II')
            ->call('doneTermsCurrent')
            ->assertSet('showTermCurrentPicker', false);

        $terms = $component->get('terms');
        $current = collect($terms)->first(fn ($t) => ($t['status'] ?? '') === 'current');
        $this->assertSame('Term II', $current['name'] ?? null);

        $component->call('confirmOnboarding');

        $this->assertSame(
            'Term II',
            AcademicTerm::where('school_id', $this->school->id)->where('status', 'current')->value('name')
        );
        $this->assertSame(3, AcademicTerm::where('school_id', $this->school->id)->count());
    }

    public function test_plans_ensure_command_seeds_three_tiers(): void
    {
        DB::table('plans')->delete();
        $this->assertSame(0, DB::table('plans')->count());

        $this->artisan('plans:ensure')->assertSuccessful();

        $names = DB::table('plans')->orderBy('order')->pluck('name')->all();
        $this->assertSame(['freemium', 'growth', 'premium'], $names);
        $this->assertEquals(35, (float) DB::table('plans')->where('name', 'growth')->value('amount'));
        $this->assertSame(1, (int) DB::table('plans')->where('name', 'premium')->value('is_custom_pricing'));
    }
}
