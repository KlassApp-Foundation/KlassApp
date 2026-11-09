<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A class head opens every stream of the class. A stream section is its own
 * section ("Primary Four E"), not a row on the base. The register must not
 * open only the first stream.
 */
class WholeClassRegisterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Section $base;

    private StandardLink $east;

    private StandardLink $west;

    private User $head;

    private User $eastTeacher;

    private User $admin;

    /** @var list<int> */
    private array $eastIds = [];

    /** @var list<int> */
    private array $westIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Primary Four School',
            'slug' => 'p4-'.uniqid(),
            'email' => 'p4-'.uniqid().'@t.sch.ug',
            'phone' => '+256705'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 4, 'status' => 1,
        ]);

        $this->head = $this->user(5, 'Wilson Head');
        $this->eastTeacher = $this->user(5, 'East Teacher');
        $this->admin = $this->user(3, 'School Admin');

        $this->base = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Four',
            'status' => 1,
            'class_teacher_id' => $this->head->id,
        ]);
        $eastSection = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Four E', 'status' => 1,
        ]);
        $westSection = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Four W', 'status' => 1,
        ]);
        $this->link($standard, $this->base, $this->head->id);
        $this->east = $this->link($standard, $eastSection, $this->eastTeacher->id);
        $this->west = $this->link($standard, $westSection, null);

        $this->eastIds = $this->students($this->east, 'East', 60);
        $this->westIds = $this->students($this->west, 'West', 58);
    }

    public function test_opening_the_register_lists_every_stream_not_the_first_one(): void
    {
        $bare = $this->actingAs($this->head)->get(route('teacher.attendance.register'))->assertOk();
        $this->assertSame(118, substr_count($bare->getContent(), 'data-testid="attendance-row"'));
        $bare->assertSee('Primary Four');
        $bare->assertSee('Stream E', false);
        $bare->assertSee('Stream W', false);
        $bare->assertSee('value="">Whole class', false);
        $bare->assertSee('data-testid="mark-all-present"', false);
        $this->assertSame(118, substr_count($bare->getContent(), 'value="1" checked'));

        $explicit = $this->actingAs($this->head)
            ->get(route('teacher.attendance.register', ['section_id' => $this->base->id]))
            ->assertOk();
        $this->assertSame(118, substr_count($explicit->getContent(), 'data-testid="attendance-row"'));
    }

    public function test_the_stream_filter_loads_one_stream_and_saving_it_leaves_the_other_alone(): void
    {
        Attendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->west->id,
            'user_id' => $this->westIds[0],
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'status' => 0,
            'remarks' => 'West stays absent',
            'recorded_by' => $this->head->id,
        ]);
        Attendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->east->id,
            'user_id' => $this->eastIds[0],
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'status' => 0,
            'remarks' => 'East was absent',
            'recorded_by' => $this->head->id,
        ]);

        $east = $this->actingAs($this->head)->get(route('teacher.attendance.register', [
            'section_id' => $this->base->id,
            'standardLink_id' => $this->east->id,
        ]))->assertOk();
        $this->assertSame(60, substr_count($east->getContent(), 'data-testid="attendance-row"'));
        $east->assertDontSee('WEST 1');
        $east->assertSee('· one stream');

        $rows = [];
        foreach ($this->eastIds as $id) {
            $rows[$id] = ['status' => '1', 'comment' => ''];
        }
        $this->actingAs($this->head)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->base->id,
            'standardLink_id' => $this->east->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => $rows,
        ])->assertRedirect();

        $this->assertSame(1, (int) Attendance::query()->where('user_id', $this->eastIds[0])->value('status'));
        $this->assertSame(0, (int) Attendance::query()->where('user_id', $this->westIds[0])->value('status'));
        $this->assertSame('West stays absent', Attendance::query()->where('user_id', $this->westIds[0])->value('remarks'));
        $this->assertSame(1, Attendance::query()->where('standardLink_id', $this->west->id)->count());
        $this->assertSame(60, Attendance::query()->where('standardLink_id', $this->east->id)->count());
    }

    public function test_mark_all_present_is_a_page_control_and_a_full_save_clears_absences(): void
    {
        foreach (array_slice($this->eastIds, 0, 3) as $id) {
            Attendance::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'standardLink_id' => $this->east->id,
                'user_id' => $id,
                'date' => now()->toDateString(),
                'session' => 'forenoon',
                'status' => 0,
                'recorded_by' => $this->head->id,
            ]);
        }

        $page = $this->actingAs($this->head)
            ->get(route('teacher.attendance.register', ['section_id' => $this->base->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="mark-all-present"', $page);
        $this->assertStringContainsString("present.checked = true", $page);
        $this->assertStringNotContainsString('fetch(', substr($page, (int) strpos($page, 'mark-all-present')));

        $rows = [];
        foreach (array_merge($this->eastIds, $this->westIds) as $id) {
            $rows[$id] = ['status' => '1', 'comment' => ''];
        }
        $this->actingAs($this->head)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->base->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => $rows,
        ])->assertRedirect();

        $this->assertSame(0, Attendance::query()->where('school_id', $this->school->id)->where('status', 0)->count());
        $this->assertSame(118, Attendance::query()->where('school_id', $this->school->id)->where('status', 1)->count());
    }

    public function test_an_admin_sees_the_same_whole_class_and_a_stream_teacher_cannot_escalate(): void
    {
        $admin = $this->actingAs($this->admin)
            ->get(route('admin.attendance.register', ['section_id' => $this->base->id]))
            ->assertOk();
        $this->assertSame(118, substr_count($admin->getContent(), 'data-testid="attendance-row"'));

        $eastOnly = $this->actingAs($this->eastTeacher)
            ->get(route('teacher.attendance.register', ['section_id' => $this->base->id]))
            ->assertOk();
        $this->assertSame(60, substr_count($eastOnly->getContent(), 'data-testid="attendance-row"'));
        $eastOnly->assertDontSee('WEST 1');

        $this->actingAs($this->eastTeacher)
            ->get(route('teacher.attendance.register', ['standardLink_id' => $this->west->id]))
            ->assertForbidden();

        $this->actingAs($this->eastTeacher)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->base->id,
            'standardLink_id' => $this->west->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->westIds[0] => ['status' => '1', 'comment' => ''],
            ],
        ])->assertForbidden();
        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_another_school_cannot_open_the_class(): void
    {
        $other = School::create([
            'name' => 'Other Primary School',
            'slug' => 'p4-other-'.uniqid(),
            'email' => 'p4-other-'.uniqid().'@t.sch.ug',
            'phone' => '+256706'.random_int(100000, 999999),
            'status' => 1,
        ]);
        AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'status' => 1,
        ]);
        $admin = $this->user(3, 'Other Admin', $other);

        $this->actingAs($admin)
            ->get(route('admin.attendance.register', ['section_id' => $this->base->id]))
            ->assertForbidden();
        $this->actingAs($admin)
            ->get(route('admin.attendance.register', ['standardLink_id' => $this->east->id]))
            ->assertForbidden();
    }

    private function link(Standard $standard, Section $section, ?int $teacherId): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacherId,
            'status' => 1,
        ]);
    }

    /**
     * @return list<int>
     */
    private function students(StandardLink $link, string $side, int $count): array
    {
        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $user = User::create([
                'school_id' => $this->school->id,
                'usergroup_id' => 6,
                'name' => $side.' '.$i,
                'email' => strtolower($side).$i.'-'.uniqid().'@p4.test',
                'password' => bcrypt('secret'),
                'status' => 'active',
                'email_verified' => 1,
            ]);
            Userprofile::create([
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'usergroup_id' => 6,
                'firstname' => $side,
                'lastname' => (string) $i,
            ]);
            StudentAcademic::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'user_id' => $user->id,
                'standardLink_id' => $link->id,
            ]);
            $ids[] = (int) $user->id;
        }

        return $ids;
    }

    private function user(int $group, string $name, ?School $school = null): User
    {
        $school = $school ?: $this->school;

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@p4.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? 'Person',
        ]);

        return $user;
    }
}
