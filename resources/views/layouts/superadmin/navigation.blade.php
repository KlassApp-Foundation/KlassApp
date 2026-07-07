{{-- SPDX-License-Identifier: MIT --}}
<nav class="navbar dashboard-themed-header w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center" style="background-color: #FFFFFF; border-bottom: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
    <div class="nav-brand flex items-center">
        @if(\Auth::user())
            <button type="button" class="hidden lg:flex md:flex sidebar-toggle-btn mr-3" id="sidebar-toggle" title="Toggle sidebar" aria-label="Toggle sidebar" style="background: transparent; border: none; cursor: pointer; padding: 6px; border-radius: 6px; color: #0F172A;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="15" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <button class="block lg:hidden md:hidden mr-3" onclick="showsidebar('res_sidebar')" style="background: transparent; border: none; cursor: pointer; padding: 4px;">
                <span class="navbar-toggler-icon">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path class="heroicon-ui" d="M4 5h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z" fill="#0F172A"/></svg>
                </span>
            </button>

            <a class="h-10 object-contain" href="{{ route('superadmin.dashboard') }}">
                <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="h-10 w-auto object-contain mr-3" alt="KlassApp Logo">
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

        <div class="flex items-center">
            {{-- <notification url="{{url('/')}}" mode="admin"></notification> --}}
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
                        </li>
                        <!-- end -->
                    @endguest
                </ul>
            </div>
        </div>
    </div>
</nav>
