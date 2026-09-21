{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('rActive')){function rActive($p){ $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive('dashboard') }}">
        <a href="{{ url('receptionist/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-nowrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['visitorlog','visitors','visitor']) }}">
        <a href="{{ url('receptionist/visitorlog') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-nowrap">Visitors</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['calllog','calls','call']) }}">
        <a href="{{ url('receptionist/calllog') }}" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-nowrap">Call Log</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['postalrecord','postal']) }}">
        <a href="{{ url('receptionist/postalrecord') }}" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-nowrap">Postal Record</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['notices','notice']) }}">
        <a href="{{ url('receptionist/notices') }}" class="flex items-center"><x-icons.sidebar name="messages"/><span class="mx-3 whitespace-nowrap">Notices</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['events','event']) }}">
        <a href="{{ url('receptionist/events') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-nowrap">Events</span></a>
    </li>
    <li class="py-3 px-3 hover:bg-green-100 {{ rActive(['tasks','task']) }}">
        <a href="{{ url('receptionist/tasks') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-nowrap">Tasks</span></a>
    </li>
</ul>
