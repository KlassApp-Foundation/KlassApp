<li>
    <span class="ka-dot ka-dot-{{ $event['kind'] }}"></span>
    <div>
        <div class="ka-history-event-title">
            <strong>{{ $event['title'] }}</strong>
            @if($event['when'] !== '')
                <span>{{ $event['when'] }}</span>
            @endif
        </div>
        @if($event['by'] !== '')
            <div class="ka-history-by">{{ $event['by'] }}</div>
        @endif
        @if($event['detail'])
            <div class="{{ $event['note'] ? 'ka-history-note' : 'ka-history-by' }}">{{ $event['detail'] }}</div>
        @endif
    </div>
</li>
