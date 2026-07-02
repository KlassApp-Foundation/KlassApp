{{-- SPDX-License-Identifier: MIT --}}
@php
// Helper: check if current route segment matches any of the given patterns
if (!function_exists('sidebarActive')) {
    function sidebarActive($patterns): string {
        $segment = Request()->segment('2');
        foreach ((array)$patterns as $p) {
            if ($segment === $p) return 'active dashboard-active';
        }
        return '';
    }
}
@endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive('dashboard') }}">
        <a href="{{ url('admin/dashboard') }}" class="flex items-center">
            <x-icons.sidebar name="dashboard"/>
            <span class="mx-3 whitespace-no-wrap">Dashboard</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['students','student','parents','parent','teachers','teacher','staff','staffs','alumni','blocked_students']) }}">
        <a href="{{ url('admin/students') }}" class="flex items-center">
            <x-icons.sidebar name="students"/>
            <span class="mx-3 whitespace-no-wrap">Students</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['parents','parent']) }}">
        <a href="{{ url('admin/parents') }}" class="flex items-center">
            <x-icons.sidebar name="students"/>
            <span class="mx-3 whitespace-no-wrap">Parents</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['classes','sections','standardlinks','standardLink']) }}">
        <a href="{{ url('admin/classes') }}" class="flex items-center">
            <x-icons.sidebar name="classes"/>
            <span class="mx-3 whitespace-no-wrap">Classes &amp; Streams</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['subjects','subject']) }}">
        <a href="{{ url('admin/subjects') }}" class="flex items-center">
            <x-icons.sidebar name="subjects"/>
            <span class="mx-3 whitespace-no-wrap">Subjects</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['timetable','timetables']) }}">
        <a href="{{ url('admin/timetable') }}" class="flex items-center">
            <x-icons.sidebar name="timetable"/>
            <span class="mx-3 whitespace-no-wrap">Timetable</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['attendance']) }}">
        <a href="{{ url('admin/attendance') }}" class="flex items-center">
            <x-icons.sidebar name="attendance"/>
            <span class="mx-3 whitespace-no-wrap">Attendance</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['exams','exam','marks','mark']) }}">
        <a href="{{ url('admin/exams') }}" class="flex items-center">
            <x-icons.sidebar name="exams"/>
            <span class="mx-3 whitespace-no-wrap">Exams &amp; Marks</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['fees','fee','payments','payment','invoices']) }}">
        <a href="{{ url('admin/fees/payments') }}" class="flex items-center">
            <x-icons.sidebar name="fees"/>
            <span class="mx-3 whitespace-no-wrap">Fees &amp; Payments</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['reports','report']) }}">
        <a href="{{ url('admin/reports') }}" class="flex items-center">
            <x-icons.sidebar name="reports"/>
            <span class="mx-3 whitespace-no-wrap">Reports</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['messages','messaging','notifications']) }}">
        <a href="{{ url('admin/messages') }}" class="flex items-center">
            <x-icons.sidebar name="messages"/>
            <span class="mx-3 whitespace-no-wrap">Messaging</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['library','books']) }}">
        <a href="{{ url('admin/library') }}" class="flex items-center">
            <x-icons.sidebar name="library"/>
            <span class="mx-3 whitespace-no-wrap">Library</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['health','medical']) }}">
        <a href="{{ url('admin/health') }}" class="flex items-center">
            <x-icons.sidebar name="health"/>
            <span class="mx-3 whitespace-no-wrap">Health</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['transport']) }}">
        <a href="{{ url('admin/transport') }}" class="flex items-center">
            <x-icons.sidebar name="transport"/>
            <span class="mx-3 whitespace-no-wrap">Transport</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['calendar','events']) }}">
        <a href="{{ url('admin/calendar') }}" class="flex items-center">
            <x-icons.sidebar name="calendar"/>
            <span class="mx-3 whitespace-no-wrap">Calendar</span>
        </a>
    </li>

    <li class="py-3 px-3 dashboard-menu-item {{ sidebarActive(['settings']) }}">
        <a href="{{ url('admin/settings') }}" class="flex items-center">
            <x-icons.sidebar name="settings"/>
            <span class="mx-3 whitespace-no-wrap">Settings</span>
        </a>
    </li>

    {{-- TODO: Alumni — route and controller not built yet --}}
    {{-- TODO: Certificates — route and controller not built yet --}}
</ul>
