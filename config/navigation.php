<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Sidebar navigation AS DATA.
 *
 * One renderer (`resources/views/layouts/partials/sidebar-menu.blade.php`) draws
 * every role's sidebar from these arrays. This replaced the 10 duplicated
 * `resources/views/layouts/<role>/menu.blade.php` files and their per-role
 * copy-pasted `segment('2')` active-state helpers.
 *
 * Item keys:
 *   label      menu text (rendered verbatim, entity-decoded)
 *   icon       key into the `icons` map below (rendered by <x-icons.sidebar>, which draws
 *              the Lucide glyph through <x-icon>). Every key used here must exist in `icons`.
 *   route      named route  (route('name'))      } one of the two
 *   url        path         (url('path'))        }
 *   hash       appended anchor, e.g. '#timetable'
 *   active     list of URL second-segments that also highlight this item
 *              (transcribed from the retired per-role helper calls)
 *   paths      explicit URL patterns (request()->is()) — used where the old code
 *              matched on segment(3) etc. instead of a simple alias list
 *   class      extra <li> classes
 *   small      child-style item: text-xs anchor + plain <span>
 *   a_class    anchor class override
 *   title      anchor title attribute
 *   condition  'class_teacher' — only rendered for class teachers
 *   children   nested items (collapsible submenu)
 *   submenu    true → render `children` as the collapsible submenu pattern
 */
return [

    /*
     * Sidebar icon map: navigation `icon` key => Lucide icon name
     * (design/system handoff-2026-10-01-icons, table 1 and table 4).
     * Each item gets its own key where the old set shared one glyph, so the
     * same Lucide name may appear twice only when the meaning is the same
     * (e.g. Students and Children, Classes and Schools).
     */
    'icons' => [
        'dashboard' => 'house',
        'students' => 'graduation-cap',
        'teachers' => 'presentation',
        'parents' => 'users',
        'classes' => 'school',
        'subjects' => 'book',
        'attendance' => 'clipboard-check',
        'exams' => 'notebook-pen',
        'grading' => 'chart-no-axes-column',
        'fees' => 'banknote',
        'timetable' => 'calendar-clock',
        'reports' => 'file-text',
        'messages' => 'message-square',
        'settings' => 'settings',
        'help' => 'life-buoy',
        'library' => 'library',
        'transport' => 'bus',
        'health' => 'heart-pulse',
        'accountant' => 'hand-coins',
        'calendar' => 'calendar',
        'tasks' => 'list-checks',
        'warning' => 'triangle-alert',
        // Keys split out of shared glyphs (handoff table 4).
        'admissions' => 'user-plus',
        'notices' => 'megaphone',
        'homework' => 'notebook-text',
        'marks' => 'notebook-pen',
        'assignments' => 'clipboard-list',
        'holidays' => 'calendar-heart',
        'chats' => 'messages-square',
        'activity' => 'activity',
        'grades' => 'chart-no-axes-column',
        'books' => 'book-copy',
        'library-cards' => 'id-card',
        'borrowing' => 'arrow-left-right',
        'exports' => 'file-down',
        'visitors' => 'door-open',
        'calls' => 'phone',
        'postal' => 'mail',
        'tasks-list' => 'list-todo',
        'platform-reports' => 'chart-line',
        'mail-list' => 'mail',
        'plans' => 'layers',
    ],

    /*
     * Group-header icons (handoff table 2). Dashboard v2 drops these and makes
     * group labels text-only; until that lands they are drawn at 16px.
     */
    'group_icons' => [
        'academics' => 'graduation-cap',
        'people' => 'users',
        'money' => 'wallet',
        'messages' => 'messages-square',
        'school' => 'school',
    ],

    'roles' => [

        // ───────────────────────────── SchoolAdmin ─────────────────────────────
        // Groups per the confirmed 2026-10-08 list (handoff §Sidebar groups):
        // Dashboard; People; Academics; Money; Messages; School. Items the list
        // does not name stay reachable inside the closest group (Timetable,
        // Grading, Library, Admissions, Health records, Transport, Approvals,
        // Data Exports, Unmatched Payments, Calendar) — every pre-PR1 item is
        // still in this sidebar.
        'admin' => [
            'prefix' => 'admin',
            'layout' => 'grouped',
            'item_class' => 'py-3 px-3 dashboard-menu-item',
            'active_class' => 'active dashboard-active',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'admin/dashboard', 'active' => ['dashboard']],
            ],
            'groups' => [
                [
                    'key' => 'people', 'label' => 'People', 'icon' => 'people',
                    'items' => [
                        // Soft-launch 1d: only student-area segments — do not steal
                        // Teachers/Parents/Staff active state via shared aliases.
                        ['label' => 'Students', 'icon' => 'students', 'url' => 'admin/students', 'active' => ['students', 'student', 'alumni', 'blocked_students']],
                        ['label' => 'Teachers and staff', 'icon' => 'teachers', 'url' => 'admin/teachers', 'active' => ['teachers', 'teacher', 'staff', 'staffs']],
                        ['label' => 'Parents', 'icon' => 'parents', 'url' => 'admin/parents', 'active' => ['parents', 'parent']],
                        ['label' => 'Admissions', 'icon' => 'admissions', 'url' => 'admin/admissions', 'active' => ['admissions', 'admission', 'admissionlist']],
                    ],
                ],
                [
                    'key' => 'academics', 'label' => 'Academics', 'icon' => 'academics',
                    'items' => [
                        ['label' => 'Classes and streams', 'icon' => 'classes', 'url' => 'admin/classes', 'active' => ['classes', 'sections', 'standardlinks', 'standardLink'], 'condition' => 'school_admin'],
                        ['label' => 'Subjects', 'icon' => 'subjects', 'url' => 'admin/subjects', 'active' => ['subjects', 'subject']],
                        ['label' => 'Timetable', 'icon' => 'timetable', 'url' => 'admin/timetable', 'active' => ['timetable', 'timetables']],
                        ['label' => 'Attendance', 'icon' => 'attendance', 'url' => 'admin/attendance', 'active' => ['attendance']],
                        ['label' => 'Exams and marks', 'icon' => 'exams', 'url' => 'admin/exams', 'active' => ['exams', 'exam', 'marks', 'mark']],
                        ['label' => 'Grading', 'icon' => 'grading', 'url' => 'admin/grades', 'active' => ['grades', 'grade']],
                        ['label' => 'Report cards', 'icon' => 'reports', 'url' => 'admin/reports/cards', 'active' => ['reports']],
                        ['label' => 'Library', 'icon' => 'library', 'route' => 'admin.library.books', 'active' => ['library', 'books']],
                    ],
                ],
                [
                    'key' => 'money', 'label' => 'Money', 'icon' => 'money',
                    'items' => [
                        ['label' => 'Fees', 'icon' => 'fees', 'url' => 'admin/fees/payments', 'active' => ['fees', 'fee', 'payments', 'payment', 'invoices']],
                        ['label' => 'Unmatched Payments', 'icon' => 'warning', 'url' => 'admin/fees/payments/unmatched', 'active' => ['unmatched'], 'class' => 'pl-6 py-2 px-3 dashboard-menu-item', 'small' => true],
                    ],
                ],
                [
                    'key' => 'messages', 'label' => 'Messages', 'icon' => 'messages',
                    'items' => [
                        ['label' => 'WhatsApp', 'icon' => 'messages', 'url' => 'admin/whatsapp/dashboard', 'active' => ['whatsapp']],
                        ['label' => 'Calendar', 'icon' => 'calendar', 'url' => 'admin/calendar', 'active' => ['calendar', 'events']],
                    ],
                ],
                [
                    'key' => 'school', 'label' => 'School', 'icon' => 'school',
                    'items' => [
                        ['label' => 'Health records', 'icon' => 'health', 'url' => 'admin/health', 'active' => ['health', 'medical']],
                        ['label' => 'Transport', 'icon' => 'transport', 'url' => 'admin/transport', 'active' => ['transport']],
                        ['label' => 'Approvals', 'icon' => 'tasks', 'url' => 'admin/approvals', 'active' => ['approvals', 'approval']],
                        ['label' => 'Data Exports', 'icon' => 'exports', 'url' => 'admin/reports', 'active' => ['reports', 'report']],
                        ['label' => 'Settings', 'icon' => 'settings', 'url' => 'admin/settings', 'active' => ['settings'], 'condition' => 'school_admin'],
                        ['label' => 'Help', 'icon' => 'help', 'external' => 'https://klassapp.xyz/help'],
                    ],
                ],
            ],
        ],

        // ───────────────────────────── SiteAdmin ─────────────────────────────
        'superadmin' => [
            'prefix' => 'superadmin',
            'layout' => 'flat',
            'item_class' => 'dashboard-menu-item py-3 px-3',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'superadmin.dashboard', 'title' => 'Dashboard', 'paths' => ['superadmin/dashboard*']],
                ['label' => 'Schools', 'icon' => 'classes', 'url' => 'superadmin/academics/schools', 'title' => 'Schools', 'paths' => ['superadmin/academics*', 'superadmin/*/schools*', 'superadmin/*/school*']],
                ['label' => 'Subscriptions', 'icon' => 'fees', 'route' => 'superadmin.reports.subscriptionlist', 'title' => 'Subscriptions', 'paths' => ['superadmin/*/subscriptions*', 'superadmin/*/subscription*']],
                ['label' => 'Plans', 'icon' => 'plans', 'route' => 'superadmin.setting.planlist', 'title' => 'Plans', 'paths' => ['superadmin/*/plans*', 'superadmin/*/plan*']],
                ['label' => 'Reports', 'icon' => 'platform-reports', 'route' => 'superadmin.reports.index', 'title' => 'Reports', 'paths' => ['superadmin/reports*']],
                ['label' => 'Mail List', 'icon' => 'mail-list', 'url' => 'superadmin/mail-list', 'title' => 'Mail List', 'paths' => ['superadmin/*/mail-list*']],
                ['label' => 'Settings', 'icon' => 'settings', 'url' => 'superadmin/settings', 'title' => 'Settings', 'paths' => ['superadmin/settings*', 'superadmin/*/co-admins*', 'superadmin/*/cities*', 'superadmin/*/locations*', 'superadmin/*/features*', 'superadmin/*/emis*', 'superadmin/toshi*']],
            ],
        ],

        // ───────────────────────────── Teacher ─────────────────────────────
        'teacher' => [
            'prefix' => 'teacher',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 dashboard-menu-item',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'teacher/dashboard', 'active' => ['dashboard']],
                ['label' => 'Classes', 'icon' => 'classes', 'url' => 'teacher/classes', 'a_class' => 'flex items-center whitespace-nowrap', 'active' => ['classes', 'standardLinks', 'standardLink']],
                ['label' => 'Timetable', 'icon' => 'timetable', 'route' => 'teacher.timetable.index', 'active' => ['timetable']],
                ['label' => 'Attendance', 'icon' => 'attendance', 'route' => 'teacher.attendance.index', 'active' => ['attendance']],
                ['label' => 'Exams', 'icon' => 'exams', 'route' => 'teacher.exams.create', 'active' => ['exams', 'exam'], 'condition' => 'class_teacher'],
                ['label' => 'Homework', 'icon' => 'homework', 'url' => 'teacher/homeworks', 'active' => ['homework', 'homeworks']],
                ['label' => 'Marks', 'icon' => 'marks', 'url' => 'teacher/exam/marks', 'active' => ['marks', 'mark']],
                ['label' => 'Report Cards', 'icon' => 'reports', 'route' => 'teacher.reports.cards.index', 'active' => ['reports'], 'condition' => 'class_teacher'],
                ['label' => 'Class Streams', 'icon' => 'classes', 'route' => 'teacher.class-stream.index', 'active' => ['class-streams'], 'condition' => 'class_streams', 'a_class' => 'flex items-center', 'testid' => 'ct-streams-nav'],
                ['label' => 'Notices', 'icon' => 'notices', 'route' => 'teacher.notices.index', 'active' => ['notices', 'notice']],
                ['label' => 'Events', 'icon' => 'calendar', 'url' => 'teacher/events', 'active' => ['events']],
            ],
        ],

        // ───────────────────────────── Student ─────────────────────────────
        'student' => [
            'prefix' => 'student',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 dashboard-menu-item',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'student/dashboard', 'active' => ['dashboard']],
                ['label' => 'Marks', 'icon' => 'marks', 'url' => 'student/marks', 'active' => ['marks', 'mark']],
                ['label' => 'Attendance', 'icon' => 'attendance', 'url' => 'student/attendance', 'active' => ['attendance']],
                ['label' => 'Homework', 'icon' => 'homework', 'url' => 'student/homeworks', 'active' => ['homework', 'homeworks']],
                ['label' => 'Assignments', 'icon' => 'assignments', 'url' => 'student/assignments', 'active' => ['assignments', 'assignment']],
                ['label' => 'Events', 'icon' => 'calendar', 'url' => 'student/events', 'active' => ['events']],
                ['label' => 'Notices', 'icon' => 'notices', 'url' => 'student/notices', 'active' => ['notices', 'notice']],
                ['label' => 'Library', 'icon' => 'library', 'url' => 'student/libraryactivity', 'active' => ['libraryactivity', 'library']],
                ['label' => 'Holidays', 'icon' => 'holidays', 'url' => 'student/holidays', 'active' => ['holidays', 'holiday']],
                ['label' => 'Chats', 'icon' => 'chats', 'url' => 'student/conversations', 'active' => ['chats', 'chat', 'conversations']],
                ['label' => 'Activity', 'icon' => 'activity', 'url' => 'student/activity', 'active' => ['activity']],
            ],
        ],

        // ───────────────────────────── Parent ─────────────────────────────
        'parent' => [
            'prefix' => 'parent',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 dashboard-menu-item',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'parent.dashboard', 'active' => ['dashboard']],
                ['label' => 'Children', 'icon' => 'students', 'route' => 'parent.children', 'active' => ['children']],
                ['label' => 'Fees', 'icon' => 'fees', 'resolver' => 'parent_child', 'child_path' => 'fees', 'paths' => ['parent/*/fees']],
                ['label' => 'Grades', 'icon' => 'grades', 'resolver' => 'parent_child', 'child_path' => 'grades', 'paths' => ['parent/*/grades']],
                ['label' => 'Attendance', 'icon' => 'attendance', 'resolver' => 'parent_child', 'child_path' => 'attendance', 'paths' => ['parent/*/attendance']],
            ],
        ],

        // ───────────────────────────── Librarian ─────────────────────────────
        'library' => [
            'prefix' => 'library',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 hover:font-semibold',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'library/dashboard', 'active' => ['dashboard']],
                ['label' => 'Books', 'icon' => 'books', 'url' => 'library/books/index', 'active' => ['books', 'book']],
                ['label' => 'Cards', 'icon' => 'library-cards', 'route' => 'library.cards', 'active' => ['cards', 'card', 'members', 'member']],
                ['label' => 'Borrowing', 'icon' => 'borrowing', 'url' => 'library/booklending/index', 'active' => ['borrowing', 'borrow', 'returns', 'return']],
                ['label' => 'Data Exports', 'icon' => 'exports', 'url' => 'library/activity', 'active' => ['reports', 'report']],
            ],
        ],

        // ───────────────────────────── Receptionist ─────────────────────────────
        'reception' => [
            'prefix' => 'receptionist',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 hover:bg-green-100',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'receptionist/dashboard', 'active' => ['dashboard']],
                ['label' => 'Visitors', 'icon' => 'visitors', 'url' => 'receptionist/visitorlog', 'active' => ['visitorlog', 'visitors', 'visitor']],
                ['label' => 'Call Log', 'icon' => 'calls', 'url' => 'receptionist/calllog', 'active' => ['calllog', 'calls', 'call']],
                ['label' => 'Postal Record', 'icon' => 'postal', 'url' => 'receptionist/postalrecord', 'active' => ['postalrecord', 'postal']],
                ['label' => 'Notices', 'icon' => 'notices', 'url' => 'receptionist/notices', 'active' => ['notices', 'notice']],
                ['label' => 'Events', 'icon' => 'calendar', 'url' => 'receptionist/events', 'active' => ['events', 'event']],
                ['label' => 'Tasks', 'icon' => 'tasks-list', 'url' => 'receptionist/tasks', 'active' => ['tasks', 'task']],
            ],
        ],

        // ───────────────────────────── Accountant ─────────────────────────────
        'accountant' => [
            'prefix' => 'accountant',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 hover:font-semibold',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'accountant/dashboard', 'active' => ['dashboard']],
                ['label' => 'Fees & Payments', 'icon' => 'fees', 'route' => 'accountant.fee-payments', 'active' => ['fees', 'fee', 'payments', 'payment']],
                ['label' => 'Data Exports', 'icon' => 'exports', 'route' => 'accountant.reports', 'active' => ['reports', 'report']],
                ['label' => 'Holidays', 'icon' => 'holidays', 'url' => 'accountant/holidays', 'active' => ['holidays', 'holiday']],
                [
                    'label' => 'Payroll', 'icon' => 'accountant', 'submenu' => true, 'active' => ['payroll'],
                    'children' => [
                        ['label' => 'Templates', 'url' => 'accountant/payroll/template'],
                        ['label' => 'Salaries', 'url' => 'accountant/payroll/salary'],
                        ['label' => 'Payslips', 'url' => 'accountant/payroll/payslip'],
                        ['label' => 'Batch Run', 'url' => 'accountant/payroll/batch', 'active' => ['payroll.batch']],
                    ],
                ],
            ],
        ],

        // ───────────────────────────── Stock Keeper ─────────────────────────────
        // NOTE: the stock module has no routes yet (stock/* is empty), so this menu
        // is currently unreachable — kept data-identical, flagged in the audit.
        // Soft-launch 1f: stock module has no routes — hide Stock nav everywhere.
        'stock' => [
            'prefix' => 'stock',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3',
            'items' => [
                // intentionally empty
            ],
        ],

        // ───────────────────────────── Alumni ─────────────────────────────
        'alumni' => [
            'prefix' => 'alumni',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'alumni/dashboard', 'active' => ['dashboard']],
                ['label' => 'My Marks', 'icon' => 'exams', 'url' => 'alumni/marks', 'active' => ['marks', 'mark']],
                ['label' => 'Directory', 'icon' => 'students', 'url' => 'alumni/directory', 'active' => ['directory']],
            ],
        ],
    ],
];
