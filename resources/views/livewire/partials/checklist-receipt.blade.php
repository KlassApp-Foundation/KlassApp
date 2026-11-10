@foreach($groups as $group)
    <div class="t-group t-group-{{ $group['tone'] }}" data-testid="receipt-{{ $group['tone'] }}">
        <div class="t-group-name">{{ $group['name'] }}</div>
        @foreach($group['rows'] as $row)
            <div class="t-row">
                <span>{{ $row['label'] }}</span>
                <b class="{{ (int) $row['value'] === 0 ? 'is-zero' : '' }}{{ $group['tone'] === 'flag' && (int) $row['value'] !== 0 ? ' t-flag-figure' : '' }}">{{ $row['value'] }}</b>
            </div>
        @endforeach
        @if(! empty($group['total']))
            <div class="t-row t-total">
                <span>{{ $group['total']['label'] }}</span>
                <b>{{ $group['total']['value'] }}</b>
            </div>
        @endif
    </div>
@endforeach
