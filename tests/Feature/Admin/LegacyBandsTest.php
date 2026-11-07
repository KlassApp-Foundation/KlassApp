<?php

namespace Tests\Feature\Admin;

use App\Helpers\GradingHelper;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * The legacy O-level scale is the school's own table.
 *
 * UNEB never published its pre-NLSC cut-offs as one fixed national table, so
 * the school edits the boundaries on the exam-type settings page. The D1-F9
 * letters and 1-9 points stay fixed; the shipped table is labelled as an
 * example and is only used until the school saves its own.
 */
class LegacyBandsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsFollowAlongSchools;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        Cache::flush();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->buildSchools();
    }

    /** The fixture's teacher, acting as this school's admin. */
    private function adminUser(string $which = 'real'): User
    {
        $user = $this->fx[$which]['teacher'];
        $user->forceFill(['usergroup_id' => 3])->save();

        return $user->fresh();
    }

    /** An O-level standard, so the seeding has a class to write rows for. */
    private function oLevelStandard(int $schoolId): Standard
    {
        return Standard::create([
            'school_id' => $schoolId,
            'name' => 'O-Level',
            'order' => 2,
            'status' => 1,
        ]);
    }

    /** @return array<string, array{min_score:int, max_score:int}> */
    private function bands(array $pairs): array
    {
        $out = [];
        foreach ($pairs as $grade => [$min, $max]) {
            $out[$grade] = ['min_score' => $min, 'max_score' => $max];
        }

        return $out;
    }

    public function test_the_page_shows_the_example_until_the_school_saves_its_own(): void
    {
        $admin = $this->adminUser();
        $school = $this->fx['real']['school'];
        $this->oLevelStandard((int) $school->id);

        $this->actingAs($admin)->get('/admin/settings/exam-types')
            ->assertOk()
            ->assertSee('data-testid="legacy-bands-state"', false)
            ->assertSee('example table, not a national standard')
            ->assertSee('D1')
            ->assertSee('F9');

        $this->assertNull($school->fresh()->legacy_o_level_bands);
    }

    public function test_saving_bands_stores_them_and_reseeds_the_school_scale(): void
    {
        $admin = $this->adminUser();
        $school = $this->fx['real']['school'];
        $standard = $this->oLevelStandard((int) $school->id);
        $school->forceFill(['curriculum_profile' => 'legacy'])->save();

        $response = $this->actingAs($admin)->post('/admin/settings/exam-types/bands', [
            'bands' => $this->bands([
                'D1' => [80, 100], 'D2' => [75, 79], 'C3' => [70, 74],
                'C4' => [65, 69], 'C5' => [60, 64], 'C6' => [55, 59],
                'P7' => [50, 54], 'P8' => [45, 49], 'F9' => [0, 44],
            ]),
        ]);

        $response->assertRedirect(route('admin.settings.exam-types'))->assertSessionHasNoErrors();

        $saved = $school->fresh()->legacy_o_level_bands;
        $this->assertCount(9, $saved);
        $this->assertSame('D1', $saved[0]['grade']);
        $this->assertSame(80, $saved[0]['min_score']);
        $this->assertSame(1, $saved[0]['points']);

        $row = SchoolGradingSystem::query()
            ->where('school_id', $school->id)
            ->where('standard_id', $standard->id)
            ->where('grade', 'D1')
            ->first();
        $this->assertNotNull($row, 'the school scale was re-seeded from its own bands');
        $this->assertSame(80, (int) $row->min_score);

        $this->actingAs($admin)->get('/admin/settings/exam-types')
            ->assertSee("Showing this school's saved table.");
    }

    public function test_gaps_and_overlaps_are_refused(): void
    {
        $admin = $this->adminUser();
        $school = $this->fx['real']['school'];

        // D2 stops at 74 but D1 starts at 75: 74 is covered twice and 75 twice.
        $this->actingAs($admin)->post('/admin/settings/exam-types/bands', [
            'bands' => $this->bands([
                'D1' => [75, 100], 'D2' => [74, 74], 'C3' => [70, 74],
                'C4' => [65, 69], 'C5' => [60, 64], 'C6' => [55, 59],
                'P7' => [50, 54], 'P8' => [45, 49], 'F9' => [0, 44],
            ]),
        ])->assertSessionHasErrors('bands');

        $this->assertNull($school->fresh()->legacy_o_level_bands);
    }

    public function test_one_schools_bands_never_reach_another(): void
    {
        $admin = $this->adminUser();
        $schoolA = $this->fx['real']['school'];
        $schoolB = $this->fx['demo']['school'];

        $this->actingAs($admin)->post('/admin/settings/exam-types/bands', [
            'bands' => $this->bands([
                'D1' => [90, 100], 'D2' => [85, 89], 'C3' => [80, 84],
                'C4' => [75, 79], 'C5' => [70, 74], 'C6' => [60, 69],
                'P7' => [50, 59], 'P8' => [40, 49], 'F9' => [0, 39],
            ]),
        ])->assertSessionHasNoErrors();

        $this->assertSame(90, GradingHelper::legacyBandsForSchool((int) $schoolA->id)[0]['min_score']);
        $this->assertSame(75, GradingHelper::legacyBandsForSchool((int) $schoolB->id)[0]['min_score'], 'school B still sees the example');
    }
}
