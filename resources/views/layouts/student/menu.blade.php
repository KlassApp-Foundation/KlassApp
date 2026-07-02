{{-- SPDX-License-Identifier: MIT --}}
@php function sActive($p) { $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; } @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive('dashboard') }}">
        <a href="{{ url('student/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-no-wrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['homework','homeworks']) }}">
        <a href="{{ url('student/homework') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Homework</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['assignments','assignment']) }}">
        <a href="{{ url('student/assignments') }}" class="flex items-center"><x-icons.sidebar name="subjects"/><span class="mx-3 whitespace-no-wrap">Assignments</span></a>
    </li>
    <li class="py-3 px-3 {{ sActive('quiz') }}">
        <a href="{{ url('student/quiz') }}" class="flex items-center"><x-icons.sidebar name="exams"/><span class="mx-3 whitespace-no-wrap">Quiz</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['timetable','timetables']) }}">
        <a href="{{ url('student/timetable') }}" class="flex items-center"><x-icons.sidebar name="timetable"/><span class="mx-3 whitespace-no-wrap">Timetable</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['events']) }}">
        <a href="{{ url('student/events') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-no-wrap">Events</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['notices','notice']) }}">
        <a href="{{ url('student/notices') }}" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-no-wrap">Notices</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['libraryactivity','library']) }}">
        <a href="{{ url('student/libraryactivity') }}" class="flex items-center"><x-icons.sidebar name="library"/><span class="mx-3 whitespace-no-wrap">Library</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['holidays','holiday']) }}">
        <a href="{{ url('student/holidays') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-no-wrap">Holidays</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive(['chats','chat']) }}">
        <a href="{{ url('student/chats') }}" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-no-wrap">Chats</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-teal-900 {{ sActive('activity') }}">
        <a href="{{ url('student/activity') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Activity</span></a>
    </li>
</ul>
