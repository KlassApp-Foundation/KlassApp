<?php

namespace Tests\Feature\Navigation;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Most-specific-match: when the current path matches multiple nav items,
 * only the item with the longest matching pattern is highlighted.
 *
 * Before the fix, visiting /admin/marks/releases/* activated both
 * "Exams and marks" (admin/marks*) AND "Marks Release" (admin/marks/releases*)
 * simultaneously, producing double aria-current="page". The teacher sidebar had
 * an analogous problem on /teacher/exam/{id}/marks/enter pages.
 */
class SidebarActiveStateTest extends TestCase
{
    /**
     * Render a role's sidebar as if the browser is on $path.
     */
    private function renderSidebar(string $role, string $path): string
    {
        $fakeRequest = Request::create($path);
        app()->instance('request', $fakeRequest);

        return view('layouts.partials.sidebar-menu', ['role' => $role])->render();
    }

    // ── Admin sidebar (v3, aria-current="page") ───────────────────────────────

    public function test_admin_marks_page_activates_only_exams_and_marks(): void
    {
        $html = $this->renderSidebar('admin', '/admin/marks');

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertSame(
            1,
            substr_count($html, 'aria-current="page"'),
            'Expected exactly one aria-current on /admin/marks'
        );
        $this->assertMatchesRegularExpression(
            '/aria-current="page"[^>]*>[^<]*<[^>]*>Exams and marks/s',
            $html,
            '"Exams and marks" should be the active item on /admin/marks'
        );
    }

    public function test_admin_marks_releases_page_activates_only_marks_release(): void
    {
        $html = $this->renderSidebar('admin', '/admin/marks/releases/new');

        $this->assertSame(
            1,
            substr_count($html, 'aria-current="page"'),
            'Expected exactly one aria-current on /admin/marks/releases/new'
        );
        $this->assertMatchesRegularExpression(
            '/aria-current="page"[^>]*>[^<]*<[^>]*>Marks Release/s',
            $html,
            '"Marks Release" should be the active child on /admin/marks/releases/new'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/aria-current="page"[^>]*>[^<]*<[^>]*>Exams and marks/s',
            $html,
            '"Exams and marks" must NOT be active when visiting /admin/marks/releases/new'
        );
    }

    public function test_admin_marks_submissions_page_activates_only_mark_submissions(): void
    {
        $html = $this->renderSidebar('admin', '/admin/marks/submissions');

        $this->assertSame(
            1,
            substr_count($html, 'aria-current="page"'),
            'Expected exactly one aria-current on /admin/marks/submissions'
        );
        $this->assertMatchesRegularExpression(
            '/aria-current="page"[^>]*>[^<]*<[^>]*>Mark submissions/s',
            $html,
            '"Mark submissions" should be the active child on /admin/marks/submissions'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/aria-current="page"[^>]*>[^<]*<[^>]*>Exams and marks/s',
            $html,
            '"Exams and marks" must NOT be active when visiting /admin/marks/submissions'
        );
    }

    public function test_admin_reports_cards_page_activates_only_report_cards(): void
    {
        $html = $this->renderSidebar('admin', '/admin/reports/cards');

        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertMatchesRegularExpression(
            '/aria-current="page"[^>]*>[^<]*<[^>]*>Report cards/s',
            $html
        );
    }

    // ── Teacher sidebar (flat, active CSS class on <li>) ──────────────────────

    private function countActiveLiItems(string $html, string $role): int
    {
        $activeClass = config('navigation.roles.'.$role.'.active_class', 'active');
        $marker = explode(' ', trim($activeClass))[0];

        return preg_match_all('/<li\b[^>]*\b'.preg_quote($marker, '/').'\b/', $html);
    }

    public function test_teacher_marks_list_page_activates_only_marks(): void
    {
        $html = $this->renderSidebar('teacher', '/teacher/exam/marks');

        $count = $this->countActiveLiItems($html, 'teacher');
        $this->assertSame(1, $count, 'Expected exactly one active <li> on /teacher/exam/marks');

        $this->assertMatchesRegularExpression(
            '/<li\b[^>]*\bactive\b[^>]*>.*?>Marks<\/span>/s',
            $html,
            '"Marks" should be the active item on /teacher/exam/marks'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<li\b[^>]*\bactive\b[^>]*>.*?>Exams<\/span>/s',
            $html,
            '"Exams" must NOT be active on /teacher/exam/marks'
        );
    }

    public function test_teacher_marks_entry_page_activates_only_marks(): void
    {
        $html = $this->renderSidebar('teacher', '/teacher/exam/57/marks/enter');

        $count = $this->countActiveLiItems($html, 'teacher');
        $this->assertSame(1, $count, 'Expected exactly one active <li> on marks entry page');

        $this->assertMatchesRegularExpression(
            '/<li\b[^>]*\bactive\b[^>]*>.*?>Marks<\/span>/s',
            $html,
            '"Marks" should be the active item on the marks entry page'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<li\b[^>]*\bactive\b[^>]*>.*?>Exams<\/span>/s',
            $html,
            '"Exams" must NOT be active on the marks entry page'
        );
    }

    // ── Parent sidebar ────────────────────────────────────────────────────────

    public function test_parent_marks_page_activates_only_marks(): void
    {
        $html = $this->renderSidebar('parent', '/parent/children/46/grades');

        $count = $this->countActiveLiItems($html, 'parent');
        $this->assertSame(1, $count, 'Expected exactly one active <li> on parent marks page');
    }
}
