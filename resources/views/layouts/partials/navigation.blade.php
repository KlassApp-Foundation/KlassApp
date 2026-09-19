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
