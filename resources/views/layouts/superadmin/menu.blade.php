{{-- SPDX-License-Identifier: MIT --}}
<ul class="list-reset text-sm">

    {{-- Dashboard --}}
    <li class="py-3 px-3 {{ Request::segment(2) == 'dashboard' ? 'active' : '' }}">
        <a href="{{ route('superadmin.dashboard') }}" class="flex items-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span class="mx-3 whitespace-no-wrap">Dashboard</span>
        </a>
    </li>

    {{-- Schools --}}
    <li class="relative py-3 px-3 {{ in_array(Request::segment(2), ['academics','schools']) ? 'active' : '' }}">
        <a href="#" class="flex items-center justify-between sidebar-menu-parent">
            <span class="flex items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                </svg>
                <span class="ml-3 whitespace-no-wrap">Schools</span>
            </span>
            <img src="{{ url('images/right-arrow.svg') }}" class="w-2 h-2 sidebar-menu-arrow">
        </a>
        <ul class="list-reset sites-sidebar" style="display:none;">
            <li class="py-2 px-3 {{ Request::segment(2) == 'academics' && !Request::segment(3) ? 'active' : '' }}">
                <a href="{{ route('superadmin.academics.schoollist') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-list mr-2"></i> All Schools
                </a>
            </li>
            <li class="py-2 px-3">
                <a href="{{ route('superadmin.academics.school.create') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-plus mr-2"></i> Add School
                </a>
            </li>
        </ul>
    </li>

    {{-- Users --}}
    <li class="py-3 px-3">
        <a href="{{ url('/superadmin/users') }}" class="flex items-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
            <span class="mx-3 whitespace-no-wrap">Users</span>
        </a>
    </li>

    {{-- Subscriptions --}}
    <li class="relative py-3 px-3 {{ in_array(Request::segment(2), ['reports']) && in_array(Request::segment(3), ['subscriptions','subscription']) ? 'active' : '' }}">
        <a href="#" class="flex items-center justify-between sidebar-menu-parent">
            <span class="flex items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
                <span class="ml-3 whitespace-no-wrap">Subscriptions</span>
            </span>
            <img src="{{ url('images/right-arrow.svg') }}" class="w-2 h-2 sidebar-menu-arrow">
        </a>
        <ul class="list-reset sites-sidebar" style="display:none;">
            <li class="py-2 px-3 {{ Request::segment(3) == 'subscriptions' ? 'active' : '' }}">
                <a href="{{ route('superadmin.reports.subscriptionlist') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-list mr-2"></i> All Subscriptions
                </a>
            </li>
            <li class="py-2 px-3">
                <a href="{{ route('superadmin.reports.subscription.create') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-plus mr-2"></i> Add Subscription
                </a>
            </li>
        </ul>
    </li>

    {{-- Reports --}}
    <li class="relative py-3 px-3 {{ in_array(Request::segment(3), ['contact']) ? 'active' : '' }}">
        <a href="#" class="flex items-center justify-between sidebar-menu-parent">
            <span class="flex items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>
                </svg>
                <span class="ml-3 whitespace-no-wrap">Reports</span>
            </span>
            <img src="{{ url('images/right-arrow.svg') }}" class="w-2 h-2 sidebar-menu-arrow">
        </a>
        <ul class="list-reset sites-sidebar" style="display:none;">
            <li class="py-2 px-3">
                <a href="{{ route('superadmin.reports.contactlist') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-address-book mr-2"></i> Contacts
                </a>
            </li>
        </ul>
    </li>

    {{-- Settings --}}
    <li class="relative py-3 px-3 {{ in_array(Request::segment(2), ['setting','cities','countries','states','plans']) ? 'active' : '' }}">
        <a href="#" class="flex items-center justify-between sidebar-menu-parent">
            <span class="flex items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="ml-3 whitespace-no-wrap">Settings</span>
            </span>
            <img src="{{ url('images/right-arrow.svg') }}" class="w-2 h-2 sidebar-menu-arrow">
        </a>
        <ul class="list-reset sites-sidebar" style="display:none;">
            <li class="py-2 px-3 {{ Request::segment(2) == 'cities' ? 'active' : '' }}">
                <a href="{{ route('superadmin.setting.cities') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-city mr-2"></i> Cities
                </a>
            </li>
            <li class="py-2 px-3 {{ Request::segment(2) == 'countries' ? 'active' : '' }}">
                <a href="{{ route('superadmin.setting.countries') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-globe mr-2"></i> Countries
                </a>
            </li>
            <li class="py-2 px-3 {{ Request::segment(2) == 'states' ? 'active' : '' }}">
                <a href="{{ route('superadmin.setting.states') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-map-pin mr-2"></i> States
                </a>
            </li>
            <li class="py-2 px-3 {{ Request::segment(2) == 'plans' ? 'active' : '' }}">
                <a href="{{ route('superadmin.setting.planlist') }}" class="flex items-center text-xs">
                    <i class="fa-solid fa-tags mr-2"></i> Plans
                </a>
            </li>
        </ul>
    </li>

</ul>

{{-- Sidebar accordion toggle (no jQuery dependency) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const parents = document.querySelectorAll('.sidebar-menu-parent');
    parents.forEach(function (parent) {
        parent.addEventListener('click', function (e) {
            e.preventDefault();
            const submenu = parent.nextElementSibling;
            const arrow = parent.querySelector('.sidebar-menu-arrow');
            if (submenu && submenu.classList.contains('sites-sidebar')) {
                submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
                if (arrow) {
                    arrow.style.transform = (submenu.style.display === 'block') ? 'rotate(90deg)' : 'rotate(0deg)';
                }
            }
        });
    });
    // Auto-open the active section
    const activeSection = document.querySelector('.sites-sidebar li.active');
    if (activeSection) {
        const submenu = activeSection.closest('.sites-sidebar');
        if (submenu) {
            submenu.style.display = 'block';
            const arrow = submenu.previousElementSibling ? submenu.previousElementSibling.querySelector('.sidebar-menu-arrow') : null;
            if (arrow) arrow.style.transform = 'rotate(90deg)';
        }
    }
});
</script>
