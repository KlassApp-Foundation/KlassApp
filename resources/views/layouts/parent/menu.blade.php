{{-- SPDX-License-Identifier: MIT --}}
@php if(!function_exists('pActive')){function pActive($p){ $s=Request()->segment(2); foreach((array)$p as $v) if($s===$v) return 'active'; return ''; }} @endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 dashboard-menu-item {{ pActive('dashboard') }}">
        <a href="{{ route('parent.dashboard') }}" class="flex items-center"><x-icons.sidebar name="dashboard"/><span class="mx-3 whitespace-nowrap">Dashboard</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item {{ pActive('children') }}">
        <a href="{{ route('parent.children') }}" class="flex items-center"><x-icons.sidebar name="students"/><span class="mx-3 whitespace-nowrap">Children</span></a>
    </li>
    <li class="py-3 px-3 dashboard-menu-item">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center w-full text-left" style="background:none;border:none;color:inherit;cursor:pointer;padding:0;">
                <x-icons.sidebar name="settings"/>
                <span class="mx-3 whitespace-nowrap">Logout</span>
            </button>
        </form>
    </li>
</ul>
