{{-- Admin dashboard: WhatsApp link requests card. Refreshes itself every few seconds. --}}
<section class="ds-card wa-dash-card" wire:poll.{{ $pollSeconds }}s data-testid="dashboard-whatsapp-card" data-poll-seconds="{{ $pollSeconds }}" aria-labelledby="wa-dash-title">
    <div class="wa-dash-card__head">
        <div class="wa-dash-card__title-wrap">
            <x-brand.whatsapp class="wa-dash-card__mark" />
            <div>
                <h2 class="wa-lr-section__title" id="wa-dash-title">WhatsApp link requests</h2>
                <p class="wa-dash-card__sub">New BIND requests appear here by themselves.</p>
            </div>
        </div>
        <div class="wa-dash-card__count" data-testid="dashboard-whatsapp-count">
            <span class="wa-dash-card__count-num">{{ $count }}</span>
            <span class="wa-dash-card__count-label">{{ $count === 1 ? 'request waiting' : 'requests waiting' }}</span>
        </div>
    </div>

    @include('livewire.admin.partials.link-notice')

    @if($requests->isEmpty())
        <div class="wa-lr-empty wa-lr-empty--inline" data-testid="dashboard-whatsapp-empty">
            <p>{{ $isDemoSchool ? 'No requests yet. Scan the QR on /demo to try it' : 'No link requests waiting.' }}</p>
        </div>
    @else
        <div class="wa-lr-list wa-lr-list--compact">
            @foreach($requests as $r)
                @include('livewire.admin.partials.link-request-card', ['r' => $r])
            @endforeach
        </div>
    @endif

    <div class="wa-dash-card__foot">
        <x-button variant="outline" size="lg" :href="url('/admin/whatsapp/dashboard')" data-testid="dashboard-whatsapp-link">Open the WhatsApp page →</x-button>
    </div>
</section>
