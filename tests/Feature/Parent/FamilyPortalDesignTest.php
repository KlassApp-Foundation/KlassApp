<?php

namespace Tests\Feature\Parent;

use App\Models\Attendance;
use App\Models\User;
use App\Services\Parent\ParentPortalService;

class FamilyPortalDesignTest extends ParentStudentPortalFixesTest
{

    public function test_parent_and_student_pages_share_released_marks_and_hide_unreleased(): void
    {
        $parent = $this->actingAs($this->parentUser())
            ->get(route('parent.children.grades', ['student' => $this->studentUser()->id, 'year' => 'all']))
            ->assertOk();
        $student = $this->actingAs($this->studentUser())
            ->get(route('student.marks', ['year' => 'all']))
            ->assertOk();

        $parentReleased = $this->releasedPayload($parent->getContent());
        $studentReleased = $this->releasedPayload($student->getContent());
        $this->assertNotSame([], $parentReleased);
        $this->assertSame($parentReleased, $studentReleased);
        $parent->assertDontSee('Hidden Now');
        $parent->assertDontSee('Hidden Past');
        $student->assertDontSee('Hidden Now');
        $student->assertDontSee('Hidden Past');
    }

    public function test_attendance_counts_a_morning_and_afternoon_as_one_day(): void
    {
        $presentDate = now()->subDays(3)->toDateString();
        Attendance::create([
            'school_id' => $this->schoolId(),
            'academic_year_id' => $this->yearId(),
            'user_id' => $this->studentUser()->id,
            'date' => $presentDate,
            'session' => 'afternoon',
            'status' => 1,
            'recorded_by' => $this->teacherId(),
        ]);

        $summary = app(ParentPortalService::class)->attendanceSummary($this->studentUser(), $this->schoolId());
        $this->assertSame(1, $summary['days_present']);
        $this->assertSame(2, $summary['days_total']);
        $this->assertIsInt($summary['days_present']);

        $html = $this->actingAs($this->parentUser())
            ->get(route('parent.children.attendance', $this->studentUser()->id))
            ->assertOk()
            ->assertSee('data-testid="attendance-chart"', false)
            ->assertSee('1 of 2 days');

        $this->actingAs($this->studentUser())
            ->get(route('student.attendance'))
            ->assertOk()
            ->assertSee('1 of 2 days');
    }

    public function test_dashboard_cards_open_marks_attendance_and_fees_and_states_render(): void
    {
        $dashboard = $this->actingAs($this->parentUser())
            ->get(route('parent.dashboard', ['child' => $this->studentUser()->id]))
            ->assertOk();
        $dashboard->assertSee('data-testid="family-marks-card"', false);
        $dashboard->assertSee('data-testid="family-attendance-card"', false);
        $dashboard->assertSee('data-testid="family-fees-card"', false);
        $dashboard->assertSee('data-testid="dashboard-sidebar-footer"', false);

        $this->actingAs($this->parentUser())
            ->get(route('parent.children.grades', $this->studentUser()->id))
            ->assertOk()
            ->assertSee('data-testid="family-filters-open"', false);

        $this->actingAs($this->parentUser())
            ->get(route('parent.children.grades', ['student' => $this->studentUser()->id, 'state' => 'empty']))
            ->assertOk()
            ->assertSee('No results yet this term')
            ->assertDontSee('Hidden Now');

        $this->actingAs($this->parentUser())
            ->get(route('parent.dashboard', ['child' => $this->studentUser()->id, 'state' => 'loading']))
            ->assertOk()
            ->assertSee('data-testid="family-loading"', false)
            ->assertSee('Loading')
            ->assertDontSee('Hidden Now');

        $this->actingAs($this->parentUser())
            ->get(route('parent.children.fees', ['student' => $this->studentUser()->id, 'state' => 'error']))
            ->assertOk()
            ->assertSee('data-testid="family-error"', false)
            ->assertSee('Try again')
            ->assertDontSee('Hidden Now');

        $lonely = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'email' => 'lonely.design@test.sch.ug',
        ]);
        $this->actingAs($lonely)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('No child linked to your account yet');
    }

    public function test_report_download_refuses_when_nothing_is_released(): void
    {
        $other = User::factory()->create([
            'school_id' => $this->schoolId(),
            'usergroup_id' => 6,
            'status' => 'active',
            'name' => 'Unreleased Child',
        ]);

        $result = app(ParentPortalService::class)->releasedReportPdf($other, $this->schoolId());
        $this->assertFalse($result['ok']);
        $this->assertStringNotContainsString('Hidden', $result['message']);
        $this->assertArrayNotHasKey('pdf', $result);
    }

    private function releasedPayload(string $html): array
    {
        $this->assertSame(1, preg_match('/data-released="([^"]*)"/', $html, $match));

        return json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
    }

    private function parentUser(): User
    {
        return $this->parent;
    }

    private function studentUser(): User
    {
        return $this->student;
    }

    private function schoolId(): int
    {
        return (int) $this->school->id;
    }

    private function yearId(): int
    {
        return (int) $this->currentYear->id;
    }

    private function teacherId(): int
    {
        return (int) $this->teacher->id;
    }
}
