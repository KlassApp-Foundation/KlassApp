<?php

namespace Tests\Feature\Parent;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Parent\ParentPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T-095: the parent's weekly attendance summary follows the existing design:
 * days present per week out of five, and every missed day with its comment.
 */
class ParentAttendanceWeeksTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $parent;

    private User $child;

    private StandardLink $stream;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Weekly Attendance School',
            'slug' => 'weekly-attendance',
            'email' => 'weekly@attendance.test',
            'phone' => '+256700700001',
            'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
        ]);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 3',
            'starts_on' => now()->startOfWeek()->subWeeks(2)->toDateString(),
            'ends_on' => now()->startOfWeek()->addWeeks(8)->toDateString(),
            'status' => 'current',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $this->stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => 'E',
            'status' => 1,
        ]);

        $this->parent = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Weekly Parent',
        ]);

        $this->child = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'name' => 'Amara Okoro',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->child->id,
            'usergroup_id' => 6,
            'firstname' => 'Amara',
            'lastname' => 'Okoro',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->child->id,
            'standardLink_id' => $this->stream->id,
        ]);
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $this->child->id,
            'status' => 1,
        ]);
    }

    public function test_weekly_summary_counts_distinct_present_days_and_lists_missed_days(): void
    {
        $weekOne = now()->startOfWeek()->subWeeks(2);
        $weekTwo = now()->startOfWeek()->subWeek();

        // Week one: four present, Wednesday absent with a comment.
        foreach ([0, 1, 3, 4] as $day) {
            $this->attendance($weekOne->copy()->addDays($day), 1);
        }
        $this->attendance($weekOne->copy()->addDays(2), 0, 'Hospital visit, note from parent.');

        // Week two: a full five days.
        foreach ([0, 1, 2, 3, 4] as $day) {
            $this->attendance($weekTwo->copy()->addDays($day), 1);
        }

        // Current week: record only weekdays that have already happened.
        $today = now()->startOfDay();
        $thisWeekPresent = 0;
        foreach ([0, 1, 2, 3, 4] as $day) {
            $date = now()->startOfWeek()->addDays($day);
            if ($date->gt($today)) {
                continue;
            }
            $this->attendance($date, 1);
            $thisWeekPresent++;
        }

        $payload = app(ParentPortalService::class)->attendance($this->parent, null, $this->child->id);

        $this->assertTrue($payload['success']);
        $weeks = collect($payload['data']['weeks']);

        $this->assertGreaterThanOrEqual(3, $weeks->count());
        $this->assertSame(4, (int) $weeks->firstWhere('label', 'W1')['present']);
        $this->assertSame(5, (int) $weeks->firstWhere('label', 'W1')['recorded']);
        $this->assertSame(5, (int) $weeks->firstWhere('label', 'W2')['present']);
        $this->assertSame($thisWeekPresent, (int) $payload['data']['this_week']['present']);
        $this->assertSame($thisWeekPresent, (int) $payload['data']['this_week']['recorded']);

        $missed = collect($payload['data']['missed_days']);
        $this->assertCount(1, $missed);
        $this->assertSame('Hospital visit, note from parent.', $missed->first()['comment']);
        $this->assertSame($weekOne->copy()->addDays(2)->format('D j M'), $missed->first()['date']);
    }

    public function test_an_empty_week_is_marked_unrecorded_not_five_absences(): void
    {
        // Only the current week has a register; earlier term weeks are empty.
        $this->attendance(now()->startOfWeek(), 1);

        $payload = app(ParentPortalService::class)->attendance($this->parent, null, $this->child->id);
        $weeks = collect($payload['data']['weeks']);

        $this->assertTrue($weeks->count() >= 3);
        foreach ($weeks as $week) {
            if ((int) $week['recorded'] === 0) {
                $this->assertSame(0, (int) $week['present']);
            }
        }
    }

    public function test_parent_attendance_page_shows_the_weekly_design_and_comments(): void
    {
        $weekOne = now()->startOfWeek()->subWeeks(2);
        $this->attendance($weekOne, 1);
        $this->attendance($weekOne->copy()->addDay(), 0, 'Travelled for a family event.');

        $this->actingAs($this->parent)
            ->get(route('parent.children.attendance', $this->child->id))
            ->assertOk()
            ->assertSee('data-testid="parent-attendance-week"', false)
            ->assertSee('data-testid="parent-attendance-missed-day"', false)
            ->assertSee('Weeks this term')
            ->assertSee('Days missed')
            ->assertSee('W1')
            ->assertSee('Travelled for a family event.');
    }

    private function attendance(\Carbon\Carbon $date, int $status, ?string $comment = null): void
    {
        Attendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->stream->id,
            'user_id' => $this->child->id,
            'date' => $date->toDateString(),
            'session' => 'forenoon',
            'status' => $status,
            'remarks' => $comment,
            'recorded_by' => $this->parent->id,
        ]);
    }
}
