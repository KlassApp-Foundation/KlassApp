{{-- Hidden poller. No link-request panel. wire:poll keeps the toast and the sidebar badge current. --}}
<div wire:poll.{{ $pollSeconds }}s data-testid="dashboard-whatsapp-poll" data-poll-seconds="{{ $pollSeconds }}" hidden aria-hidden="true"></div>
