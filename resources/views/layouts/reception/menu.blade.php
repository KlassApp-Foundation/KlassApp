{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('rActive')){function rActive($p){ $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive('dashboard') }}">
        <a href="{{ url('reception/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-nowrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['students','student']) }}">
        <a href="{{ url('reception/students') }}" class="flex items-center"><x-icons.sidebar name="students"/><span class="mx-3 whitespace-nowrap">Students</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['parents','parent']) }}">
        <a href="{{ url('reception/parents') }}" class="flex items-center"><x-icons.sidebar name="parents"/><span class="mx-3 whitespace-nowrap">Parents</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['visitors','visitor']) }}">
        <a href="{{ url('reception/visitors') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-nowrap">Visitors</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['appointments','appointment']) }}">
        <a href="{{ url('reception/appointments') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-nowrap">Appointments</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['messages','messaging']) }}">
        <a href="{{ url('reception/messages') }}" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-nowrap">Messages</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive('calls') }}">
        <a href="{{ url('reception/calls') }}" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-nowrap">Call Log</span></a>
    </li>
</ul>
