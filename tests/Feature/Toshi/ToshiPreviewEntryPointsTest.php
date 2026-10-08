<?php

namespace Tests\Feature\Toshi;

use App\Http\Middleware\MustBePrivilege;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A2: the floating "Toshi Agent · Talk" pill and the dashboard demo carousel's
 * Toshi slide are promo surfaces — they show only when the school is actually
 * in assistant mode. Preview and onboarding schools keep a quiet dock only.
 */
class ToshiPreviewEntryPointsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(string $mode, bool $enabled): array
    {
        $school = School::create([
            'name' => 'Toshi Entry School ' . Str::random(4),
            'email' => Str::random(8) . '@test.sch.ug',
            'phone' => '+256700' . random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => $enabled ? 1 : 0,
            'toshi_mode' => $mode,
        ]);

        // Minimal data so the dashboard renders and the empty-state demo shows
        // (year + standards exist, several steps still incomplete).
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => Str::random(8) . '@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Toshi',
            'lastname' => 'Admin',
        ]);

        return [$school, $admin];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutMiddleware([MustBePrivilege::class]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_preview_school_hides_the_pill_and_the_demo_toshi_slide(): void
    {
        [$school, $admin] = $this->makeSchool('preview', false);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        $response->assertDontSee('data-testid="toshi-pill"', false);
        $response->assertDontSee('es-demo-scene-toshi', false);
        $response->assertDontSee('Toshi helps finish');

        // the rest of the demo still renders
        $response->assertSee('es-demo-scene-whatsapp', false);
    }

    public function test_onboarding_school_also_hides_the_pill_and_demo_slide(): void
    {
        [$school, $admin] = $this->makeSchool('onboarding', false);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        $response->assertDontSee('data-testid="toshi-pill"', false);
        $response->assertDontSee('es-demo-scene-toshi', false);
    }

    public function test_assistant_school_shows_the_pill_and_the_demo_toshi_slide(): void
    {
        [$school, $admin] = $this->makeSchool('assistant', true);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        $response->assertSee('data-testid="toshi-pill"', false);
        $response->assertSee('es-demo-scene-toshi', false);
        $response->assertSee('Toshi helps finish');
    }
}
