{{-- SPDX-License-Identifier: MIT --}}
<nav class="navbar dashboard-themed-header w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center" style="background:#FAFAF5;border-bottom:1px solid #E2E8F0;">
    <div class="nav-brand flex items-center">
        @php
            // School identity: prefer the SCHOOL's own logo, fall back to the KlassApp mark.
            $navUser   = \Auth::user();
            $navSchool = $navUser ? $navUser->school : null;
            $logoDetail = $navSchool ? $navSchool->schoolDetailLogo : null;
            $schoolLogo = null;
            if ($logoDetail && ! in_array($logoDetail->meta_value, [null, '', '-'], true)) {
                try {
                    $schoolLogo = $logoDetail->getLogoPathAttribute() ?: null;
                } catch (\Throwable $e) {
                    $schoolLogo = null; // storage/url misconfig must never break the header
                }
            }
        @endphp
        @if(\Auth::user())
            <button type="button" id="sidebar-collapse-toggle"
                    class="mr-3 hidden md:inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:bg-black/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                    aria-label="Collapse sidebar" aria-expanded="true" aria-controls="admin-sidebar" title="Collapse sidebar">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="M15 9l-3 3 3 3"/></svg>
            </button>
            <button class="mr-3 lg:hidden" id="mobile-menu-trigger" aria-label="Toggle sidebar">
                <span class="navbar-toggler-icon">
                    <svg class="w-6 h-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path class="heroicon-ui" d="M4 5h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z" fill="currentColor"/></svg>
                </span>
            </button>
            <a class="h-10 object-contain" href="{{ route('dashboard') }}"
               aria-label="{{ $navSchool ? ucwords($navSchool->name).' dashboard' : 'Dashboard' }}">
                <img src="{{ $schoolLogo ?: asset('images/klassapp-logo-primary.svg') }}"
                     class="h-10 w-auto object-contain mr-3"
                     alt="{{ $schoolLogo ? ucwords($navSchool->name).' logo' : 'KlassApp' }}"
                     onerror="this.onerror=null;this.src='{{ asset('images/klassapp-logo-primary.svg') }}';">
            </a>
            <a class="text-lg lg:text-3xl font-semibold" href="{{ route('dashboard') }}" style="font-family: 'Sora', sans-serif; color: #2d2d2a;">
                <strong>{{ ucwords(Auth::user()->school->name) }}</strong>
            </a>
        @else
            @include('layouts.partials.logo')
        @endif
    </div>
    <div class="navbar-menu collapse navbar-collapse" id="navbarSupportedContent">
        <!-- Left Side Of Navbar -->
        <ul class="navbar-nav mr-auto flex">
        </ul>
    </div>

    <div class="flex flex-col-reverse lg:flex-row md:flex-row items-center">
        <!--academic year drop down-->
        <div class="hidden lg:block md:block">
            <nav-bar></nav-bar>
        </div>
        <!--academic year drop down-->
        <div class="flex items-center">
            <notification url="{{url('/')}}" mode="admin"></notification>
            <div class="navbar-menu">
                <ul class="navbar-nav ml-auto flex items-center">
                    @guest
                        <li class="nav-item px-2">
                            <a class="nav-link" href="{{ route('login') }}" id="login">{{ __('Login') }}</a>
                        </li>
                        <li class="nav-item px-2">
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                    @else
                        <!-- start -->
                        <li>
                            @include('layouts.partials.profile-dropdown')
                        <!-- end -->
                    @endguest
                </ul>
            </div>
        </div>
    </div>
</nav>
@push('scripts')
<script>
(function () {
    var KEY = 'admin_sidebar_collapsed';
    function btn() { return document.getElementById('sidebar-collapse-toggle'); }
    function apply(collapsed) {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        var el = btn();
        if (el) {
            el.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            el.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            el.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }
    }
    try { apply(localStorage.getItem(KEY) === '1'); } catch (e) {}
    // Delegate: the button lives inside the Vue-mounted #app, so a direct
    // listener is lost whenever Vue re-renders that subtree.
    document.addEventListener('click', function (e) {
        var el = e.target.closest && e.target.closest('#sidebar-collapse-toggle');
        if (!el) return;
        e.preventDefault();
        var collapsed = !document.body.classList.contains('sidebar-collapsed');
        apply(collapsed);
        try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e2) {}
    });
})();
</script>
@endpush
