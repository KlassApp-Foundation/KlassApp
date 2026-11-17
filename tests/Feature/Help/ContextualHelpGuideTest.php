<?php

namespace Tests\Feature\Help;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContextualHelpGuideTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function guides(): array
    {
        $classPages = '/docs-preview/help/guides/class-pages';
        $marks = '/docs-preview/help/guides/marks-workbook';
        $attendance = '/docs-preview/help/guides/attendance';
        $checklist = '/docs-preview/help/guides/school-checklist';
        $examSets = '/docs-preview/help/guides/exam-sets';

        return [
            'students index' => ['admin.students.index', '/docs-preview/help/guides/student-filters'],
            'class page' => ['admin.classes.show', $classPages],
            'class roster' => ['admin.classes.index', $classPages],
            'teacher class page' => ['teacher.classes.show', $classPages],
            'teacher class roster page' => ['teacher.classes.manage', $classPages],
            'teacher class list' => ['teacher.classes.index', $classPages],
            'exam sets' => ['admin.exam-sets.index', $examSets],
            'exam set detail' => ['admin.exam-sets.show', $examSets],
            'teacher exam sets' => ['teacher.exam-sets.create', $examSets],
            'teacher profile' => ['admin.teacher.show', '/docs-preview/help/guides/resend-invite'],
            'attendance' => ['admin.attendance.index', $attendance],
            'attendance register' => ['admin.attendance.register', $attendance],
            'teacher attendance' => ['teacher.attendance.index', $attendance],
            'teacher attendance register' => ['teacher.attendance.register.save', $attendance],
            'onboarding wizard' => ['admin.onboarding.wizard', $checklist],
            'onboarding choice' => ['onboarding.choice', $checklist],
            'onboarding choice save' => ['onboarding.choice.store', $checklist],
            'marks overview' => ['admin.marks', $marks],
            'marks filter' => ['admin.marks.filter', $marks],
            'class marks' => ['admin.marks.classes', $marks],
            'teacher marks list' => ['teacher.exam.marks', $marks],
            'teacher marks entry' => ['teacher.exam.marks.enter', $marks],
            'teacher marks view' => ['teacher.exam.marks.view', $marks],
            'teacher marks save' => ['teacher.exam.marks.save', $marks],
            'teacher marks template' => ['teacher.exam.marks.template', $marks],
            'admin marks import' => ['admin.exams.marks.import.page', $marks],
            'teacher marks import' => ['teacher.exam.marks.import.confirm', $marks],
        ];
    }

    #[DataProvider('guides')]
    public function test_mapped_route_opens_its_guide(string $routeName, string $guide): void
    {
        $this->get('/help/guide?from='.urlencode($routeName))
            ->assertRedirect($guide);
    }

    public function test_an_unmapped_route_opens_the_help_home(): void
    {
        $this->get('/help/guide?from=admin.dashboard')
            ->assertRedirect('/docs-preview/help/');

        $this->get('/help/guide')
            ->assertRedirect('/docs-preview/help/');

        $this->get('/help/guide?from=admin.students.attendance.store')
            ->assertRedirect('/docs-preview/help/');

        $this->get('/help/guide?from=admin.marks.releases.index')
            ->assertRedirect('/docs-preview/help/');
    }

    public function test_students_index_and_teacher_profile_are_named(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('admin.students.index'));
        $this->assertNotNull($routes->getByName('admin.teacher.show'));
        $this->assertSame('help.guide', $routes->getByName('help.guide')?->getName());
    }
}
