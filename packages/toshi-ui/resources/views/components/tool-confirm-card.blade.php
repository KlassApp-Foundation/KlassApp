@props([
    'toolName' => 'Action',
    'toolIcon' => '⚡',
    'params' => [],
    'state' => 'pending',
    'wireKey' => 'tcc',
    'confirmMethod' => 'confirmYes',
    'cancelMethod' => 'confirmNo',
    'cancelParam' => '',
])
<div class="toshi-confirm-card {{ $state === 'cancelled' ? 'is-cancelled' : 'is-pending' }}"
     wire:key="{{ $wireKey }}-{{ $state }}">
    <div class="toshi-confirm-card-header">
        <div class="toshi-confirm-card-title">
            <span class="toshi-confirm-card-icon">{{ $state === 'cancelled' ? '✕' : $toolIcon }}</span>
            <span>{{ $toolName }}</span>
        </div>
        @if($state === 'cancelled')
        <span class="toshi-confirm-cancelled-badge">✕ Cancelled</span>
        @endif
    </div>
    @if(count($params) > 0)
    <div class="toshi-confirm-card-body">
        <div class="toshi-confirm-param-list">
            @foreach($params as $param)
            <div class="toshi-confirm-param-row">
                <span class="toshi-confirm-param-label">{{ $param['label'] }}</span>
                <span class="toshi-confirm-param-value">{{ $param['value'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    <div class="toshi-confirm-card-footer">
        @if($state === 'pending')
        <button wire:click="{{ $confirmMethod }}" type="button"
                class="toshi-confirm-btn toshi-confirm-btn-yes">
            ✓ Confirm
        </button>
        <button wire:click="{{ $cancelMethod }}('{{ $cancelParam }}')" type="button"
                class="toshi-confirm-btn toshi-confirm-btn-no">
            Cancel
        </button>
        @else
        <div class="toshi-confirm-cancelled-msg">✕ Cancelled — no changes made</div>
        @endif
    </div>
</div>
