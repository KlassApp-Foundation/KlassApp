{{-- Released pill, lock, and who released it. $lock is at, timezone, name from marks_releases. --}}
@if(!empty($lock['at']))
    @php
        $releasedOn = $lock['at'] instanceof \Carbon\CarbonInterface
            ? $lock['at']->timezone($lock['timezone'] ?: 'Africa/Kampala')->format('j M Y')
            : '';
    @endphp
    <span class="ka-released" data-testid="released-lock">
        <span class="ka-pill ka-pill-green ka-released-pill" data-testid="released-pill">
            <svg class="ka-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            Released and locked
        </span>
        <span data-testid="released-on">Released on {{ $releasedOn }} by {{ $lock['name'] }}</span>
    </span>
@endif
