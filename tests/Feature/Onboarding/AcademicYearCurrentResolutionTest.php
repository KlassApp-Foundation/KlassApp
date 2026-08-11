<?php

namespace Tests\Feature\Onboarding;

use App\Helpers\SiteHelper;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicYearCurrentResolutionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

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

        $this->school = School::create([
            'name' => "AY Resolve's School",
            'email' => 'ay.resolve@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'ay-resolve-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'AY Admin',
            'email' => 'admin@ayresolve.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'AY',
            'lastname' => 'Admin',
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
    }

    public function test_get_academic_year_resolves_by_status_not_description(): void
    {
        Cache::forget('academic_year_for_school_'.$this->school->id);
        Cache::forget('academic_year');

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY 2026 Custom Label',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $resolved = SiteHelper::getAcademicYear($this->school->id);

        $this->assertNotNull($resolved);
        $this->assertSame($year->id, $resolved->id);
        $this->assertSame('AY 2026 Custom Label', $resolved->description);
    }

    public function test_cached_null_is_invalidated_when_academic_year_is_created(): void
    {
        Cache::forget('academic_year');
        $cacheKey = 'academic_year_for_school_'.$this->school->id;

        // Stale cached miss (array/file drivers often omit true null; use an explicit sentinel).
        $stale = new AcademicYear([
            'id' => 999999,
            'school_id' => $this->school->id,
            'description' => 'stale-cache',
            'status' => 1,
        ]);
        Cache::put($cacheKey, $stale, 3600);
        $this->assertTrue(Cache::has($cacheKey));

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Anything',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->assertFalse(Cache::has($cacheKey), 'Observer must forget academic_year_for_school_{id}');

        $resolved = SiteHelper::getAcademicYear($this->school->id);
        $this->assertNotNull($resolved);
        $this->assertNotSame(999999, $resolved->id);
        $this->assertSame(1, (int) $resolved->status);
    }

    public function test_wizard_custom_ay_description_clears_dashboard_empty_state(): void
    {
        $this->actingAs($this->admin);

        $before = $this->get('/admin/dashboard');
        $before->assertOk();
        $before->assertSee('data-testid="empty-state-product-demo"', false);

        // Simulate a prior empty-state request that cached "no current year".
        Cache::put('academic_year_for_school_'.$this->school->id, null, 3600);

        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'Custom AY Academy')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-AY-CUSTOM')
            ->call('next')
            ->call('next') // uneb skip
            ->set('academicYearDescription', 'AY 2026')
            ->set('academicYearStart', '2026-01-01')
            ->set('academicYearEnd', '2026-12-31')
            ->call('next') // seeds classes/subjects/grading
            ->set('teacherName', 'Ada')
            ->set('teacherEmail', 'ada@ayresolve.sch.ug')
            ->call('next')
            ->call('next') // students skip
            ->call('next') // terms
            ->call('next') // fees
            ->set('whatsappPhone', '+256700999111')
            ->call('next')
            ->call('next') // plan (freemium default)
            ->call('next'); // review → finish

        $component->assertSet('finished', true);

        $year = AcademicYear::where('school_id', $this->school->id)->first();
        $this->assertNotNull($year);
        $this->assertSame('AY 2026', $year->description);
        $this->assertSame(1, (int) $year->status);

        // Core bug: SiteHelper must resolve current AY by status, not description text.
        $resolved = SiteHelper::getAcademicYear($this->school->id);
        $this->assertNotNull($resolved);
        $this->assertSame($year->id, $resolved->id);

        // Same gate Dashboard uses for empty-state vs KPIs (avoid SQLite FIELD() in full dash query).
        $this->assertFalse($resolved === null);

        $plan = Plan::query()->where('name', 'Freemium')->first();
        $view = $this->view('admin.dashboard.dashboard', [
            'dashboard' => [
                'studentCount' => 0,
                'teacherCount' => 1,
                'parentCount' => 0,
                'nonteachingCount' => 0,
                'femaleCount' => 0,
                'maleCount' => 0,
                'setupIncomplete' => false,
                'whatsapp' => ['parentsOptedIn' => 0, 'messagesThisMonth' => 0],
                'noticeboard' => [],
                'feedbacks' => [],
                'events' => [],
                'products' => [],
                'upcomingExam' => [],
                'standardStudentCounts' => collect(),
                'work' => [],
                'task' => [],
                'event' => [],
                'reminder' => [],
                'fee' => [],
                'birthday' => [],
            ],
            'standardLink' => null,
            'selected_teacher' => null,
            'plan' => $plan,
            'planUsage' => [
                'students' => ['used' => 0, 'limit' => 0],
                'teachers' => ['used' => 1, 'limit' => 0],
            ],
            'onboardingMissing' => [],
            'onboardingSteps' => [],
            'setupIncomplete' => false,
            'openToshiOnboarding' => false,
            'pendingApprovals' => 0,
            'trendPeriod' => 'month',
            'feeTrend' => ['labels' => [], 'values' => []],
        ]);

        $view->assertDontSee('data-testid="empty-state-product-demo"', false);
        $view->assertSee('dashboard-kpi-card', false);
    }
}
