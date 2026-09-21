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
 *   icon       name for <x-icons.sidebar name="..."/>
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

    'roles' => [

        // ───────────────────────────── SchoolAdmin ─────────────────────────────
        'admin' => [
            'prefix' => 'admin',
            'layout' => 'grouped',
            'item_class' => 'py-3 px-3 dashboard-menu-item',
            'active_class' => 'active dashboard-active',
            // Bottom-of-sidebar link (was the tail of layouts/admin/menu.blade.php).
            'footer' => ['label' => 'Help & Docs', 'href' => 'https://docs.klassapp.com', 'external' => true],
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'admin/dashboard', 'active' => ['dashboard']],
            ],
            'groups' => [
                [
                    'key' => 'academics', 'label' => 'Academics', 'icon' => 'academics',
                    'items' => [
                        ['label' => 'Students', 'icon' => 'students', 'url' => 'admin/students', 'active' => ['students', 'student', 'parents', 'parent', 'teachers', 'teacher', 'staff', 'staffs', 'alumni', 'blocked_students']],
                        ['label' => 'Teachers', 'icon' => 'teachers', 'url' => 'admin/teachers', 'active' => ['teachers', 'teacher', 'staff', 'staffs']],
                        ['label' => 'Parents', 'icon' => 'parents', 'url' => 'admin/parents', 'active' => ['parents', 'parent']],
                        ['label' => 'Classes & Streams', 'icon' => 'classes', 'url' => 'admin/classes', 'active' => ['classes', 'sections', 'standardlinks', 'standardLink']],
                        ['label' => 'Subjects', 'icon' => 'subjects', 'url' => 'admin/subjects', 'active' => ['subjects', 'subject']],
                        ['label' => 'Timetable', 'icon' => 'timetable', 'url' => 'admin/timetable', 'active' => ['timetable', 'timetables']],
                        ['label' => 'Attendance', 'icon' => 'attendance', 'url' => 'admin/attendance', 'active' => ['attendance']],
                        ['label' => 'Exams & Marks', 'icon' => 'exams', 'url' => 'admin/exams', 'active' => ['exams', 'exam', 'marks', 'mark']],
                        ['label' => 'Grading', 'icon' => 'grading', 'url' => 'admin/grades', 'active' => ['grades', 'grade']],
                        ['label' => 'Report Cards', 'icon' => 'reports', 'url' => 'admin/reports/cards', 'active' => ['reports']],
                    ],
                ],
                [
                    'key' => 'operations', 'label' => 'Operations', 'icon' => 'operations',
                    'items' => [
                        ['label' => 'Library', 'icon' => 'library', 'route' => 'admin.library.books', 'active' => ['library', 'books']],
                        ['label' => 'Health', 'icon' => 'health', 'url' => 'admin/students', 'active' => ['health', 'medical']],
                        ['label' => 'Transport', 'icon' => 'transport', 'url' => 'admin/transport', 'active' => ['transport']],
                    ],
                ],
                [
                    'key' => 'finance', 'label' => 'Finance', 'icon' => 'finance',
                    'items' => [
                        ['label' => 'Fees & Payments', 'icon' => 'fees', 'url' => 'admin/fees/payments', 'active' => ['fees', 'fee', 'payments', 'payment', 'invoices']],
                        ['label' => 'Unmatched Payments', 'icon' => 'warning', 'url' => 'admin/fees/payments/unmatched', 'active' => ['unmatched'], 'class' => 'pl-6 py-2 px-3 dashboard-menu-item', 'small' => true],
                    ],
                ],
                [
                    'key' => 'communication', 'label' => 'Communication', 'icon' => 'communication',
                    'items' => [
                        ['label' => 'Messaging', 'icon' => 'messages', 'route' => 'admin.messages', 'active' => ['messages', 'messaging', 'notifications', 'sentmessages']],
                        ['label' => 'Calendar', 'icon' => 'calendar', 'url' => 'admin/calendar', 'active' => ['calendar', 'events']],
                    ],
                ],
                [
                    'key' => 'system', 'label' => 'System', 'icon' => 'system',
                    'items' => [
                        ['label' => 'Approvals', 'icon' => 'tasks', 'url' => 'admin/approvals', 'active' => ['approvals', 'approval']],
                        ['label' => 'Data Exports', 'icon' => 'reports', 'url' => 'admin/reports', 'active' => ['reports', 'report']],
                        ['label' => 'Settings', 'icon' => 'settings', 'url' => 'admin/settings', 'active' => ['settings']],
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
                ['label' => 'Plans', 'icon' => 'fees', 'route' => 'superadmin.setting.planlist', 'title' => 'Plans', 'paths' => ['superadmin/*/plans*', 'superadmin/*/plan*']],
                ['label' => 'Reports', 'icon' => 'reports', 'route' => 'superadmin.reports.index', 'title' => 'Reports', 'paths' => ['superadmin/reports*']],
                ['label' => 'Mail List', 'icon' => 'messages', 'url' => 'superadmin/mail-list', 'title' => 'Mail List', 'paths' => ['superadmin/*/mail-list*']],
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
                ['label' => 'Timetable', 'icon' => 'timetable', 'url' => 'teacher/dashboard', 'hash' => 'timetable', 'active' => ['timetable']],
                ['label' => 'Attendance', 'icon' => 'attendance', 'url' => 'teacher/dashboard', 'hash' => 'attendance', 'active' => ['attendance']],
                ['label' => 'Exams', 'icon' => 'exams', 'url' => 'teacher/exam/marks', 'active' => ['exams', 'exam']],
                ['label' => 'Homework', 'icon' => 'reports', 'url' => 'teacher/homeworks', 'active' => ['homework', 'homeworks']],
                ['label' => 'Marks', 'icon' => 'subjects', 'url' => 'teacher/exam/marks', 'active' => ['marks', 'mark']],
                ['label' => 'Report Cards', 'icon' => 'reports', 'route' => 'teacher.reports.cards.index', 'active' => ['reports'], 'condition' => 'class_teacher'],
                ['label' => 'Class Streams', 'icon' => 'classes', 'route' => 'teacher.class-stream.index', 'active' => ['class-streams'], 'condition' => 'class_teacher', 'a_class' => 'flex items-center', 'testid' => 'ct-streams-nav'],
                ['label' => 'Students', 'icon' => 'students', 'url' => 'teacher/classes', 'active' => ['students', 'student', 'classes']],
                ['label' => 'Notices', 'icon' => 'messages', 'url' => 'teacher/dashboard', 'hash' => 'notices', 'active' => ['notices', 'notice']],
                ['label' => 'Events', 'icon' => 'calendar', 'url' => 'teacher/events', 'active' => ['events']],
                ['label' => 'Library', 'icon' => 'library', 'url' => 'teacher/libraryactivity', 'active' => ['library', 'libraryactivity']],
            ],
        ],

        // ───────────────────────────── Student ─────────────────────────────
        'student' => [
            'prefix' => 'student',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 dashboard-menu-item',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'student/dashboard', 'active' => ['dashboard']],
                ['label' => 'Homework', 'icon' => 'reports', 'url' => 'student/homeworks', 'active' => ['homework', 'homeworks']],
                ['label' => 'Assignments', 'icon' => 'subjects', 'url' => 'student/assignments', 'active' => ['assignments', 'assignment']],
                ['label' => 'Events', 'icon' => 'calendar', 'url' => 'student/events', 'active' => ['events']],
                ['label' => 'Notices', 'icon' => 'messages', 'url' => 'student/notices', 'active' => ['notices', 'notice']],
                ['label' => 'Library', 'icon' => 'library', 'url' => 'student/libraryactivity', 'active' => ['libraryactivity', 'library']],
                ['label' => 'Holidays', 'icon' => 'calendar', 'url' => 'student/holidays', 'active' => ['holidays', 'holiday']],
                ['label' => 'Chats', 'icon' => 'messages', 'url' => 'student/conversations', 'active' => ['chats', 'chat', 'conversations']],
                ['label' => 'Activity', 'icon' => 'reports', 'url' => 'student/activity', 'active' => ['activity']],
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
            ],
        ],

        // ───────────────────────────── Librarian ─────────────────────────────
        'library' => [
            'prefix' => 'library',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 hover:font-semibold',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'library/dashboard', 'active' => ['dashboard']],
                ['label' => 'Books', 'icon' => 'library', 'url' => 'library/books/index', 'active' => ['books', 'book']],
                ['label' => 'Cards', 'icon' => 'students', 'route' => 'library.cards', 'active' => ['cards', 'card', 'members', 'member']],
                ['label' => 'Borrowing', 'icon' => 'reports', 'url' => 'library/booklending/index', 'active' => ['borrowing', 'borrow', 'returns', 'return']],
                ['label' => 'Data Exports', 'icon' => 'reports', 'url' => 'library/activity', 'active' => ['reports', 'report']],
            ],
        ],

        // ───────────────────────────── Receptionist ─────────────────────────────
        'reception' => [
            'prefix' => 'receptionist',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3 hover:bg-green-100',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'receptionist/dashboard', 'active' => ['dashboard']],
                ['label' => 'Visitors', 'icon' => 'reports', 'url' => 'receptionist/visitorlog', 'active' => ['visitorlog', 'visitors', 'visitor']],
                ['label' => 'Call Log', 'icon' => 'messages', 'url' => 'receptionist/calllog', 'active' => ['calllog', 'calls', 'call']],
                ['label' => 'Postal Record', 'icon' => 'messages', 'url' => 'receptionist/postalrecord', 'active' => ['postalrecord', 'postal']],
                ['label' => 'Notices', 'icon' => 'messages', 'url' => 'receptionist/notices', 'active' => ['notices', 'notice']],
                ['label' => 'Events', 'icon' => 'calendar', 'url' => 'receptionist/events', 'active' => ['events', 'event']],
                ['label' => 'Tasks', 'icon' => 'reports', 'url' => 'receptionist/tasks', 'active' => ['tasks', 'task']],
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
                ['label' => 'Data Exports', 'icon' => 'reports', 'route' => 'accountant.reports', 'active' => ['reports', 'report']],
                ['label' => 'Holidays', 'icon' => 'calendar', 'url' => 'accountant/holidays', 'active' => ['holidays', 'holiday']],
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
        'stock' => [
            'prefix' => 'stock',
            'layout' => 'flat',
            'item_class' => 'py-3 px-3',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => 'stock/dashboard', 'active' => ['dashboard']],
                ['label' => 'Products', 'icon' => 'reports', 'url' => 'stock/products', 'active' => ['products', 'product']],
                ['label' => 'Categories', 'icon' => 'subjects', 'url' => 'stock/categories', 'active' => ['categories', 'category']],
                ['label' => 'Suppliers', 'icon' => 'parents', 'url' => 'stock/suppliers', 'active' => ['suppliers', 'supplier']],
                ['label' => 'Orders', 'icon' => 'reports', 'url' => 'stock/orders', 'active' => ['orders', 'order']],
                ['label' => 'Data Exports', 'icon' => 'reports', 'url' => 'stock/reports', 'active' => ['reports', 'report']],
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
