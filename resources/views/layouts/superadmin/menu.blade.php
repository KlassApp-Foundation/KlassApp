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

    {{-- Schools --}}
    <li class="py-3 px-3 {{ Request::segment(2) == 'academics' || Request::segment(3) == 'schools' || Request::segment(3) == 'school' ? 'active' : '' }}">
        <a href="{{ url('/superadmin/academics/schools') }}" class="flex items-center">
            <x-icons.sidebar name="classes"/>
            <span class="mx-3 whitespace-no-wrap">Schools</span>
        </a>
    </li>

    {{-- Subscriptions --}}
    <li class="py-3 px-3 {{ Request::segment(3) == 'subscriptions' || Request::segment(3) == 'subscription' ? 'active' : '' }}">
        <a href="{{ route('superadmin.reports.subscriptionlist') }}" class="flex items-center">
            <x-icons.sidebar name="fees"/>
            <span class="mx-3 whitespace-no-wrap">Subscriptions</span>
        </a>
    </li>

    {{-- Plans --}}
    <li class="py-3 px-3 {{ Request::segment(3) == 'plans' || Request::segment(3) == 'plan' ? 'active' : '' }}">
        <a href="{{ route('superadmin.setting.planlist') }}" class="flex items-center">
            <x-icons.sidebar name="fees"/>
            <span class="mx-3 whitespace-no-wrap">Plans</span>
        </a>
    </li>

    {{-- Reports --}}
    <li class="py-3 px-3 {{ Request::segment(3) == 'contact' || Request::segment(3) == 'contacts' ? 'active' : '' }}">
        <a href="{{ route('superadmin.reports.contactlist') }}" class="flex items-center">
            <x-icons.sidebar name="reports"/>
            <span class="mx-3 whitespace-no-wrap">Reports</span>
        </a>
    </li>

    {{-- Mail List --}}
    <li class="py-3 px-3 {{ Request::segment(3) == 'mail-list' ? 'active' : '' }}">
        <a href="{{ url('/superadmin/mail-list') }}" class="flex items-center">
            <x-icons.sidebar name="messages"/>
            <span class="mx-3 whitespace-no-wrap">Mail List</span>
        </a>
    </li>

    {{-- Settings --}}
    <li class="py-3 px-3 {{ superadminActive('settings') || Request::segment(3) == 'co-admins' || Request::segment(3) == 'cities' || Request::segment(3) == 'locations' || Request::segment(3) == 'features' || Request::segment(3) == 'emis' ? 'active' : '' }}">
        <a href="{{ url('/superadmin/settings') }}" class="flex items-center">
            <x-icons.sidebar name="settings"/>
            <span class="mx-3 whitespace-no-wrap">Settings</span>
        </a>
    </li>
</ul>
