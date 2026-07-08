{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('lActive')){function lActive($p){ $s=Request()->segment('2'); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 hover:font-semibold {{ lActive('dashboard') }}">
        <a href="{{ url('library/dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-no-wrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ lActive(['books','book']) }}">
        <a href="{{ url('library/books') }}" class="flex items-center"><x-icons.sidebar name="library"/><span class="mx-3 whitespace-no-wrap">Books</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ lActive(['members','member']) }}">
        <a href="{{ url('library/members') }}" class="flex items-center"><x-icons.sidebar name="students"/><span class="mx-3 whitespace-no-wrap">Members</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ lActive(['borrowing','borrow','returns','return']) }}">
        <a href="{{ url('library/borrowing') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Borrowing</span></a>
    </li>
    <li class="py-3 px-3 hover:font-semibold {{ lActive(['reports','report']) }}">
        <a href="{{ url('library/reports') }}" class="flex items-center"><x-icons.sidebar name="reports"/><span class="mx-3 whitespace-no-wrap">Data Exports</span></a>
    </li>
</ul>
