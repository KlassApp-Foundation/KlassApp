@if($notice)
    <div class="wa-lr-notice {{ $notice['ok'] ? 'wa-lr-notice--ok' : 'wa-lr-notice--error' }}" role="status" data-testid="wa-link-notice">
        <span>{{ $notice['text'] }}</span>
        <button type="button" class="ds-btn ds-btn-ghost ds-btn-sm" wire:click="dismissNotice" aria-label="Dismiss">Dismiss</button>
    </div>
@endif
