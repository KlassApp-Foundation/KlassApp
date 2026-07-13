<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Toshi Skill Agent Registry
    |--------------------------------------------------------------------------
    |
    | Maps skill domains to their agent classes and trigger keywords.
    | Used by ToshiOrchestrator to route queries to the right skill.
    |
    | Each skill entry:
    |   class   - The Agent class implementing Agent & HasTools
    |   triggers - Keywords the orchestrator uses for fast-path routing
    |   confirm  - If true, all tools in this skill require confirmation by default
    |
    */

    'student' => [
        'class' => \App\AiAgents\Skills\StudentSkill::class,
        'description' => 'Manage students — add, find, count, record attendance, enter marks',
        'triggers' => ['student', 'learner', 'pupil', 'enroll', 'admission', 'attendance', 'mark', 'exam result'],
        'confirm' => false,
    ],

    'teacher' => [
        'class' => \App\AiAgents\Skills\TeacherSkill::class,
        'description' => 'Manage teachers — add, assign, promote to co-admin, list',
        'triggers' => ['teacher', 'staff', 'instructor', 'co-admin', 'assign teacher'],
        'confirm' => false,
    ],

    'academic' => [
        'class' => \App\AiAgents\Skills\AcademicSkill::class,
        'description' => 'Manage academic structure — classes, sections, terms, subjects, exams',
        'triggers' => ['class', 'section', 'term', 'subject', 'exam', 'academic', 'grade'],
        'confirm' => false,
    ],

    'fee' => [
        'class' => \App\AiAgents\Skills\FeeSkill::class,
        'description' => 'Manage fees — categories, payments, balances',
        'triggers' => ['fee', 'payment', 'pay', 'balance', 'invoice', 'due', 'school fees'],
        'confirm' => false,
    ],

    'grading' => [
        'class' => \App\AiAgents\Skills\GradingSkill::class,
        'description' => 'Manage grading scales — set, view, seed defaults',
        'triggers' => ['grade', 'grading', 'scale', 'marking', 'score interpretation'],
        'confirm' => false,
    ],

    'report' => [
        'class' => \App\AiAgents\Skills\ReportingSkill::class,
        'description' => 'Generate reports — academic, fee, attendance analytics',
        'triggers' => ['report', 'analytics', 'breakdown', 'summary', 'statistics', 'export'],
        'confirm' => false,
    ],
];
