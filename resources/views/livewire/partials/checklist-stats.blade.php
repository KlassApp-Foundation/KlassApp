@if($stats !== [])
    <div class="checklist-stats" data-testid="checklist-stats">
        @foreach($stats as $stat)
            <div class="checklist-stat {{ $stat['label'] === 'Terms' ? 'wide' : '' }}" wire:key="stat-{{ $stat['label'] }}">
                <b>{{ $stat['value'] }}</b>
                <span>{{ $stat['label'] }}</span>
                @if(($stat['detail'] ?? '') !== '')
                    <small>{{ $stat['detail'] }}</small>
                @endif
            </div>
        @endforeach
    </div>
@endif
