<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Help home
    |--------------------------------------------------------------------------
    |
    | Pages with no matching guide open this path. It is the same destination
    | as the public /help redirect.
    |
    */

    'fallback' => '/docs-preview/help/',

    /*
    |--------------------------------------------------------------------------
    | Route name → guide
    |--------------------------------------------------------------------------
    |
    | Keys are route-name patterns (Illuminate\Support\Str::is). The first
    | match wins. Values are paths under /docs-preview/help/.
    |
    */

    'guides' => [
        'admin.students.index' => 'guides/student-filters',

        'admin.classes.show' => 'guides/class-pages',
        'admin.classes.index' => 'guides/class-pages',
        'teacher.classes.show' => 'guides/class-pages',
        'teacher.classes.manage' => 'guides/class-pages',
        'teacher.classes.index' => 'guides/class-pages',

        '*.exam-sets.*' => 'guides/exam-sets',

        'admin.teacher.show' => 'guides/resend-invite',

        'admin.attendance.*' => 'guides/attendance',
        'teacher.attendance.*' => 'guides/attendance',

        'admin.onboarding.*' => 'guides/school-checklist',
        'onboarding.choice' => 'guides/school-checklist',
        'onboarding.choice.store' => 'guides/school-checklist',

        'admin.marks' => 'guides/marks-workbook',
        'admin.marks.filter' => 'guides/marks-workbook',
        'admin.marks.classes' => 'guides/marks-workbook',
        'teacher.exam.marks' => 'guides/marks-workbook',
        'teacher.exam.marks.enter' => 'guides/marks-workbook',
        'teacher.exam.marks.view' => 'guides/marks-workbook',
        'teacher.exam.marks.save' => 'guides/marks-workbook',
        'teacher.exam.marks.template' => 'guides/marks-workbook',
        '*.marks.import.*' => 'guides/marks-workbook',
    ],

];
