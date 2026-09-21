{{-- SPDX-License-Identifier: MIT --}}
{{--
    Toshi panel embed — ONE source of truth for the role allow-list.
    Included by the shared dashboard shell (layouts/app.blade.php) and the
    SiteAdmin platform shell (layouts/superadmin-app.blade.php), so the two can
    no longer drift apart.
    Lives OUTSIDE #app so Vue never touches Alpine markup.
--}}
@auth
    @if(in_array(auth()->user()->usergroup_id, [1, 3, 4, 5, 11, 8, 10, 6, 7, 9]))
        @livewire('agent-toshi')
        <div id="toshi-toggle-wrapper" class="toshi-toggle-wrapper" data-testid="toshi-toggle-wrapper">
            <div id="toshi-toggle" class="toshi-toggle" data-testid="toshi-toggle" title="Open Toshi" onclick="document.body.classList.toggle('toshi-collapsed');var t=document.getElementById('toshi-toggle');t.textContent=document.body.classList.contains('toshi-collapsed')?'◀':'▶'">▶</div>
        </div>
        <script>
        document.addEventListener('click', function(e) {
            if (document.body.classList.contains('toshi-collapsed') && e.target.closest('.toshi-pill')) {
                e.preventDefault();
                document.body.classList.remove('toshi-collapsed');
                document.getElementById('toshi-toggle').textContent = '▶';
            }
        });
        </script>
    @endif
@endauth
