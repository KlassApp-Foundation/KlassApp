{{-- SPDX-License-Identifier: MIT --}}
{{--
    Toshi split-layout PRE-PAINT bootstrap (2026-09-27 redesign).
    Runs in <head> so the persisted width + collapsed state apply BEFORE first
    paint AND before Livewire's initial hydration snapshot — the embed script at
    body-end was reverted by Livewire's morph (it snapshots the body's class
    attribute during hydration and restores it), which silently undid the
    default-collapsed class. From <head> the class is present in the snapshot
    itself, so nothing reverts it.

    Keys mirror toshi-embed: toshi_split_w (300–640, default 380),
    toshi_split_collapsed ('0' = expanded; absent/'1' = collapsed default).
    Same localStorage pattern as the #697 sidebar collapse.
--}}
@auth
    @toshiUi
    @if(in_array(auth()->user()->usergroup_id, [1, 3, 4, 5, 11, 8, 10, 6, 7, 9]))
        <script>
        (function () {
            try {
                var w = parseInt(localStorage.getItem('toshi_split_w'), 10);
                if (!w || isNaN(w)) w = 380;
                w = Math.min(640, Math.max(300, w));
                document.documentElement.style.setProperty('--toshi-w', w + 'px');
                if (localStorage.getItem('toshi_split_collapsed') !== '0') {
                    document.documentElement.classList.add('toshi-collapsed');
                }
            } catch (e) {}
        })();
        </script>
    @endif
    @endtoshiUi
@endauth
