{{-- One pending WhatsApp link request. $r is a LinkRequestBoard::pending() row. --}}
<article class="ds-card wa-lr-card" wire:key="link-request-{{ $r['id'] }}" data-testid="wa-link-request" data-request-id="{{ $r['id'] }}" data-source="{{ $r['source'] }}"
         x-data="{ confirming: false }">
    <div class="wa-lr-card__main">
        <p class="wa-lr-parent">
            <span class="wa-lr-parent__name" data-testid="wa-link-request-parent">{{ $r['parent_name'] }}</span>
            <span class="wa-lr-chip wa-lr-chip--source" data-testid="wa-link-request-source">{{ $r['source_label'] }}</span>
        </p>
        <p class="wa-lr-phone" data-testid="wa-link-request-phone">{{ $r['phone_display'] }}</p>
        <p class="wa-lr-student">
            <span class="wa-lr-student__name">{{ $r['child_name'] }}</span>
            @if($r['class_name'] !== '')
                <span class="wa-lr-chip">{{ $r['class_name'] }}</span>
            @endif
            @if($r['stream'] !== '')
                <span class="wa-lr-chip">{{ $r['stream'] }}</span>
            @endif
        </p>
        @if($r['needs_check'])
            <p class="wa-lr-typed" data-testid="wa-link-request-typed">
                Parent typed
                @if($r['typed_child'] !== '') child <strong>{{ $r['typed_child'] }}</strong>@endif
                @if($r['typed_school'] !== '') , school <strong>{{ $r['typed_school'] }}</strong>@endif
                @if($r['typed_class'] !== '') , class <strong>{{ $r['typed_class'] }}</strong>@endif
                — this does not match one student exactly.
            </p>
        @endif
        <p class="wa-lr-meta">
            <span>Requested {{ $r['requested_at']?->diffForHumans() }} · {{ $r['requested_at']?->timezone(config('app.timezone'))->format('H:i') }}</span>
            @if($r['time_left'] !== null)
                <span class="wa-lr-time {{ $r['expires_at']->lte(now()->addMinutes(5)) ? 'wa-lr-time--soon' : '' }}" data-testid="wa-link-request-time-left">{{ $r['time_left'] }}</span>
            @endif
        </p>
    </div>
    <div class="wa-lr-actions">
        @if(count($r['candidates']) > 1 && ! $r['student_id'])
            <label class="wa-lr-pick">
                <span class="sr-only">Student</span>
                <select wire:model="studentChoice.{{ $r['id'] }}" data-testid="wa-link-request-student">
                    <option value="">Choose student</option>
                    @foreach($r['candidates'] as $candidate)
                        <option value="{{ $candidate['id'] }}">{{ $candidate['name'] }}@if($candidate['class'] !== '') — {{ trim($candidate['class'].' '.$candidate['stream']) }}@endif</option>
                    @endforeach
                </select>
            </label>
        @endif
        <div class="wa-lr-actions__row" x-show="!confirming">
            @if($r['student_id'] || count($r['candidates']) > 1)
                <x-button variant="success" size="lg" class="wa-lr-btn" wire:click="approve({{ $r['id'] }})" wire:loading.attr="disabled" data-testid="wa-approve">Approve</x-button>
            @else
                <x-button variant="primary" size="lg" class="wa-lr-btn" :href="url('/admin/approvals')">Choose student</x-button>
            @endif
            <x-button variant="outline" size="lg" class="wa-lr-btn wa-lr-btn--reject" x-on:click="confirming = true" data-testid="wa-reject">Reject</x-button>
        </div>
        <div class="wa-lr-confirm" x-show="confirming" x-cloak role="group" aria-label="Confirm reject">
            <p class="wa-lr-confirm__text">Reject this request?</p>
            <div class="wa-lr-actions__row">
                <x-button variant="danger" size="lg" class="wa-lr-btn" wire:click="reject({{ $r['id'] }})" wire:loading.attr="disabled" data-testid="wa-reject-confirm">Yes, reject</x-button>
                <x-button variant="ghost" size="lg" class="wa-lr-btn" x-on:click="confirming = false">Cancel</x-button>
            </div>
        </div>
    </div>
</article>
