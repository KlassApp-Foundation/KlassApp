{{-- SPDX-License-Identifier: MIT --}}
<nav class="navbar dashboard-themed-header w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center" style="background:#FFFFFF;border-bottom:1px solid #E2E8F0;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
    <div class="nav-brand flex items-center">
        @if(\Auth::user())
            <button class="mr-3 lg:hidden" id="mobile-menu-trigger" aria-label="Toggle sidebar">
                <span class="navbar-toggler-icon">
                    <svg class="w-6 h-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path class="heroicon-ui" d="M4 5h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z" fill="currentColor"/></svg>
                </span>
            </button>
            <a class="h-10 object-contain" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="h-10 w-auto object-contain mr-3" alt="KlassApp Logo">
            </a>
            <a class="text-lg lg:text-3xl font-semibold text-gray-900" href="{{ route('dashboard') }}" style="font-family: 'Sora', sans-serif;">
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
                            <div class="profile-click user-dtl-dark" dusk="profile-menu">
                                @if(Auth::user()->userprofile && Auth::user()->userprofile->avatar != null)
                                    <img src="{{ url(Auth::user()->userprofile->AvatarPath) }}" class="w-8 h-8 rounded-full cursor-pointer" style="border: 2px solid rgba(255,255,255,0.1);">
                                @else 
                                    <img src="{{ asset('uploads/user/avatar/default-user.jpg') }}" class="w-8 h-8 rounded-full cursor-pointer" style="border: 2px solid rgba(255,255,255,0.1);">
                                @endif
                                <div class="user-dtl">
                                    <ul class="list-reset">
                                        <li class="user-dtl-header">
                                            @if(Auth::user()->userprofile && Auth::user()->userprofile->avatar != null)
                                                <img src="{{ url(Auth::user()->userprofile->AvatarPath) }}" class="user-avatar">
                                            @else
                                                <img src="{{asset('uploads/user/avatar/default-user.jpg')}}" class="user-avatar">
                                            @endif
                                            <div class="user-info">
                                                <div class="user-name">
                                                    {{ Auth::user()->userprofile && Auth::user()->userprofile->firstname ? Auth::user()->FullName : Auth::user()->name }}
                                                </div>
                                                <div class="user-email">{{ Auth::user()->email }}</div>
                                            </div>
                                        </li>

                                        <li class="user-dtl-item">
                                            <a href="{{url('/admin/changepassword')}}" dusk="password-link">
                                                <span class="dtl-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                </span>
                                                Change Password
                                            </a>
                                        </li>

                                        <li class="user-dtl-item">
                                            <a href="{{url('/admin/editprofile')}}">
                                                <span class="dtl-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                </span>
                                                Edit Profile
                                            </a>
                                        </li>

                                        <li class="user-dtl-item">
                                            <a href="{{url('/admin/changeavatar')}}">
                                                <span class="dtl-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                </span>
                                                Change Avatar
                                            </a>
                                        </li>

                                        @if(Auth::user()->isImpersonating())
                                        <li class="user-dtl-divider" role="separator"></li>
                                        <li class="user-dtl-item">
                                            <a href="{{url('/teacher/impersonate/stop')}}">
                                                <span class="dtl-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 8 21 12 17 16"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                                </span>
                                                Stop Impersonating
                                            </a>
                                        </li>
                                        @endif

                                        <li class="user-dtl-divider" role="separator"></li>

                                        <li class="user-dtl-item user-dtl-logout">
                                            <a dusk="logout-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                <span class="dtl-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                                </span>
                                                Logout
                                            </a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                                @csrf
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                        <!-- end -->
                    @endguest
                </ul>
            </div>
        </div>
    </div>
</nav>
