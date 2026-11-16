<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\ProgressiveRulesChange;
use App\Models\ReportDivisionRange;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Reports\GradingPresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * T-154: the grading settings screen (grade bands and points, division ranges, ranking) with presets,
 * a live example, a reset, "last changed by", validation and who may edit. Invented school and people.
 */
class GradingSettingsScreenTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/admin/reports/cards/progressive-rules';

    private School $school;

    private Standard $standard;

    private User $admin;

    private User $head;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = $this->makeSchool('Rules Screen School');
        AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        GradingHelper::seedGradingForSchool((int) $this->school->id, 'primary');
        $this->admin = $this->user($this->school, 3, 'Screen Admin');
        $this->head = $this->user($this->school, 4, 'Screen Head');
    }

    public function test_the_screen_says_changes_apply_from_the_next_release_and_released_reports_never_change(): void
    {
        $this->actingAs($this->admin)->get(self::URL.'?standard='.$this->standard->id)
            ->assertOk()
            ->assertSee('data-testid="rules-apply-note"', false)
            ->assertSee('apply from the next release', false)
            ->assertSee('never changes', false);
    }

    public function test_the_screen_offers_the_three_generic_presets(): void
    {
        $html = $this->actingAs($this->admin)->get(self::URL.'?standard='.$this->standard->id)->assertOk()->getContent();

        $this->assertStringContainsString('>App default<', $html);
        $this->assertStringContainsString('Older seeded scale (confirm with the school)', $html);
        $this->assertStringContainsString('>Custom<', $html);
        // The preset names are generic: exactly these three options, nothing named for a school or a country.
        preg_match('/<select id="rules-preset".*?<\/select>/s', $html, $select);
        preg_match_all('/<option value="([^"]+)"[^>]*>([^<]+)<\/option>/', $select[0] ?? '', $options, PREG_SET_ORDER);
        $this->assertSame([
            ['default', 'App default'],
            ['older-seeded', 'Older seeded scale (confirm with the school)'],
            ['custom', 'Custom'],
        ], array_map(fn ($o) => [$o[1], $o[2]], $options));
    }

    public function test_loading_the_older_seeded_preset_fills_the_form_but_saves_nothing(): void
    {
        $before = SchoolGradingSystem::query()->where('school_id', $this->school->id)->orderBy('id')->get(['grade', 'min_score', 'max_score'])->toArray();

        $html = $this->actingAs($this->admin)
            ->get(self::URL.'?standard='.$this->standard->id.'&preset='.GradingPresets::OLDER_SEEDED)
            ->assertOk()
            ->assertSee('data-testid="rules-preset-note"', false)
            ->assertSee('not saved yet', false)
            ->getContent();

        // The older table has 80 to 89 as grade 3: that value is in the form, not in the database.
        $this->assertStringContainsString('name="bands[2][min_score]" value="80"', $html);
        $this->assertSame($before, SchoolGradingSystem::query()->where('school_id', $this->school->id)->orderBy('id')->get(['grade', 'min_score', 'max_score'])->toArray());
        $this->assertSame(0, ProgressiveRulesChange::query()->count());
    }

    public function test_the_app_default_preset_shows_the_app_default_bands(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(self::URL.'?standard='.$this->standard->id.'&preset='.GradingPresets::DEFAULT)
            ->assertOk()->getContent();

        $this->assertStringContainsString('name="bands[1][min_score]" value="90"', $html);
        $this->assertStringContainsString('name="bands[2][min_score]" value="75"', $html);
        $this->assertStringContainsString('name="bands[2][max_score]" value="89"', $html);
        $this->assertStringContainsString('Nothing is saved until you save.', $html);
        $this->assertStringNotContainsString('These are the bands saved for this class level.', $html);
    }

    public function test_try_a_mark_shows_its_grade_and_points_from_the_bands_on_screen(): void
    {
        $this->actingAs($this->admin)->get(self::URL.'?standard='.$this->standard->id.'&try_mark=95')
            ->assertOk()->assertSee('data-testid="try-mark-result"', false)->assertSee('95 is grade', false)->assertSee('<strong>1</strong> point', false);

        $this->actingAs($this->admin)->get(self::URL.'?standard='.$this->standard->id.'&try_mark=0')
            ->assertOk()->assertSee('0 is grade', false)->assertSee('<strong>9</strong> points', false);

        $this->actingAs($this->admin)->get(self::URL.'?standard='.$this->standard->id.'&try_mark=140')
            ->assertOk()->assertSee('is not in any band', false);
    }

    public function test_try_a_mark_follows_a_loaded_preset(): void
    {
        // 90 is 2 points on the app default. 89 is 3 points. 78 is 3 points on the app default
        // and 4 points on the older seeded table, so the two presets still disagree.
        $this->actingAs($this->admin)
            ->get(self::URL.'?standard='.$this->standard->id.'&preset='.GradingPresets::DEFAULT.'&try_mark=90')
            ->assertOk()->assertSee('90 is grade <strong>D2</strong>', false);
        $this->actingAs($this->admin)
            ->get(self::URL.'?standard='.$this->standard->id.'&preset='.GradingPresets::DEFAULT.'&try_mark=89')
            ->assertOk()->assertSee('89 is grade <strong>C3</strong>', false);
        $this->actingAs($this->admin)
            ->get(self::URL.'?standard='.$this->standard->id.'&preset='.GradingPresets::OLDER_SEEDED.'&try_mark=78')
            ->assertOk()->assertSee('78 is grade <strong>C4</strong>', false);
        $this->actingAs($this->admin)
            ->get(self::URL.'?standard='.$this->standard->id.'&preset='.GradingPresets::DEFAULT.'&try_mark=78')
            ->assertOk()->assertSee('78 is grade <strong>C3</strong>', false);
    }

    public function test_the_screen_writes_letters_and_a_stored_number_still_prints_as_a_letter(): void
    {
        $this->actingAs($this->admin)->get(self::URL.'?standard='.$this->standard->id)
            ->assertOk()
            ->assertSee('name="bands[0][grade]" value="D1"', false)
            ->assertSee('name="bands[8][grade]" value="F9"', false);

        SchoolGradingSystem::query()
            ->where('school_id', $this->school->id)
            ->where('standard_id', $this->standard->id)
            ->where('grade', 'F9')
            ->update(['grade' => '9']);

        $this->assertSame('F9', app(\App\Services\Results\SchoolGradingScale::class)->labelFor(
            (int) $this->school->id,
            (int) $this->standard->id,
            10,
        ));
    }

    public function test_try_an_aggregate_shows_the_division_from_the_ranges_on_screen(): void
    {
        foreach ([[12, '1'], [13, '2'], [28, '3'], [32, '4'], [33, 'U']] as [$aggregate, $division]) {
            $this->actingAs($this->head)->get(self::URL.'?try_aggregate='.$aggregate)
                ->assertOk()->assertSee("Aggregate {$aggregate} is division <strong>{$division}</strong>", false);
        }
    }

    public function test_saving_records_who_changed_it_and_the_screen_shows_it(): void
    {
        $this->actingAs($this->admin)->get(self::URL)->assertOk()->assertSee('Not changed yet', false);

        $this->actingAs($this->admin)->post(self::URL, [
            'ranking_basis' => 'total',
            'use_automatic_division' => '1',
        ])->assertRedirect();

        $change = ProgressiveRulesChange::query()->latest('id')->first();
        $this->assertSame((int) $this->school->id, (int) $change->school_id);
        $this->assertSame((int) $this->admin->id, (int) $change->user_id);
        $this->assertSame('saved', $change->action);

        $this->actingAs($this->head)->get(self::URL)->assertOk()
            ->assertSee('data-testid="rules-last-changed"', false)
            ->assertSee('Last changed by '.$this->admin->name, false);
    }

    public function test_saving_says_it_applies_from_the_next_release(): void
    {
        $this->actingAs($this->admin)->followingRedirects()->post(self::URL, [
            'ranking_basis' => 'aggregate', 'use_automatic_division' => '1',
        ])->assertOk()->assertSee('applies from the next release', false)->assertSee('keep the rules they were released with', false);
    }

    public function test_a_gap_in_the_grade_bands_is_refused_and_nothing_changes(): void
    {
        $bands = $this->bandsFromScreen();
        $bands[2]['min_score'] = 77; // marks 75 and 76 would be in no band
        $before = $this->storedBands();

        $this->actingAs($this->admin)->post(self::URL, [
            'standard_id' => $this->standard->id, 'ranking_basis' => 'automatic', 'use_automatic_division' => '1', 'bands' => $bands,
        ])->assertSessionHasErrors('bands');

        $this->assertSame($before, $this->storedBands());
        $this->assertSame(0, ProgressiveRulesChange::query()->count());
    }

    public function test_overlapping_grade_bands_are_refused(): void
    {
        $bands = $this->bandsFromScreen();
        $bands[2]['min_score'] = 70;

        $this->actingAs($this->admin)->post(self::URL, [
            'standard_id' => $this->standard->id, 'ranking_basis' => 'automatic', 'use_automatic_division' => '1', 'bands' => $bands,
        ])->assertSessionHasErrors('bands');
    }

    public function test_bands_that_do_not_reach_zero_are_refused(): void
    {
        $bands = $this->bandsFromScreen();
        $bands[8]['min_score'] = 3;

        $this->actingAs($this->admin)->post(self::URL, [
            'standard_id' => $this->standard->id, 'ranking_basis' => 'automatic', 'use_automatic_division' => '1', 'bands' => $bands,
        ])->assertSessionHasErrors('bands');
    }

    public function test_valid_changed_bands_are_saved_and_logged(): void
    {
        $bands = $this->bandsFromScreen();
        $bands[1]['min_score'] = 88; // grade 2 now 88 to 94
        $bands[2]['max_score'] = 87; // grade 3 now 75 to 87

        $this->actingAs($this->admin)->post(self::URL, [
            'standard_id' => $this->standard->id, 'ranking_basis' => 'automatic', 'use_automatic_division' => '1',
            'preset' => GradingPresets::OLDER_SEEDED, 'bands' => $bands,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(88, (int) SchoolGradingSystem::query()->where('school_id', $this->school->id)->where('standard_id', $this->standard->id)->where('grade', 'D2')->value('min_score'));
        $this->assertStringContainsString('Older seeded scale', (string) ProgressiveRulesChange::query()->latest('id')->value('summary'));
    }

    public function test_division_ranges_that_overlap_have_a_gap_or_are_out_of_order_are_refused(): void
    {
        $cases = [
            'overlap' => [['label' => '1', 'min' => '', 'max' => '10'], ['label' => '2', 'min' => '10', 'max' => '']],
            'gap' => [['label' => '1', 'min' => '', 'max' => '10'], ['label' => '2', 'min' => '13', 'max' => '']],
            'out of order' => [['label' => '2', 'min' => '11', 'max' => ''], ['label' => '1', 'min' => '', 'max' => '10']],
            'closed at the bottom' => [['label' => '1', 'min' => '3', 'max' => '10'], ['label' => '2', 'min' => '11', 'max' => '']],
        ];
        foreach ($cases as $ranges) {
            $this->actingAs($this->admin)->post(self::URL, ['ranking_basis' => 'automatic', 'ranges' => $ranges])
                ->assertSessionHasErrors('ranges');
        }
        $this->assertSame(0, ReportDivisionRange::query()->count());
    }

    public function test_a_refused_band_edit_keeps_the_ranking_the_ranges_and_the_bands(): void
    {
        $this->actingAs($this->admin)->post(self::URL, [
            'standard_id' => $this->standard->id,
            'ranking_basis' => 'aggregate',
            'ranges' => [
                ['label' => '1', 'min' => '', 'max' => '12'],
                ['label' => '2', 'min' => '13', 'max' => ''],
            ],
            'bands' => $this->bandsFromScreen(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bands = $this->bandsFromScreen();
        $before = $this->storedBands();
        $bands[0]['min_score'] = 90;

        $this->actingAs($this->admin)->post(self::URL, [
            'standard_id' => $this->standard->id,
            'ranking_basis' => 'total',
            'ranges' => [
                ['label' => '1', 'min' => '', 'max' => '4'],
                ['label' => '2', 'min' => '5', 'max' => ''],
            ],
            'bands' => $bands,
        ])->assertSessionHasErrors('bands');

        $this->assertSame('aggregate', $this->standard->fresh()->ranking_basis);
        $this->assertSame(
            ['1', '2'],
            ReportDivisionRange::query()->where('school_id', $this->school->id)->orderBy('position')->pluck('label')->all(),
        );
        $this->assertSame(
            12,
            (int) ReportDivisionRange::query()->where('school_id', $this->school->id)->where('label', '1')->value('max_aggregate'),
        );
        $this->assertSame($before, $this->storedBands());
    }

    public function test_ordered_division_ranges_are_saved(): void
    {
        $this->actingAs($this->head)->post(self::URL, ['ranking_basis' => 'aggregate', 'ranges' => [
            ['label' => 'First', 'min' => '', 'max' => '8'],
            ['label' => 'Second', 'min' => '9', 'max' => '20'],
            ['label' => 'Third', 'min' => '21', 'max' => ''],
        ]])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(['First', 'Second', 'Third'], ReportDivisionRange::query()->where('school_id', $this->school->id)->orderBy('position')->pluck('label')->all());
    }

    public function test_reset_puts_the_bands_ranges_and_ranking_back_to_the_app_defaults(): void
    {
        $bands = $this->bandsFromScreen();
        $bands[1]['min_score'] = 88;
        $bands[2]['max_score'] = 87;
        $this->actingAs($this->admin)->post(self::URL, [
            'standard_id' => $this->standard->id, 'ranking_basis' => 'total', 'bands' => $bands,
            'ranges' => [['label' => 'A', 'min' => '', 'max' => '5'], ['label' => 'B', 'min' => '6', 'max' => '']],
        ])->assertRedirect();
        $this->assertSame('total', $this->standard->fresh()->ranking_basis);

        $this->actingAs($this->admin)->post(self::URL.'/reset', ['standard_id' => $this->standard->id, 'confirm' => '1'])
            ->assertRedirect();

        $this->assertNull($this->standard->fresh()->ranking_basis);
        $this->assertSame(0, ReportDivisionRange::query()->where('school_id', $this->school->id)->count());
        $this->assertSame(90, (int) SchoolGradingSystem::query()->where('school_id', $this->school->id)->where('standard_id', $this->standard->id)->where('grade', 'D2')->value('min_score'));
        $this->assertSame(89, (int) SchoolGradingSystem::query()->where('school_id', $this->school->id)->where('standard_id', $this->standard->id)->where('grade', 'C3')->value('max_score'));
        $this->assertSame('reset', ProgressiveRulesChange::query()->latest('id')->value('action'));
    }

    public function test_opening_the_screen_does_not_rewrite_bands_a_school_already_stored(): void
    {
        SchoolGradingSystem::query()
            ->where('school_id', $this->school->id)
            ->where('standard_id', $this->standard->id)
            ->where('grade', 'D2')
            ->update(['min_score' => 85, 'max_score' => 94]);
        SchoolGradingSystem::query()
            ->where('school_id', $this->school->id)
            ->where('standard_id', $this->standard->id)
            ->where('grade', 'C3')
            ->update(['min_score' => 75, 'max_score' => 84]);

        $this->actingAs($this->admin)->get(self::URL.'?standard='.$this->standard->id)->assertOk();

        $this->assertSame(85, (int) SchoolGradingSystem::query()->where('school_id', $this->school->id)->where('standard_id', $this->standard->id)->where('grade', 'D2')->value('min_score'));
        $this->assertSame(84, (int) SchoolGradingSystem::query()->where('school_id', $this->school->id)->where('standard_id', $this->standard->id)->where('grade', 'C3')->value('max_score'));
        $this->assertSame(0, ProgressiveRulesChange::query()->count());
    }

    public function test_reset_without_the_confirmation_does_nothing(): void
    {
        $this->actingAs($this->admin)->post(self::URL, ['ranking_basis' => 'total', 'use_automatic_division' => '1'])->assertRedirect();
        $this->school->refresh();
        $this->assertSame('total', $this->school->ranking_basis);

        $this->actingAs($this->admin)->post(self::URL.'/reset', [])->assertSessionHasErrors('confirm');

        $this->assertSame('total', $this->school->fresh()->ranking_basis);
    }

    public function test_another_schools_class_level_is_refused_like_a_missing_one(): void
    {
        $other = $this->makeSchool('Other Rules School');
        $foreign = Standard::create(['school_id' => $other->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $missing = Standard::query()->max('id') + 500;

        foreach ([$foreign->id, $missing] as $id) {
            $this->actingAs($this->admin)->get(self::URL.'?standard='.$id)->assertNotFound();
            $this->actingAs($this->admin)->post(self::URL, ['standard_id' => $id, 'ranking_basis' => 'total'])->assertNotFound();
            $this->actingAs($this->admin)->post(self::URL.'/reset', ['standard_id' => $id, 'confirm' => '1'])->assertNotFound();
        }
        $this->assertNull($foreign->fresh()->ranking_basis);
    }

    public function test_the_history_shows_only_this_schools_changes(): void
    {
        $other = $this->makeSchool('Other History School');
        $otherAdmin = $this->user($other, 3, 'Other Admin');
        ProgressiveRulesChange::create(['school_id' => $other->id, 'user_id' => $otherAdmin->id, 'action' => 'saved', 'summary' => 'Saved']);

        $this->actingAs($this->admin)->get(self::URL)->assertOk()->assertDontSee('Other Admin')->assertSee('Not changed yet', false);
    }

    public static function rolesThatMayEdit(): array
    {
        return ['school admin' => [3], 'head teacher' => [4]];
    }

    public static function rolesThatMayNot(): array
    {
        return ['teacher' => [5], 'student' => [6], 'parent' => [7], 'librarian' => [8], 'receptionist' => [10], 'accountant' => [11]];
    }

    #[DataProvider('rolesThatMayEdit')]
    public function test_admins_and_head_teachers_can_open_save_and_reset(int $group): void
    {
        $user = $this->user($this->school, $group, 'Allowed '.$group);

        $this->actingAs($user)->get(self::URL.'?standard='.$this->standard->id)->assertOk();
        $this->actingAs($user)->post(self::URL, ['ranking_basis' => 'total', 'use_automatic_division' => '1'])->assertRedirect();
        $this->assertSame('total', $this->school->fresh()->ranking_basis);
        $this->actingAs($user)->post(self::URL.'/reset', ['confirm' => '1'])->assertRedirect();
        $this->assertNull($this->school->fresh()->ranking_basis);
    }

    #[DataProvider('rolesThatMayNot')]
    public function test_other_roles_cannot_open_save_or_reset_and_nothing_changes(int $group): void
    {
        $user = $this->user($this->school, $group, 'Refused '.$group);

        foreach ([
            $this->actingAs($user)->get(self::URL),
            $this->actingAs($user)->post(self::URL, ['ranking_basis' => 'total', 'use_automatic_division' => '1']),
            $this->actingAs($user)->post(self::URL.'/reset', ['confirm' => '1']),
        ] as $response) {
            $this->assertContains($response->getStatusCode(), [302, 403, 404], 'role '.$group.' got '.$response->getStatusCode());
            $this->assertNotSame(200, $response->getStatusCode());
        }
        $this->assertNull($this->school->fresh()->ranking_basis);
        $this->assertSame(0, ProgressiveRulesChange::query()->count());
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(self::URL)->assertRedirect();
        $this->post(self::URL.'/reset', ['confirm' => '1'])->assertRedirect();
    }

    /** @return array<int, array{grade: string, points: int|null, min_score: int, max_score: int, remark: string}> */
    private function bandsFromScreen(): array
    {
        return SchoolGradingSystem::query()
            ->where('school_id', $this->school->id)->where('standard_id', $this->standard->id)
            ->orderByDesc('min_score')->get()
            ->map(fn ($row) => ['grade' => (string) $row->grade, 'points' => $row->points, 'min_score' => (int) $row->min_score, 'max_score' => (int) $row->max_score, 'remark' => (string) $row->remark])
            ->values()->all();
    }

    private function storedBands(): array
    {
        return SchoolGradingSystem::query()->where('school_id', $this->school->id)->orderBy('id')->get(['grade', 'points', 'min_score', 'max_score'])->toArray();
    }

    private function makeSchool(string $name): School
    {
        return School::create([
            'name' => $name, 'slug' => 'rs-'.uniqid(), 'email' => uniqid().'@rs.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1,
        ]);
    }

    private function user(School $school, int $group, string $name): User
    {
        $user = User::create([
            'name' => $name.' '.uniqid(), 'email' => uniqid().'@rs-user.test', 'password' => Hash::make('password123'),
            'usergroup_id' => $group, 'school_id' => $school->id, 'status' => 'active',
        ]);
        Userprofile::create(['user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => $group, 'status' => 'active']);

        return $user;
    }
}
