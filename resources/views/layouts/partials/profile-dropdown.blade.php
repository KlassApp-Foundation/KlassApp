{{-- SPDX-License-Identifier: MIT --}}
{{-- Soft-launch Part A account card — sidebar footer + mobile drawer --}}
@php
    $portalLinks = \App\Support\PortalProfileLinks::forUser(Auth::user());
    $profile = Auth::user()->userprofile;
    $first = is_string($profile?->firstname) ? trim($profile->firstname) : '';
    $last = is_string($profile?->lastname) ? trim($profile->lastname) : '';
    $displayName = trim($first.' '.$last);
    if ($displayName === '') {
        $displayName = (string) Auth::user()->name;
    }
    $email = (string) Auth::user()->email;
    $menuId = 'account-menu-'.Auth::id();
@endphp
<div class="profile-click account-card" dusk="profile-menu" data-account-card>
    <button
        type="button"
        class="account-card__trigger"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="{{ $menuId }}"
        data-account-trigger
    >
        <x-profile-photo :user="Auth::user()" size="sm" class="account-card__avatar" />
        <span class="account-card__identity">
            <span class="account-card__name" title="{{ $displayName }}">{{ $displayName }}</span>
            <span class="account-card__email" title="{{ $email }}">{{ $email }}</span>
        </span>
        <svg class="account-card__chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
    </button>

    <div
        class="user-dtl account-card__menu"
        id="{{ $menuId }}"
        role="menu"
        aria-label="Account"
        hidden
        data-account-menu
    >
        <div class="account-card__who">
            @if($portalLinks['change_avatar'])
                <a href="{{ url($portalLinks['change_avatar']) }}" class="account-card__who-photo" tabindex="-1">
                    <x-profile-photo :user="Auth::user()" size="sm" />
                </a>
            @else
                <span class="account-card__who-photo">
                    <x-profile-photo :user="Auth::user()" size="sm" />
                </span>
            @endif
            <span class="account-card__identity">
                <span class="account-card__name" title="{{ $displayName }}">{{ $displayName }}</span>
                <span class="account-card__email" title="{{ $email }}">{{ $email }}</span>
            </span>
        </div>

        <ul class="account-card__list list-reset">
            @if($portalLinks['change_password'])
            <li role="none">
                <a role="menuitem" href="{{ url($portalLinks['change_password']) }}" dusk="password-link" class="account-card__item">
                    <span class="dtl-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    Change password
                </a>
            </li>
            @endif

            @if($portalLinks['edit_profile'])
            <li role="none">
                <a role="menuitem" href="{{ url($portalLinks['edit_profile']) }}" dusk="edit-profile-link" class="account-card__item">
                    <span class="dtl-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    Edit profile
                </a>
            </li>
            @endif

            @if($portalLinks['settings'])
            <li role="none">
                <a role="menuitem" href="{{ url($portalLinks['settings']) }}" dusk="settings-link" class="account-card__item">
                    <span class="dtl-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.68 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </span>
                    Settings
                </a>
            </li>
            @endif

            @if(Auth::user()->isImpersonating())
            <li class="account-card__sep" role="separator"></li>
            <li role="none">
                <a role="menuitem" href="{{ url('/teacher/impersonate/stop') }}" class="account-card__item">
                    <span class="dtl-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 8 21 12 17 16"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </span>
                    Stop impersonating
                </a>
            </li>
            @endif

            <li class="account-card__sep" role="separator"></li>

            <li role="none" class="user-dtl-logout">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="account-card__logout-form">
                    @csrf
                    <button type="submit" role="menuitem" dusk="logout-link" class="account-card__item account-card__item--danger">
                        <span class="dtl-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </span>
                        Log out
                    </button>
                </form>
            </li>
        </ul>
    </div>
</div>
