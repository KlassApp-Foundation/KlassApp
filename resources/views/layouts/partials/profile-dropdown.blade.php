{{-- SPDX-License-Identifier: MIT --}}
{{-- Shared profile dropdown — used by all role navigation files --}}
@php
    $prefix = Auth::user()->usergroup_id == 1 ? '/superadmin' : '/admin';
@endphp
<div class="profile-click" dusk="profile-menu">
    @if(Auth::user()->userprofile && Auth::user()->userprofile->avatar != null)
        <img src="{{ url(Auth::user()->userprofile->AvatarPath) }}" class="w-8 h-8 rounded-full cursor-pointer" style="border: 2px solid rgba(34,197,94,0.3);">
    @else
        <img src="{{ asset('uploads/user/avatar/default-user.jpg') }}" class="w-8 h-8 rounded-full cursor-pointer" style="border: 2px solid rgba(34,197,94,0.3);">
    @endif
    <div class="user-dtl">
        <ul class="list-reset">
            <li class="user-dtl-header">
                <a href="{{url($prefix . '/changeavatar')}}" style="display:flex;align-items:center;text-decoration:none;">
                    @if(Auth::user()->userprofile && Auth::user()->userprofile->avatar != null)
                        <img src="{{ url(Auth::user()->userprofile->AvatarPath) }}" class="user-avatar" style="cursor:pointer;">
                    @else
                        <img src="{{asset('uploads/user/avatar/default-user.jpg')}}" class="user-avatar" style="cursor:pointer;">
                    @endif
                </a>
                <div class="user-info">
                    <div class="user-name">
                        {{ Auth::user()->userprofile && Auth::user()->userprofile->firstname ? Auth::user()->FullName : Auth::user()->name }}
                    </div>
                    <div class="user-email">{{ Auth::user()->email }}</div>
                </div>
            </li>

            <li class="user-dtl-item">
                <a href="{{url($prefix . '/changepassword')}}" dusk="password-link">
                    <span class="dtl-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    Change Password
                </a>
            </li>

            <li class="user-dtl-item">
                <a href="{{url($prefix . '/editprofile')}}">
                    <span class="dtl-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    Edit Profile
                </a>
            </li>

            <li class="user-dtl-item">
                <a href="{{url($prefix . '/settings')}}">
                    <span class="dtl-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </span>
                    Settings
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
