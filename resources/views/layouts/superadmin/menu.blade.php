{{-- SPDX-License-Identifier: MIT --}}
@php
if (!function_exists('superadminActive')) {
    function superadminActive($patterns): string {
        $segment = Request()->segment('2');
        foreach ((array)$patterns as $p) {
            if ($segment === $p) return 'active';
        }
        return '';
    }
}
@endphp
<ul class="list-reset text-sm">
    <li class="py-3 px-3 {{ superadminActive('dashboard') }}">
        <a href="{{ route('superadmin.dashboard') }}" class="flex items-center">
            <x-icons.sidebar name="dashboard"/>
            <span class="mx-3 whitespace-no-wrap">Dashboard</span>
        </a>
    </li>

    {{-- Schools with submenu --}}
    <li class="relative py-3 px-3 {{ superadminActive(['academics','schools']) }}">
        <a href="#" class="flex items-center justify-between sidebar-menu-parent">
            <span class="flex items-center">
                <x-icons.sidebar name="classes"/>
                <span class="mx-3 whitespace-no-wrap">Schools</span>
            </span>
            <svg class="w-4 h-4 sidebar-menu-arrow {{ superadminActive(['academics','schools']) ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </a>
        <ul class="sites-sidebar pl-4 mt-1 space-y-1" style="display: {{ superadminActive(['academics','schools']) ? 'block' : 'none' }}">
            <li class="py-2 px-2 {{ Request::segment(3) == 'schools' ? 'active' : '' }}">
                <a href="{{ url('/superadmin/academics/schools') }}" class="flex items-center text-sm">
                    <span class="mx-2">All Schools</span>
                </a>
            </li>
        </ul>
    </li>

    {{-- Subscriptions --}}
    <li class="py-3 px-3 {{ superadminActive('subscriptions') }}">
        <a href="{{ route('superadmin.reports.subscriptionlist') }}" class="flex items-center">
            <x-icons.sidebar name="fees"/>
            <span class="mx-3 whitespace-no-wrap">Subscriptions</span>
        </a>
    </li>

    {{-- Plans --}}
    <li class="py-3 px-3 {{ superadminActive('plans') }}">
        <a href="{{ route('superadmin.setting.planlist') }}" class="flex items-center">
            <x-icons.sidebar name="fees"/>
            <span class="mx-3 whitespace-no-wrap">Plans</span>
        </a>
    </li>

    {{-- Reports --}}
    <li class="py-3 px-3 {{ superadminActive('reports') }}">
        <a href="{{ route('superadmin.reports.contactlist') }}" class="flex items-center">
            <x-icons.sidebar name="reports"/>
            <span class="mx-3 whitespace-no-wrap">Reports</span>
        </a>
    </li>

    {{-- Settings --}}
    <li class="py-3 px-3 {{ superadminActive('settings') }}">
        <a href="{{ route('superadmin.setting.cities') }}" class="flex items-center">
            <x-icons.sidebar name="settings"/>
            <span class="mx-3 whitespace-no-wrap">Settings</span>
        </a>
    </li>
</ul>
