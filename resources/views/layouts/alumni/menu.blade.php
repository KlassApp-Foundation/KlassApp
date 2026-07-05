{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('alActive')){function alActive($p){ $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 {{ alActive('dashboard') }}">
        <a href="{{ url('alumni/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-no-wrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 {{ alActive(['members','member']) }}">
        <a href="{{ url('alumni/members') }}" class="flex items-center"><x-icons.sidebar name="students"/><span class="mx-3 whitespace-no-wrap">Members</span></a>
    </li>
    <li class="py-3 px-3 {{ alActive(['events','event']) }}">
        <a href="{{ url('alumni/events') }}" class="flex items-center"><x-icons.sidebar name="calendar"/><span class="mx-3 whitespace-no-wrap">Events</span></a>
    </li>
</ul>
