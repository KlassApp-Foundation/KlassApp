{{-- SPDX-License-Identifier: MIT --}}
{{--
    Single shared dashboard header (chrome only). Role shells include this; each
    role's MENU stays in layouts/<role>/menu.blade.php and is unaffected.

    Parameters (all optional):
      variant            'dashboard' (cream themed header, default) | 'plain' (white/font-exo)
      notifyMode         notification component mode, e.g. 'teacher'; null => no notification
      brandText          when set, show this text instead of the school logo+name
      brandRoute         route name for the brand link (default 'dashboard')
      showToggle         show the desktop sidebar collapse toggle (default: variant === 'dashboard')
      showAcademicYear   show the academic-year selector (default: variant === 'dashboard')
      extraPricing       show the Pricing link (default false)
      showLogout         show a Logout button instead of the profile dropdown (default false)
--}}
@php
    $variant          = $variant          ?? 'dashboard';
    $notifyMode       = $notifyMode       ?? 'admin';
    $brandText        = $brandText        ?? null;
    $brandRoute       = $brandRoute       ?? 'dashboard';
    $showToggle       = $showToggle       ?? ($variant === 'dashboard');
    $showAcademicYear = $showAcademicYear ?? ($variant === 'dashboard');
    $extraPricing     = $extraPricing     ?? false;
    $showLogout       = $showLogout       ?? false;
    $brandLogo        = $brandLogo        ?? null;   // 'klassapp' => KlassApp mark (parent; may span schools)
    $familyMenu       = $familyMenu       ?? false;  // parent: children + schools menu in the header

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

    $navClass = $variant === 'dashboard'
        ? 'navbar dashboard-themed-header w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center'
        : 'navbar bg-white w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center';
    $nameClass = $variant === 'dashboard'
        ? 'text-lg lg:text-3xl font-semibold'
        : 'text-lg lg:text-3xl font-exo font-medium text-gray-700 px-4';
@endphp
<nav class="{{ $navClass }}" @if($variant === 'dashboard') style="background:#FAFAF5;border-bottom:1px solid #E2E8F0;" @endif>
    <div class="nav-brand flex items-center">
        @if($navUser)
            @if($showToggle)
                <button type="button" id="sidebar-collapse-toggle"
                        class="mr-3 hidden md:inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:bg-black/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        aria-label="Collapse sidebar" aria-expanded="true" aria-controls="admin-sidebar" title="Collapse sidebar">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="M15 9l-3 3 3 3"/></svg>
                </button>
            @endif
            <button class="mr-3 {{ $variant === 'dashboard' ? 'md:hidden' : 'block lg:hidden md:hidden' }}" id="mobile-menu-trigger" aria-label="Toggle sidebar" aria-expanded="false" aria-controls="res_sidebar">
                {{-- NOTE: click handling lives in public/js/custom.js (delegated). An earlier inline
                     onclick="showsidebar('res_sidebar')" here double-bound with that listener, so every
                     tap re-added `hidden` right after removing it and the menu could NEVER open (<768px). --}}
                <span class="navbar-toggler-icon">
                    <svg class="w-6 h-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path class="heroicon-ui" d="M4 5h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z" fill="currentColor"/></svg>
                </span>
            </button>
            @if($brandText)
                <a class="text-xl lg:text-2xl font-exo font-semibold px-2" href="{{ route($brandRoute) }}">
                    <strong>{{ $brandText }}</strong>
                </a>
            @elseif($brandLogo === 'klassapp')
                {{-- A parent can have children at several schools, so one school's logo
                     would be wrong here; the children/schools menu carries identity. --}}
                <a class="h-10 object-contain" href="{{ route($brandRoute) }}" aria-label="KlassApp">
                    <img src="{{ asset('images/klassapp-logo-primary.svg') }}"
                         class="h-10 w-auto object-contain mr-3"
                         alt="KlassApp"
                         onerror="this.onerror=null;this.src='{{ asset('images/klassapp-logo.svg') }}';">
                </a>
                <a class="parent-brand-name {{ $nameClass }}" href="{{ route($brandRoute) }}">
                    <strong>KlassApp</strong>
                </a>
            @else
                <a class="h-10 object-contain" href="{{ route($brandRoute) }}"
                   aria-label="{{ $navSchool ? ucwords($navSchool->name).' dashboard' : 'Dashboard' }}">
                    <img src="{{ $schoolLogo ?: asset('images/klassapp-logo-primary.svg') }}"
                         class="h-10 w-auto object-contain mr-3"
                         alt="{{ $schoolLogo ? ucwords($navSchool->name).' logo' : 'KlassApp' }}"
                         onerror="this.onerror=null;this.src='{{ asset('images/klassapp-logo-primary.svg') }}';">
                </a>
                <a class="{{ $nameClass }}" href="{{ route($brandRoute) }}">
                    <strong>{{ ucwords($navSchool->name) }}</strong>
                </a>
            @endif
        @else
            @include('layouts.partials.logo')
        @endif
        @if($familyMenu)
            @include('layouts.partials.family-menu', ['navUser' => $navUser, 'brandRoute' => $brandRoute])
        @endif
    </div>

    <div class="navbar-menu collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto flex"></ul>
    </div>

    <div class="flex {{ $variant === 'dashboard' ? 'flex-col-reverse lg:flex-row md:flex-row' : '' }} items-center">
        @if($showAcademicYear)
            <div class="hidden lg:block md:block">
                <nav-bar></nav-bar>
            </div>
        @endif
        <div class="flex items-center">
            {{-- Command palette trigger. It dispatches the palette's OWN shortcut so
                 there is a single source of open/close logic (the package binds
                 cmd/ctrl+K; dispatching both modifiers would toggle twice). --}}
            @auth
                <button type="button" id="command-palette-trigger"
                        class="inline-flex items-center gap-2 mr-3 px-2.5 py-1.5 rounded-lg text-sm hover:opacity-90"
                        style="border: 1px solid var(--d-border); background: var(--d-white); color: var(--d-text-secondary);"
                        title="Search your pages" aria-label="Open the command palette"
                        onclick="(function () {
                            var mac = /Mac|iPhone|iPad/.test(navigator.platform || '');
                            window.dispatchEvent(new KeyboardEvent('keydown', {
                                key: 'k', code: 'KeyK', bubbles: true, cancelable: true,
                                metaKey: mac, ctrlKey: ! mac
                            }));
                        })()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                    <span class="hidden sm:inline">Search</span>
                    <kbd class="hidden sm:inline" id="command-palette-kbd" style="font-family: 'DM Sans', sans-serif; font-size: 0.68rem; padding: 1px 5px; border: 1px solid var(--d-border); border-radius: 4px; color: var(--d-muted);">⌘K</kbd>
                </button>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var k = document.getElementById('command-palette-kbd');
                        if (k && ! /Mac|iPhone|iPad/.test(navigator.platform || '')) k.textContent = 'Ctrl K';
                    });
                </script>
            @endauth
            @if($notifyMode)
                <notification url="{{ url('/') }}" mode="{{ $notifyMode }}"></notification>
            @endif
            @if($showLogout)
                <form method="POST" action="{{ route('logout') }}" class="inline ml-3">
                    @csrf
                    <button type="submit" class="ds-btn ds-btn-sm" style="background:#fff;color:#0F172A;font-weight:600;">Logout</button>
                </form>
            @else
                <div class="navbar-menu {{ $variant === 'dashboard' ? 'ml-5' : 'lg:ml-5 md:ml-3' }}">
                    <ul class="navbar-nav ml-auto flex items-center">
                        @if($extraPricing)
                            <li class="mx-2 hidden lg:block"><a href="{{ url('/pricing') }}">{{ __('Pricing') }}</a></li>
                        @endif
                        @guest
                            <li class="nav-item px-2">
                                <a class="nav-link" href="{{ route('login') }}" id="login">{{ __('Login') }}</a>
                            </li>
                            <li class="nav-item px-2">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                            </li>
                        @else
                            <li>
                                @include('layouts.partials.profile-dropdown')
                            </li>
                        @endguest
                    </ul>
                </div>
            @endif
        </div>
    </div>
</nav>
@if($showToggle)
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
@endif
