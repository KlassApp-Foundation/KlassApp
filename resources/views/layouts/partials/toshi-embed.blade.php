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
        <div id="toshi-toggle-wrapper" class="toshi-toggle-wrapper" data-testid="toshi-toggle-wrapper">
            <div id="toshi-toggle" class="toshi-toggle" data-testid="toshi-toggle" title="Open Toshi">▶</div>
        </div>
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
                var c = document.body.classList.contains('toshi-collapsed');
                if (toggle) {
                    toggle.textContent = c ? '▶' : '◀';
                    toggle.title = c ? 'Open Toshi' : 'Collapse Toshi';
                }
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
                try {
                    window.dispatchEvent(new CustomEvent('toshi-collapsed-changed', { detail: { collapsed: !!collapsed } }));
                } catch (e) {}
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
                        setCollapsed(!document.body.classList.contains('toshi-collapsed'));
                    });
                }
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
