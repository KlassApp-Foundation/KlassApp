{{-- WhatsApp page, top: Link requests + Linked now. Refreshes itself every few seconds. --}}
<div class="wa-lr" wire:poll.{{ $pollSeconds }}s data-testid="wa-link-requests" data-poll-seconds="{{ $pollSeconds }}">
    @include('livewire.admin.partials.link-notice')

    <section class="wa-lr-section" aria-labelledby="wa-lr-requests-title">
        <div class="wa-lr-section__head">
            <h2 class="wa-lr-section__title" id="wa-lr-requests-title">Link requests</h2>
            <span class="wa-lr-count" data-testid="wa-link-requests-count">{{ $requests->count() }} waiting</span>
        </div>
        <p class="ds-section-subtitle wa-lr-section__sub">Parents asking to link a child, by KlassApp number, form, or demo. This list updates by itself.</p>

        @if($requests->isEmpty())
            <div class="ds-card wa-lr-empty" data-testid="wa-link-requests-empty">
                <p>{{ $isDemoSchool ? 'No requests yet. Scan the QR on /demo to try it.' : 'No link requests waiting.' }}</p>
            </div>
        @else
            <x-fold-list tag="div" class="wa-lr-list" name="link-requests">
                @foreach($requests as $r)
                    @include('livewire.admin.partials.link-request-card', ['r' => $r])
                @endforeach
            </x-fold-list>
        @endif
    </section>

    <section class="wa-lr-section" aria-labelledby="wa-lr-linked-title">
        <div class="wa-lr-section__head">
            <h2 class="wa-lr-section__title" id="wa-lr-linked-title">Linked now</h2>
            <span class="wa-lr-count wa-lr-count--linked" data-testid="wa-linked-count">{{ $linked->count() }} {{ \Illuminate\Support\Str::plural('number', $linked->count()) }}</span>
        </div>
        <p class="ds-section-subtitle wa-lr-section__sub">Numbers linked to a student. A demo link shows the time left in its session. A school link stays until you unlink it.</p>

        @if($linked->isEmpty())
            <div class="ds-card wa-lr-empty wa-lr-empty--quiet" data-testid="wa-linked-empty">
                <p>No numbers are linked right now.@unless($isDemoSchool) <a href="{{ url('/admin/whatsapp/parents') }}">See linked parents</a>@endunless</p>
            </div>
        @else
            <x-fold-list tag="div" class="wa-lr-list" name="linked-numbers">
                @foreach($linked as $l)
                    <article class="ds-card wa-lr-card" wire:key="linked-{{ $l['id'] }}" data-testid="wa-linked" data-demo="{{ $l['demo'] ? '1' : '0' }}" x-data="{ confirming: false }">
                        <div class="wa-lr-card__main">
                            <p class="wa-lr-parent">
                                <span class="wa-lr-parent__name">{{ $l['name'] }}</span>
                                @if($l['demo'])
                                    <span class="wa-lr-chip wa-lr-chip--source">Demo</span>
                                @endif
                            </p>
                            <p class="wa-lr-phone">{{ $l['phone_display'] }}</p>
                            <p class="wa-lr-student">
                                <span class="wa-lr-student__name">{{ $l['student_name'] }}</span>
                                @if($l['class_name'] !== '')
                                    <span class="wa-lr-chip">{{ $l['class_name'] }}</span>
                                @endif
                                @if($l['stream'] !== '')
                                    <span class="wa-lr-chip">{{ $l['stream'] }}</span>
                                @endif
                            </p>
                            <p class="wa-lr-meta">
                                <span>Linked {{ $l['linked_at']?->diffForHumans() }} · {{ $l['linked_at']?->timezone(config('app.timezone'))->format('H:i') }}</span>
                                @if($l['time_left'] !== null)
                                    <span class="wa-lr-time {{ $l['expires_at']->lte(now()->addMinutes(5)) ? 'wa-lr-time--soon' : '' }}" data-testid="wa-linked-time-left">{{ $l['time_left'] }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="wa-lr-actions">
                            <div class="wa-lr-actions__row" x-show="!confirming">
                                <x-button variant="outline" size="lg" class="wa-lr-btn wa-lr-btn--reject" x-on:click="confirming = true" data-testid="wa-unlink">Unlink</x-button>
                            </div>
                            <div class="wa-lr-confirm" x-show="confirming" x-cloak role="group" aria-label="Confirm unlink">
                                <p class="wa-lr-confirm__text">Unlink this number?</p>
                                <div class="wa-lr-actions__row">
                                    <x-button variant="danger" size="lg" class="wa-lr-btn" wire:click="unlink({{ $l['id'] }})" wire:loading.attr="disabled" data-testid="wa-unlink-confirm">Yes, unlink</x-button>
                                    <x-button variant="ghost" size="lg" class="wa-lr-btn" x-on:click="confirming = false">Cancel</x-button>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </x-fold-list>
        @endif
    </section>
</div>
