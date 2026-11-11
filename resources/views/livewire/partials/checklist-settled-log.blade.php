@if(($progressLines ?? []) !== [])
    <details class="t-log" open data-testid="checklist-log">
        <summary>What Toshi did</summary>
        <div class="t-stream" aria-live="polite" @if(!empty($streamTestId)) data-testid="{{ $streamTestId }}" @endif>
            @foreach($progressLines as $progressLine)
                @include('livewire.partials.checklist-progress-lines', ['line' => $progressLine])
            @endforeach
        </div>
    </details>
@endif
