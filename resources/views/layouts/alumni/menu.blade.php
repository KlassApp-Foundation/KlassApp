{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('alActive')){function alActive($p){ $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 {{ alActive('dashboard') }}">
        <a href="{{ url('alumni/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-no-wrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 {{ alActive(['marks','mark']) }}">
        <a href="{{ url('alumni/marks') }}" class="flex items-center"><x-icons.sidebar name="exams"/><span class="mx-3 whitespace-no-wrap">My Marks</span></a>
    </li>
    <li class="py-3 px-3 {{ alActive(['directory']) }}">
        <a href="{{ url('alumni/directory') }}" class="flex items-center"><x-icons.sidebar name="students"/><span class="mx-3 whitespace-no-wrap">Directory</span></a>
    </li>
</ul>
