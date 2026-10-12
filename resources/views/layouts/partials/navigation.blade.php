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
      chromeInSidebar    true => suppress the notification bell + profile dropdown here
                         (they live in the sidebar footer instead — admin shell only,
                         2026-09-27 UI-polish pass)
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
    $chromeInSidebar  = $chromeInSidebar ?? false;  // admin: chrome lives in the sidebar footer

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
        ? 'nav-school-name'
        : 'nav-school-name font-exo text-gray-700 px-4';
@endphp
<nav class="{{ $navClass }}" @if($variant === 'dashboard') style="background:#FAFAF5;border-bottom:1px solid #E2E8F0;" @endif>
    <div class="nav-brand flex items-center">
        @if($navUser)
            @if($showToggle)
                <button type="button" id="sidebar-collapse-toggle"
                        class="mr-3 hidden md:inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:bg-black/5 sidebar-fold-toggle"
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
                <a class="h-10 object-contain" href="{{ route($brandRoute) }}" aria-label="KlassApp home">
                    <img src="{{ asset('images/klassapp-logo-primary.svg') }}"
                         class="h-10 w-auto object-contain mr-3"
                         alt="KlassApp"
                         onerror="this.onerror=null;this.src='{{ asset('images/klassapp-logo.svg') }}';">
                </a>
                <a class="parent-brand-name {{ $nameClass }}" href="{{ route($brandRoute) }}" aria-label="KlassApp home">
                    <strong>KlassApp</strong>
                </a>
            @else
                @php
                    $schoolTerm = \App\Support\SchoolHeaderLine::term($navSchool?->id);
                    $schoolTitle = ucwords((string) ($navSchool->name ?? ''));
                @endphp
                <a class="nav-school-line" href="{{ route($brandRoute) }}">
                    @if($schoolLogo)
                        <img src="{{ $schoolLogo }}" class="nav-school-logo" alt="{{ $schoolTitle }}">
                    @endif
                    <span class="{{ $nameClass }}"><strong>{{ $schoolTitle }}</strong></span>
                    @if($schoolTerm !== '')
                        <span class="nav-school-term">{{ $schoolTerm }}</span>
                    @endif
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

    <div class="flex flex-row flex-wrap items-center gap-2 {{ $variant === 'dashboard' ? 'justify-end' : '' }}">
        @if($showAcademicYear)
            <div class="dashboard-ay-selector" data-testid="dashboard-ay-selector">
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
                    <kbd class="hidden sm:inline" id="command-palette-kbd" style="font-family: 'DM Sans', sans-serif; font-size: 0.68rem; padding: 1px 5px; border: 1px solid var(--d-border); border-radius: 4px; color: #64748B;">⌘K</kbd>
                </button>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var k = document.getElementById('command-palette-kbd');
                        if (k && ! /Mac|iPhone|iPad/.test(navigator.platform || '')) k.textContent = 'Ctrl K';
                    });
                </script>
            @endauth
            @if($notifyMode && ! $chromeInSidebar)
                <notification url="{{ url('/') }}" mode="{{ $notifyMode }}"></notification>
            @endif
            @if($showLogout)
                <form method="POST" action="{{ route('logout') }}" class="inline ml-3">
                    @csrf
                    <button type="submit" class="ds-btn ds-btn-sm" style="background:#fff;color:#0F172A;font-weight:600;">Logout</button>
                </form>
            @elseif(! $chromeInSidebar)
                <div class="navbar-menu {{ $variant === 'dashboard' ? 'ml-5' : 'lg:ml-5 md:ml-3' }}">
                    <ul class="navbar-nav ml-auto flex items-center">
                        @if($extraPricing)
                            <li class="mx-2 hidden lg:block"><a href="{{ \App\Helpers\SiteHelper::talkToSalesUrl() }}">{{ __('Talk to sales') }}</a></li>
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
            @auth
                @if($chromeInSidebar)
                    {{-- PR7: below 768px the sidebar is a drawer, so the account menu
                         hangs from a top-bar avatar (right-aligned popover). --}}
                    <div class="ml-auto md:hidden flex items-center">
                        @include('layouts.partials.profile-dropdown', ['variant' => 'topbar'])
                    </div>
                @endif
            @endauth
        </div>
    </div>
</nav>
@if($showToggle)
    @push('scripts')
    <script>
    (function () {
        var KEY = 'ka:sidebar:v3';
        function btn() { return document.getElementById('sidebar-collapse-toggle'); }
        function preferred() {
            try {
                var v = localStorage.getItem(KEY);
                if (v === '1' || v === '0') return v === '1';
            } catch (e) {}
            var w = window.innerWidth;
            return w >= 761 && w < 1024;
        }
        function apply(collapsed) {
            if (window.innerWidth < 761) collapsed = false;
            document.body.classList.toggle('sidebar-collapsed', collapsed);
            var el = btn();
            if (el) {
                el.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                el.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
                el.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            }
        }
        try { apply(preferred()); } catch (e) {}
        // Delegate: the button lives inside the Vue-mounted #app, so a direct
        // listener is lost whenever Vue re-renders that subtree.
        document.addEventListener('click', function (e) {
            var el = e.target.closest && e.target.closest('#sidebar-collapse-toggle');
            if (!el) return;
            e.preventDefault();
            var collapsed = !document.body.classList.contains('sidebar-collapsed');
            apply(collapsed);
            try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e2) {}
            document.dispatchEvent(new CustomEvent('ka-sidebar-changed'));
        });
        document.addEventListener('ka-sidebar-changed', function () {
            if (window.kaCloseAccount) window.kaCloseAccount();
            if (window.kaCloseFlyouts) window.kaCloseFlyouts();
            if (window.kaHideTip) window.kaHideTip();
        });
    })();
    (function () {
        var tip = document.createElement('div');
        tip.id = 'ka-rail-tip';
        tip.setAttribute('role', 'tooltip');
        tip.hidden = true;
        document.body.appendChild(tip);
        var timer = null;
        var current = null;
        var armed = null;
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        function hide() {
            clearTimeout(timer);
            timer = null;
            armed = null;
            tip.hidden = true;
            if (current) current.removeAttribute('aria-describedby');
            current = null;
        }
        function host(node) {
            return node && node.closest ? node.closest('#admin-sidebar [data-tip]') : null;
        }
        function show(el) {
            if (!document.body.classList.contains('sidebar-collapsed')) return;
            if (document.querySelector('#admin-sidebar .is-flyout, [data-account-trigger][aria-expanded="true"]')) return;
            var row = el.closest('.sidebar-v3-item');
            if (row && row.classList.contains('is-flyout')) return;
            var text = el.getAttribute('data-tip');
            if (!text) return;
            var box = el.getBoundingClientRect();
            tip.textContent = text;
            tip.hidden = false;
            var left = box.right + 8;
            tip.style.left = left + 'px';
            tip.style.top = (box.top + box.height / 2) + 'px';
            if (left + tip.offsetWidth > window.innerWidth - 8) {
                tip.style.left = Math.max(8, window.innerWidth - 8 - tip.offsetWidth) + 'px';
            }
            el.setAttribute('aria-describedby', 'ka-rail-tip');
            current = el;
        }
        function arm(el) {
            if (!document.body.classList.contains('sidebar-collapsed') || armed === el) return;
            clearTimeout(timer);
            if (current && current !== el) {
                tip.hidden = true;
                current.removeAttribute('aria-describedby');
                current = null;
            }
            armed = el;
            timer = setTimeout(function () { show(el); }, reduce ? 0 : 300);
        }
        document.addEventListener('mouseover', function (e) {
            var el = host(e.target);
            if (el) arm(el);
        });
        document.addEventListener('mouseout', function (e) {
            var el = host(e.target);
            if (!el || el.contains(e.relatedTarget) || document.activeElement === el) return;
            hide();
        });
        document.addEventListener('focusin', function (e) {
            var el = host(e.target);
            if (el) arm(el);
        });
        document.addEventListener('focusout', function () { hide(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hide();
        });
        window.kaHideTip = hide;
    })();
    (function () {
        var OPEN_MS = 80;
        var CLOSE_MS = 200;
        var armed = null;
        var openTimer = null;
        var closeTimer = null;
        var lastPointer = 'mouse';

        function collapsed() { return document.body.classList.contains('sidebar-collapsed'); }
        function parents() {
            return Array.prototype.filter.call(document.querySelectorAll('#admin-sidebar .sidebar-v3-item'), function (item) {
                return item.querySelector('.sidebar-v3-children');
            });
        }
        function alpine(item) {
            if (!window.Alpine) return null;
            try { return window.Alpine.$data(item); } catch (e) { return null; }
        }
        function predict(item) {
            var trigger = item.querySelector('.sidebar-v3-link').getBoundingClientRect();
            var panel = item.querySelector('.sidebar-v3-children');
            var rows = panel.querySelectorAll('.sidebar-v3-child').length;
            var height = Math.max(panel.scrollHeight || 0, 28 + rows * 32);
            var top = trigger.top;
            if (top + height > window.innerHeight - 8) top = Math.max(8, window.innerHeight - 8 - height);
            return { left: 64, top: top, width: 200, height: height, trigger: trigger };
        }
        function placeBridge(item) {
            var bridge = item.querySelector('.sidebar-v3-bridge');
            if (!bridge) return;
            var pred = predict(item);
            var top = Math.min(pred.trigger.top, pred.top);
            var bottom = Math.max(pred.trigger.bottom, pred.top + pred.height);
            bridge.hidden = false;
            bridge.style.left = pred.trigger.right + 'px';
            bridge.style.width = Math.max(12, pred.left - pred.trigger.right) + 'px';
            bridge.style.top = top + 'px';
            bridge.style.height = Math.max(pred.trigger.height, bottom - top) + 'px';
        }
        function hideBridge(item) {
            var bridge = item.querySelector('.sidebar-v3-bridge');
            if (bridge) bridge.hidden = true;
        }
        function setFlyout(item, value) {
            var data = alpine(item);
            if (data) data.flyout = value;
            item.classList.toggle('is-flyout', value);
            var link = item.querySelector('.sidebar-v3-link');
            if (link && collapsed()) link.setAttribute('aria-expanded', value ? 'true' : 'false');
            if (value) placeBridge(item);
            else hideBridge(item);
        }
        function closeFlyout(item) {
            if (!item) return;
            setFlyout(item, false);
            if (armed === item) armed = null;
        }
        function closeAll(except) {
            parents().forEach(function (item) { if (item !== except) closeFlyout(item); });
        }
        function openFlyout(item) {
            closeAll(item);
            if (window.kaCloseAccount) window.kaCloseAccount();
            if (window.kaHideTip) window.kaHideTip();
            var pred = predict(item);
            item.style.setProperty('--fly-top', pred.top + 'px');
            setFlyout(item, true);
            var panel = item.querySelector('.sidebar-v3-children');
            var shown = panel.getBoundingClientRect();
            if (shown.height > 0 && shown.bottom > window.innerHeight - 8) {
                item.style.setProperty('--fly-top', Math.max(8, window.innerHeight - 8 - shown.height) + 'px');
            }
            armed = item;
        }
        function cancelClose() { clearTimeout(closeTimer); closeTimer = null; }
        function scheduleClose(item) {
            clearTimeout(closeTimer);
            closeTimer = setTimeout(function () { closeFlyout(item); }, CLOSE_MS);
        }
        function boxHas(box, x, y, pad) {
            pad = pad || 0;
            return box && x >= box.left - pad && x <= box.right + pad && y >= box.top - pad && y <= box.bottom + pad && box.width > 0 && box.height > 0;
        }
        function onTrigger(item, x, y) {
            return boxHas(item.querySelector('.sidebar-v3-link').getBoundingClientRect(), x, y);
        }
        function inPanel(item, x, y) {
            var bridge = item.querySelector('.sidebar-v3-bridge');
            if (bridge && !bridge.hidden && boxHas(bridge.getBoundingClientRect(), x, y, 8)) return true;
            var pred = predict(item);
            if (x >= pred.left - 8 && x <= pred.left + pred.width + 8 && y >= pred.top - 8 && y <= pred.top + pred.height + 8) return true;
            var panel = item.querySelector('.sidebar-v3-children');
            return !!(item.classList.contains('is-flyout') && panel && boxHas(panel.getBoundingClientRect(), x, y, 8));
        }
        function arm(e) {
            if (!collapsed() || !e.target.closest) return;
            var link = e.target.closest('#admin-sidebar .sidebar-v3-link');
            if (!link) return;
            var item = link.closest('.sidebar-v3-item');
            if (!item || !item.querySelector('.sidebar-v3-children')) return;
            if (armed && armed !== item) {
                clearTimeout(openTimer);
                cancelClose();
                closeFlyout(armed);
            }
            if (item.classList.contains('is-flyout')) { cancelClose(); armed = item; return; }
            clearTimeout(openTimer);
            cancelClose();
            placeBridge(item);
            armed = item;
            openTimer = setTimeout(function () { if (armed === item) openFlyout(item); }, OPEN_MS);
        }
        function track(e) {
            if (!collapsed() || !armed) return;
            if (onTrigger(armed, e.clientX, e.clientY)) { cancelClose(); return; }
            if (inPanel(armed, e.clientX, e.clientY)) {
                cancelClose();
                if (!armed.classList.contains('is-flyout')) { clearTimeout(openTimer); openFlyout(armed); }
                return;
            }
            clearTimeout(openTimer);
            if (armed.classList.contains('is-flyout')) {
                if (!closeTimer) scheduleClose(armed);
            } else armed = null;
        }
        document.addEventListener('pointerover', arm);
        document.addEventListener('mouseover', arm);
        document.addEventListener('pointermove', track);
        document.addEventListener('mousemove', track);
        document.addEventListener('pointerdown', function (e) {
            lastPointer = e.pointerType || 'mouse';
            if (!collapsed()) return;
            parents().forEach(function (item) {
                if (!item.classList.contains('is-flyout')) return;
                if (!onTrigger(item, e.clientX, e.clientY) && !inPanel(item, e.clientX, e.clientY)) closeFlyout(item);
            });
        });
        document.addEventListener('click', function (e) {
            if (!collapsed() || lastPointer !== 'touch' || !e.target.closest) return;
            var link = e.target.closest('#admin-sidebar .sidebar-v3-link');
            if (!link) return;
            var item = link.closest('.sidebar-v3-item');
            if (!item || !item.querySelector('.sidebar-v3-children')) return;
            if (!item.classList.contains('is-flyout')) {
                e.preventDefault();
                e.stopPropagation();
                openFlyout(item);
            }
        }, true);
        document.addEventListener('keydown', function (e) {
            if (!collapsed()) return;
            var current = document.activeElement;
            if (!current || !current.closest) return;
            var item = current.closest('#admin-sidebar .sidebar-v3-item');
            if (!item || !item.querySelector('.sidebar-v3-children')) return;
            var trigger = item.querySelector('.sidebar-v3-link');
            var links = Array.prototype.slice.call(item.querySelectorAll('.sidebar-v3-child'));
            if (current === trigger && (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowRight')) {
                e.preventDefault();
                openFlyout(item);
                return;
            }
            if (!item.classList.contains('is-flyout')) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                var idx = links.indexOf(current);
                var next = e.key === 'ArrowDown'
                    ? (idx < 0 ? 0 : Math.min(links.length - 1, idx + 1))
                    : (idx < 0 ? links.length - 1 : Math.max(0, idx - 1));
                if (links[next]) links[next].focus();
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                closeFlyout(item);
                trigger.focus();
            }
        });
        document.addEventListener('focusout', function (e) {
            var item = e.target.closest && e.target.closest('#admin-sidebar .sidebar-v3-item.is-flyout');
            if (!item) return;
            if (e.relatedTarget && item.contains(e.relatedTarget)) return;
            closeFlyout(item);
        });
        document.addEventListener('ka-sidebar-changed', function () { closeAll(null); });
        window.kaCloseFlyouts = function () { closeAll(null); };
    })();
    </script>
    @endpush
@endif
