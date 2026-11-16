<?php

namespace Tests\Feature\Reports;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\SchoolReportKindChange;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Reports\ReportKindSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Each school chooses Progressive report or Set-based reports.
 * The choice is recorded, and a release keeps the type it was issued under.
 */
class ReportKindSettingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $other;

    private User $admin;

    private User $head;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Kind School',
            'slug' => 'kind-'.uniqid(),
            'email' => uniqid().'@kind.test',
            'phone' => '071'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
        $this->other = School::create([
            'name' => 'Other Kind School',
            'slug' => 'other-kind-'.uniqid(),
            'email' => uniqid().'@other-kind.test',
            'phone' => '072'.random_int(1000000, 9999999),
            'status' => 1,
        ]);

        $this->admin = $this->makeUser($this->school, 3, 'Kind Admin', 'admin');
        $this->head = $this->makeUser($this->school, 4, 'Kind Head', 'head');
        $this->teacher = $this->makeUser($this->school, 5, 'Kind Teacher', 'teacher');
    }

    public function test_a_new_school_starts_on_the_progressive_report(): void
    {
        $this->assertSame(ReportKindSetting::PROGRESSIVE, $this->school->fresh()->report_kind);
        $this->assertSame(ReportKindSetting::PROGRESSIVE, $this->other->fresh()->report_kind);
    }

    public function test_an_admin_and_a_head_teacher_can_save_the_report_type_with_a_history_row(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/reports/cards')
            ->assertOk()
            ->assertSee('data-testid="report-kind"', false)
            ->assertSee('Progressive report')
            ->assertSee('Set-based reports')
            ->assertSee('monthly results table')
            ->assertSee('Each exam set has its own report');

        $this->actingAs($this->head)
            ->post('/admin/reports/cards/report-kind', ['report_kind' => 'set_based'])
            ->assertRedirect()
            ->assertSessionHas('successmessage');

        $this->assertSame('set_based', $this->school->fresh()->report_kind);
        $change = SchoolReportKindChange::query()->where('school_id', $this->school->id)->sole();
        $this->assertSame((int) $this->head->id, (int) $change->user_id);
        $this->assertSame('progressive', $change->from_kind);
        $this->assertSame('set_based', $change->to_kind);

        $this->actingAs($this->head)
            ->get('/admin/reports/cards')
            ->assertOk()
            ->assertSee('changed Progressive report to Set-based reports', false);

        $this->actingAs($this->head)
            ->post('/admin/reports/cards/report-kind', ['report_kind' => 'set_based'])
            ->assertRedirect();

        $this->assertSame(1, SchoolReportKindChange::query()->where('school_id', $this->school->id)->count());
        $this->assertSame('progressive', $this->other->fresh()->report_kind);
        $this->assertSame(0, SchoolReportKindChange::query()->where('school_id', $this->other->id)->count());
    }

    public function test_a_teacher_cannot_change_the_report_type(): void
    {
        $this->actingAs($this->teacher)
            ->post('/admin/reports/cards/report-kind', ['report_kind' => 'set_based'])
            ->assertRedirect('/teacher/dashboard');

        $this->assertSame('progressive', $this->school->fresh()->report_kind);
        $this->assertSame(0, SchoolReportKindChange::count());
    }

    public function test_an_unknown_report_type_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/reports/cards')
            ->post('/admin/reports/cards/report-kind', ['report_kind' => 'kabale'])
            ->assertSessionHasErrors('report_kind');

        $this->assertSame('progressive', $this->school->fresh()->report_kind);
    }

    public function test_the_composition_columns_show_only_for_set_based_reports(): void
    {
        $this->school->forceFill([
            'report_composition' => ['1' => ['include' => false, 'weight' => 40]],
        ])->save();

        $this->actingAs($this->admin)
            ->get('/admin/settings/exam-types')
            ->assertOk()
            ->assertSee('data-testid="composition-hidden"', false)
            ->assertDontSee('data-testid="exam-type-weight-', false);

        $this->actingAs($this->admin)
            ->post('/admin/settings/exam-types', [
                'contributes' => [],
                'include' => ['1' => '1'],
                'weight' => ['1' => '90'],
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $this->assertSame(
            ['1' => ['include' => false, 'weight' => 40]],
            $this->school->fresh()->report_composition
        );

        $this->actingAs($this->admin)
            ->post('/admin/reports/cards/report-kind', ['report_kind' => 'set_based']);

        $this->actingAs($this->admin)
            ->get('/admin/settings/exam-types')
            ->assertOk()
            ->assertDontSee('data-testid="composition-hidden"', false)
            ->assertSee('Show on Report Cards')
            ->assertSee('Weight %');

        $this->actingAs($this->head)
            ->get('/admin/settings/exam-types')
            ->assertOk()
            ->assertSee('Weight %');
    }

    public function test_a_release_keeps_the_type_it_was_issued_under(): void
    {
        $this->school->forceFill(['report_kind' => 'set_based'])->save();

        $release = new MarksRelease([
            'school_id' => $this->school->id,
            'exam_id' => 1,
            'scope' => 'school',
            'scheduled_at' => now(),
            'status' => MarksRelease::STATUS_SCHEDULED,
        ]);
        $release->id = 1;
        $release->exists = false;
        $release->setRelation('school', $this->school->fresh());

        $setting = app(ReportKindSetting::class);
        $this->assertSame('set_based', $setting->forRelease(null, $this->school->fresh()));

        $released = new MarksRelease([
            'school_id' => $this->school->id,
            'status' => MarksRelease::STATUS_RELEASED,
            'report_kind' => 'progressive',
        ]);
        $released->setRelation('school', $this->school->fresh());

        $this->assertSame('progressive', $setting->forRelease($released, $this->school->fresh()));

        $historical = new MarksRelease([
            'school_id' => $this->school->id,
            'status' => MarksRelease::STATUS_RELEASED,
            'report_kind' => null,
        ]);
        $this->assertSame('progressive', $setting->forRelease($historical, $this->school->fresh()));
    }

    private function makeUser(School $school, int $usergroupId, string $name, string $prefix): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $prefix.'.'.uniqid().'@kind.test',
            'password' => Hash::make('password123'),
            'usergroup_id' => $usergroupId,
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $usergroupId,
            'status' => 'active',
        ]);

        return $user;
    }
}
