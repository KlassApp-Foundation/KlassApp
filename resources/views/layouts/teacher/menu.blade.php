{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('tActive')){function tActive($p){ $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 dashboard-menu-item {{ tActive('dashboard') }}">
        <a href="{{ url('teacher/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-nowrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['classes','standardLinks','standardLink']) }}">
        <a href="{{ url('teacher/classes') }}" class="flex items-center whitespace-nowrap"><x-icons.sidebar name="classes"/><span class="mx-3 whitespace-nowrap">Classes</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['timetable']) }}">
        <a href="{{ url('teacher/dashboard') }}#timetable" class="flex items-center"><x-icons.sidebar name="timetable"/><span class="mx-3 whitespace-nowrap">Timetable</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['attendance']) }}">
        <a href="{{ url('teacher/dashboard') }}#attendance" class="flex items-center"><x-icons.sidebar name="attendance"/><span class="mx-3 whitespace-nowrap">Attendance</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['exams','exam']) }}">
        <a href="{{ url('teacher/exam/marks') }}" class="flex items-center"><x-icons.sidebar name="exams"/><span class="mx-3 whitespace-nowrap">Exams</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['homework','homeworks']) }}">
        <a href="{{ url('teacher/homeworks') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-nowrap">Homework</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['marks','mark']) }}">
        <a href="{{ url('teacher/exam/marks') }}" class="flex items-center"><x-icons.sidebar name="subjects"/><span class="mx-3 whitespace-nowrap">Marks</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['students','student','classes']) }}">
        <a href="{{ url('teacher/classes') }}" class="flex items-center"><x-icons.sidebar name="students"/><span class="mx-3 whitespace-nowrap">Students</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['notices','notice']) }}">
        <a href="{{ url('teacher/dashboard') }}#notices" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-nowrap">Notices</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['events']) }}">
        <a href="{{ url('teacher/events') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-nowrap">Events</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ tActive(['library','libraryactivity']) }}">
        <a href="{{ url('teacher/libraryactivity') }}" class="flex items-center"><x-icons.sidebar name="library"/><span class="mx-3 whitespace-nowrap">Library</span></a>
    </li>
</ul>
