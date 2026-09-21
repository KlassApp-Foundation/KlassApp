{{-- SPDX-License-Identifier: MIT --}}
{{-- Command palette (Step 4) — wire-elements/spotlight, fed by App\Spotlight\NavigationCommand
     which derives role-scoped destinations from config/navigation.php (the sidebar
     source of truth). Included OUTSIDE #app, like Toshi: Vue owns #app and replaces
     server-rendered nodes, which would break Alpine/Livewire markup inside it. --}}
@auth
    <div id="ds-command-palette">
        <livewire:livewire-ui-spotlight />
    </div>

    {{-- Guard for a real bug in wire-elements/spotlight 2.0.4: go() does
         `filteredItems()[selected][0].item.id` even when the list is empty, which
         throws "Cannot read properties of undefined (reading '0')" on Enter with no
         results. Wrapping the factory (which runs after the package's inline script
         and before Alpine initialises the component) no-ops that case instead.
         Remove once upstream fixes it. --}}
    <script>
        (function () {
            if (! window.LivewireUISpotlight || window.__dsSpotlightEmptyGuard) return;
            window.__dsSpotlightEmptyGuard = true;
            var factory = window.LivewireUISpotlight;
            window.LivewireUISpotlight = function (config) {
                var state = factory(config);
                var go = state.go;
                state.go = function (id) {
                    if (! id) {
                        var items = [];
                        try { items = this.filteredItems() || []; } catch (e) { return; }
                        if (items.length === 0) return;   // nothing selected -> nothing to do
                    }
                    return go.apply(this, arguments);
                };
                return state;
            };
        })();
    </script>
@endauth
