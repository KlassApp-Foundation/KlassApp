{{-- One pending WhatsApp link request. $r is a LinkRequestBoard::pending() row. --}}
<article class="ds-card wa-lr-card" wire:key="link-request-{{ $r['id'] }}" data-testid="wa-link-request" data-request-id="{{ $r['id'] }}"
         x-data="{ confirming: false }">
    <div class="wa-lr-card__main">
        <p class="wa-lr-phone" data-testid="wa-link-request-phone">{{ $r['phone'] }}</p>
        <p class="wa-lr-student">
            <span class="wa-lr-student__name">{{ $r['child_name'] !== '' ? $r['child_name'] : 'Student not named' }}</span>
            @if($r['child_class'] !== '')
                <span class="wa-lr-chip">{{ $r['child_class'] }}</span>
            @endif
        </p>
        <p class="wa-lr-meta">
            <span>Requested {{ $r['requested_at']?->diffForHumans() }} · {{ $r['requested_at']?->timezone(config('app.timezone'))->format('H:i') }}</span>
            @if($r['time_left'] !== null)
                <span class="wa-lr-time {{ $r['expires_at']->lte(now()->addMinutes(5)) ? 'wa-lr-time--soon' : '' }}" data-testid="wa-link-request-time-left">{{ $r['time_left'] }}</span>
            @else
                <span class="wa-lr-time wa-lr-time--none">Does not expire</span>
            @endif
        </p>
    </div>
    <div class="wa-lr-actions">
        @if($r['demo_bind'] && $r['student_id'])
            <div class="wa-lr-actions__row" x-show="!confirming">
                <x-button variant="success" size="lg" class="wa-lr-btn" wire:click="approve({{ $r['id'] }})" wire:loading.attr="disabled" data-testid="wa-approve">Approve</x-button>
                <x-button variant="outline" size="lg" class="wa-lr-btn wa-lr-btn--reject" x-on:click="confirming = true" data-testid="wa-reject">Reject</x-button>
            </div>
        @else
            <div class="wa-lr-actions__row" x-show="!confirming">
                <x-button variant="primary" size="lg" class="wa-lr-btn" :href="url('/admin/approvals')">Choose student</x-button>
                <x-button variant="outline" size="lg" class="wa-lr-btn wa-lr-btn--reject" x-on:click="confirming = true" data-testid="wa-reject">Reject</x-button>
            </div>
        @endif
        <div class="wa-lr-confirm" x-show="confirming" x-cloak role="group" aria-label="Confirm reject">
            <p class="wa-lr-confirm__text">Reject this request?</p>
            <div class="wa-lr-actions__row">
                <x-button variant="danger" size="lg" class="wa-lr-btn" wire:click="reject({{ $r['id'] }})" wire:loading.attr="disabled" data-testid="wa-reject-confirm">Yes, reject</x-button>
                <x-button variant="ghost" size="lg" class="wa-lr-btn" x-on:click="confirming = false">Cancel</x-button>
            </div>
        </div>
    </div>
</article>
