<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Founding schools must always be able to add teaching/support staff from the
 * dashboard. Plan over-limit is a notice (same rule as student CSV import),
 * never a hard "Upgrade Plan to Add More Staff" gate. Upgrade hrefs must be
 * root-relative (/pricing) so a wrong APP_URL cannot emit 127.0.0.1:8899.
 */
class StaffPlanLimitNonBlockingTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            \App\Http\Middleware\MustBePrivilege::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Staff Limit School '.Str::random(4),
            'slug' => 'staff-limit-'.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('academic_years')->insert([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'name' => 'Staff Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'firstname' => 'Staff',
            'lastname' => 'Admin',
        ]);
    }

    private function attachPlan(int $noOfUsers, bool $alsoSubscription = true): Plan
    {
        $plan = Plan::create([
            'cycle' => 30,
            'name' => 'StaffPlan'.Str::random(4),
            'display_name' => 'Staff Plan '.Str::random(4),
            'no_of_students' => 0,
            'no_of_users' => $noOfUsers,
            'is_active' => 1,
            'order' => 99,
            'amount' => 0,
        ]);

        CurrentPlan::create([
            'school_id' => $this->schoolId,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);

        if ($alsoSubscription) {
            Subscription::create([
                'school_id' => $this->schoolId,
                'user_id' => $this->admin->id,
                'plan_id' => $plan->id,
                'status' => 'approved',
            ]);
        }

        return $plan;
    }

    /** @test */
    public function freemium_unlimited_staff_shows_add_form_not_upgrade_gate(): void
    {
        // Freemium / Growth: no_of_users = 0 means unlimited. The old blade
        // compared `$count < 0`, which always failed and showed Upgrade Plan.
        $this->attachPlan(0);

        config(['app.url' => 'http://127.0.0.1:8899']);

        $response = $this->actingAs($this->admin)->get('/admin/teacher/add');

        $response->assertOk();
        $response->assertDontSee('Upgrade Plan to Add More Staff', false);
        $response->assertSee('Add Teaching Staff', false);
        $response->assertSee('add-tab-teacher', false);
        $response->assertDontSee('data-testid="staff-overlimit-notice"', false);
        $response->assertDontSee('http://127.0.0.1:8899/pricing', false);
    }

    /** @test */
    public function at_staff_limit_still_shows_form_with_notice_and_relative_pricing_link(): void
    {
        $this->attachPlan(1);

        User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->schoolId,
            'status' => 'active',
        ]);

        config(['app.url' => 'http://127.0.0.1:8899']);

        $response = $this->actingAs($this->admin)->get('/admin/teacher/add');

        $response->assertOk();
        $response->assertDontSee('Upgrade Plan to Add More Staff', false);
        $response->assertSee('add-tab-teacher', false);
        $response->assertSee('data-testid="staff-overlimit-notice"', false);
        $response->assertSee('href="/pricing"', false);
        $response->assertDontSee('http://127.0.0.1:8899/pricing', false);
    }

    /** @test */
    public function support_staff_create_also_non_blocking_with_relative_pricing_link(): void
    {
        $this->attachPlan(1);

        User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->schoolId,
            'status' => 'active',
        ]);

        config(['app.url' => 'http://127.0.0.1:8899']);

        $response = $this->actingAs($this->admin)->get('/admin/staff/add');

        $response->assertOk();
        $response->assertDontSee('Upgrade Plan to Add More Teachers', false);
        $response->assertSee('Add Support Staff', false);
        $response->assertSee('href="/pricing"', false);
        $response->assertDontSee('http://127.0.0.1:8899/pricing', false);
    }

    /** @test */
    public function admin_upgrade_and_pricing_blades_use_relative_pricing_href(): void
    {
        // Regression: url('/pricing') baked APP_URL (e.g. 127.0.0.1:8899) into CTAs.
        $paths = [
            resource_path('views/admin/member/create.blade.php'),
            resource_path('views/admin/files/videos/create.blade.php'),
            resource_path('views/admin/files/documents/create.blade.php'),
            resource_path('views/admin/payment/success.blade.php'),
            resource_path('views/layouts/partials/navigation.blade.php'),
            resource_path('views/layouts/partials/main-footer.blade.php'),
            resource_path('views/admin/member/import/import.blade.php'),
            resource_path('views/admin/teacher/create.blade.php'),
            resource_path('views/admin/staff/create.blade.php'),
            resource_path('views/partials/message.blade.php'),
        ];

        foreach ($paths as $path) {
            $contents = file_get_contents($path);
            $this->assertStringNotContainsString(
                "url('/pricing')",
                $contents,
                basename($path).' must not use url(\'/pricing\') (hard-codes APP_URL host)'
            );
            $this->assertStringNotContainsString(
                'url("/pricing")',
                $contents,
                basename($path).' must not use url("/pricing")'
            );
        }
    }
}
