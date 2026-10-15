{{-- SPDX-License-Identifier: MIT --}}
{{--
    Toshi panel embed — ONE source of truth for the role allow-list.
    Included by the shared dashboard shell (layouts/app.blade.php) and the
    SiteAdmin platform shell (layouts/superadmin-app.blade.php), so the two can
    no longer drift apart.
    Lives OUTSIDE #app so Vue never touches Alpine markup.

    Split-layout (2026-09-27 redesign, supersedes #823's pure-overlay Option B
    for the content area; the fixed-column geometry, z-index 32, and full-width
    navbar from #823 are deliberately PRESERVED — see toshi-ui.css dock block):
    - Default state: COLLAPSED. First render ships body.toshi-collapsed —
      confirmed via the inline class, not a post-load JS flip.
    - Content reflow: #app carries margin-right = current dock width (a CSS
      variable --toshi-w), so content never sits under the panel.
    - Resizable: [data-toshi-resize-handle] drags between 300 and 640px
      (min keeps the panel usable, max keeps ≥640px content at 1280).
    - Persistence: localStorage keys toshi_split_w / toshi_split_collapsed —
      same pattern as the #697 sidebar collapse (admin_sidebar_collapsed).
      Set pre-paint in this partial's script so there is no flash on load.
--}}
@auth
    @toshiUi
    @if(in_array(auth()->user()->usergroup_id, [1, 3, 4, 5, 11, 8, 10, 6, 7, 9]))
        @livewire('agent-toshi')
        @php
            $toshiMode = app(\App\Services\Toshi\ToshiUiSwitch::class)->mode()->value;
            $toshiPillLabel = match ($toshiMode) {
                'preview' => 'Meet Toshi',
                'assistant' => 'Talk',
                default => 'Setup guide',
            };
            $toshiSetupDone = 0;
            $toshiSetupTotal = 0;
            $toshiSchool = auth()->user()->school;
            if ($toshiSchool) {
                $toshiSetup = \App\Services\OnboardingStepsService::progress($toshiSchool, (int) auth()->id());
                $toshiSetupDone = (int) $toshiSetup['done'];
                $toshiSetupTotal = (int) $toshiSetup['total'];
            }
            // The launchers (side handle and entry pill) are for school admins and head
            // teachers only (ToshiUiSwitch::LAUNCHER_USERGROUPS). The pill also shows on
            // the dashboard and the onboarding screens only; elsewhere the handle alone
            // is the launcher. Other roles keep the panel and its other entry points.
            $toshiLauncher = app(\App\Services\Toshi\ToshiUiSwitch::class)->showsLauncher();
            $toshiPillPage = $toshiLauncher && request()->is('dashboard', '*/dashboard', 'onboarding', 'onboarding/*', '*/onboarding', '*/onboarding/*');
        @endphp
        @if($toshiLauncher)
        <div id="toshi-toggle-wrapper" class="toshi-toggle-wrapper" data-testid="toshi-toggle-wrapper"
             data-toshi-mode="{{ $toshiMode }}" data-toshi-user="{{ auth()->id() }}"
             data-setup-done="{{ $toshiSetupDone }}" data-setup-total="{{ $toshiSetupTotal }}"
             data-pill-page="{{ $toshiPillPage ? '1' : '0' }}">
            <button type="button" id="toshi-toggle" class="toshi-toggle" data-testid="toshi-toggle"
                aria-label="Open Toshi"
                title="{{ $toshiMode === 'preview' ? 'Toshi, coming soon' : 'Toshi' }}">
                <span class="toshi-handle-mark" aria-hidden="true">K</span>
            </button>
            <button type="button" id="toshi-entry-pill" class="toshi-entry-pill" data-testid="toshi-entry-pill"
                aria-label="Open Toshi">
                <span class="toshi-entry-ring" aria-hidden="true"><span class="toshi-entry-mark">K</span></span>
                <span class="toshi-entry-label">{{ $toshiPillLabel }}</span>
                @if($toshiMode === 'onboarding' && $toshiSetupTotal > $toshiSetupDone)
                    <span class="toshi-entry-chip" data-testid="toshi-entry-chip">{{ $toshiSetupDone }}/{{ $toshiSetupTotal }}</span>
                @endif
            </button>
            @toshiAssistant
            <span class="toshi-early-pill" aria-hidden="true">Early access</span>
            @endtoshiAssistant
        </div>
        @endif
        <script src="{{ asset('js/toshi-entry-pill.js') }}?v={{ filemtime(public_path('js/toshi-entry-pill.js')) }}"></script>
        <script>
        (function () {
            var W_KEY = 'toshi_split_w';
            var C_KEY = 'toshi_split_collapsed';
            var MIN_W = 300, MAX_W = 640, DEFAULT_W = 380;
            var root, toggle;

            function clampW(w) { w = parseInt(w, 10); if (!w || isNaN(w)) return DEFAULT_W; return Math.min(MAX_W, Math.max(MIN_W, w)); }

            // ── Persisted state was applied pre-paint by
            //    layouts/partials/toshi-prepaint.blade.php in <head> (html-level
            //    class survives Livewire's morph; the body class is mirrored
            //    below so ALL existing body.toshi-collapsed CSS keeps working).

            function syncToggleGlyph() {
                // The collapsed handle keeps its K mark and its server-rendered tooltip.
            }

            function persist() {
                try {
                    localStorage.setItem(C_KEY, document.body.classList.contains('toshi-collapsed') ? '1' : '0');
                } catch (e) {}
            }

            function setCollapsed(collapsed) {
                document.body.classList.toggle('toshi-collapsed', collapsed);
                document.documentElement.classList.toggle('toshi-collapsed', collapsed);
                syncToggleGlyph();
                persist();
                if (window.toshiSyncLauncher) window.toshiSyncLauncher();
                try {
                    window.dispatchEvent(new CustomEvent('toshi-collapsed-changed', { detail: { collapsed: !!collapsed } }));
                } catch (e) {}
                if (collapsed) {
                    var which = window.toshiLauncherReturn === 'pill' ? 'toshi-entry-pill' : 'toshi-toggle';
                    var back = document.getElementById(which);
                    if (!back || back.tabIndex < 0 || getComputedStyle(back).display === 'none') {
                        back = document.getElementById('toshi-toggle');
                    }
                    if (back && getComputedStyle(back).display !== 'none') back.focus();
                }
            }
            window.toshiSetCollapsed = setCollapsed; // inline handlers in the Livewire root call this

            function bindToggle() {
                root = document.querySelector('[data-toshi-root]');
                toggle = document.getElementById('toshi-toggle');
                // Mirror the html-level class (set pre-paint, morph-safe) onto
                // body so every existing body.toshi-collapsed rule applies.
                if (document.documentElement.classList.contains('toshi-collapsed')) {
                    document.body.classList.add('toshi-collapsed');
                } else {
                    document.body.classList.remove('toshi-collapsed');
                }
                syncToggleGlyph();
                if (toggle && !toggle.dataset.splitBound) {
                    toggle.dataset.splitBound = '1';
                    toggle.addEventListener('click', function () {
                        if (document.body.classList.contains('toshi-collapsed')) {
                            window.toshiLauncherReturn = 'handle';
                            if (window.toshiMarkPreviewSeen) window.toshiMarkPreviewSeen();
                            setCollapsed(false);
                        } else {
                            setCollapsed(true);
                        }
                    });
                }
                var entryPill = document.getElementById('toshi-entry-pill');
                if (entryPill && !entryPill.dataset.splitBound) {
                    entryPill.dataset.splitBound = '1';
                    entryPill.addEventListener('click', function () {
                        window.toshiLauncherReturn = 'pill';
                        if (window.toshiMarkPreviewSeen) window.toshiMarkPreviewSeen();
                        setCollapsed(false);
                    });
                    entryPill.addEventListener('keydown', function (e) {
                        if (e.key !== 'Enter') return;
                        window.toshiLauncherReturn = 'pill';
                        if (window.toshiMarkPreviewSeen) window.toshiMarkPreviewSeen();
                        setCollapsed(false);
                    });
                }
                if (window.toshiSyncLauncher) window.toshiSyncLauncher();
                else if (window.toshiPlaceEntryPill) window.toshiPlaceEntryPill();
                // Pill click expands (replaces the old delegated handler, same behavior)
                document.addEventListener('click', function (e) {
                    if (document.body.classList.contains('toshi-collapsed') && e.target.closest('.toshi-pill')) {
                        e.preventDefault();
                        setCollapsed(false);
                    }
                });
            }

            function bindResize() {
                var handle = document.querySelector('[data-toshi-resize-handle]');
                if (!handle || handle.dataset.splitBound) return;
                handle.dataset.splitBound = '1';
                var dragging = false;

                handle.addEventListener('pointerdown', function (e) {
                    if (document.body.classList.contains('toshi-collapsed')) return;
                    dragging = true;
                    handle.setPointerCapture(e.pointerId);
                    document.body.classList.add('toshi-resizing');
                    e.preventDefault();
                });
                handle.addEventListener('pointermove', function (e) {
                    if (!dragging) return;
                    var w = clampW(window.innerWidth - e.clientX);
                    document.documentElement.style.setProperty('--toshi-w', w + 'px');
                    handle.setAttribute('aria-valuenow', String(w));
                });
                function end(e) {
                    if (!dragging) return;
                    dragging = false;
                    document.body.classList.remove('toshi-resizing');
                    try {
                        localStorage.setItem(W_KEY, (parseInt(getComputedStyle(document.documentElement).getPropertyValue('--toshi-w'), 10) || DEFAULT_W) + '');
                    } catch (err) {}
                }
                handle.addEventListener('pointerup', end);
                handle.addEventListener('pointercancel', end);
                // Double-click resets the width to the default
                handle.addEventListener('dblclick', function () {
                    document.documentElement.style.setProperty('--toshi-w', DEFAULT_W + 'px');
                    try { localStorage.setItem(W_KEY, String(DEFAULT_W)); } catch (err) {}
                });
                // Keyboard resize: arrows ±10px (shift ±40), Home resets to default
                handle.addEventListener('keydown', function (e) {
                    if (document.body.classList.contains('toshi-collapsed')) return;
                    var cur = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--toshi-w'), 10) || DEFAULT_W;
                    var step = e.shiftKey ? 40 : 10;
                    var next = null;
                    if (e.key === 'ArrowLeft') next = cur + step;
                    if (e.key === 'ArrowRight') next = cur - step;
                    if (e.key === 'Home') next = DEFAULT_W;
                    if (next !== null) {
                        e.preventDefault();
                        next = clampW(next);
                        document.documentElement.style.setProperty('--toshi-w', next + 'px');
                        try { localStorage.setItem(W_KEY, next + ''); } catch (err) {}
                    }
                });
            }

            // Livewire morphs can re-create the root; delegate-observe binding.
            document.addEventListener('DOMContentLoaded', function () { bindToggle(); bindResize(); });
            if (document.readyState !== 'loading') { bindToggle(); bindResize(); }
            window.addEventListener('livewire:navigated', function () { bindToggle(); bindResize(); });
        })();
        </script>
    @endif
    @endtoshiUi
@endauth
