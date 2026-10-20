{{-- SPDX-License-Identifier: MIT --}}
@extends($layout)
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" style="background:#FFFFFF;color:#0F172A">

@include('layouts.partials.page-header', [
    'title' => 'My connected tools',
    'subtitle' => 'These are your own accounts. A Google tool shows Connected only after you connect it, and only if your school allows it.',
])

@include('partials.message')

<ul class="mt-4 space-y-3" data-testid="connected-tools-list" style="list-style:none;padding:0;margin-top:16px">
    @foreach ($tools as $tool)
        <li class="ds-card ds-card-padding-default" data-testid="connected-tool-{{ $tool['key'] }}" style="background:#FFFFFF;color:#0F172A">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <img src="{{ asset($tool['icon']) }}" alt="" width="40" height="40" data-testid="connected-tool-icon-{{ $tool['key'] }}" style="width:40px;height:40px;object-fit:contain;flex:none;background:transparent">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold" style="color:#0F172A">{{ $tool['label'] }}</span>
                        <span class="ds-badge {{ $tool['status'] === 'connected' ? 'ds-badge-active' : ($tool['status'] === 'needs_attention' ? 'ds-badge-warning' : 'ds-badge-inactive') }}" data-testid="connected-tool-status-{{ $tool['key'] }}">{{ $tool['status_label'] }}</span>
                    </div>
                    <p class="text-sm mt-1" style="color:#64748B">{{ $tool['summary'] }}</p>
                    @if ($tool['status'] === 'needs_attention' && $tool['attention'])
                        <p class="text-sm mt-1" style="color:#B45309" data-testid="connected-tool-attention-{{ $tool['key'] }}">{{ $tool['attention'] }}</p>
                    @endif
                    @if (! empty($tool['note']))
                        <p class="text-sm mt-1" style="color:#64748B" data-testid="connected-tool-note-{{ $tool['key'] }}">{{ $tool['note'] }}</p>
                    @endif
                    @if ($tool['status'] === 'connected' && $tool['account_email'])
                        <p class="text-sm mt-1" style="color:#64748B" data-testid="connected-tool-email-{{ $tool['key'] }}">Account: {{ $tool['account_email'] }}</p>
                    @endif
                    @if ($tool['status'] === 'connected' && $tool['last_synced_at'])
                        <p class="text-sm mt-1" style="color:#64748B">
                            <time datetime="{{ $tool['last_synced_at']->utc()->toIso8601String() }}" data-testid="connected-tool-sync-{{ $tool['key'] }}">Last sync {{ $tool['last_synced_at']->timezone(config('app.timezone'))->format('j M Y, H:i') }}</time>
                        </p>
                    @endif
                </div>
                @if ($tool['action'] && $tool['action_url'])
                    <div class="shrink-0">
                        @if ($tool['action'] === 'disconnect')
                            <form method="POST" action="{{ $tool['action_url'] }}">
                                @csrf
                                <button type="submit" class="ds-btn ds-btn-danger ds-btn-md" data-testid="connected-tool-disconnect-{{ $tool['key'] }}">Disconnect</button>
                            </form>
                        @elseif ($tool['action'] === 'settings')
                            <a href="{{ $tool['action_url'] }}" class="ds-btn ds-btn-outline ds-btn-md" data-testid="connected-tool-settings-{{ $tool['key'] }}">Settings</a>
                        @else
                            <a href="{{ $tool['action_url'] }}" class="ds-btn ds-btn-primary ds-btn-md" data-testid="connected-tool-connect-{{ $tool['key'] }}">Connect</a>
                        @endif
                    </div>
                @endif
            </div>
        </li>
    @endforeach
</ul>

@if (is_array($allowRows))
    <div class="mt-8" data-testid="google-allow-list">
        <h2 style="color:#0F172A;font-size:18px;font-weight:700;margin:0 0 4px">Tools this school allows</h2>
        <p style="color:#64748B;font-size:14px;margin:0 0 14px">This list does not show who connected, or their Google account.</p>
        <ul style="list-style:none;padding:0;margin:0">
            @foreach ($allowRows as $tool)
                <li class="ds-card ds-card-padding-default" data-testid="google-allow-{{ $tool['key'] }}" style="background:#FFFFFF;color:#0F172A;margin-bottom:8px">
                    <span class="font-semibold" style="color:#0F172A">{{ $tool['label'] }}</span>
                    <span class="block text-sm" style="color:#64748B" data-testid="google-allow-count-{{ $tool['key'] }}">{{ $tool['count'] }} connected</span>
                    <form method="POST" action="{{ route('admin.google.allow', ['connector' => $tool['key']]) }}">
                        @csrf
                        <input type="hidden" name="allowed" value="{{ $tool['allowed'] ? '0' : '1' }}">
                        <button type="submit" class="ds-btn ds-btn-md {{ $tool['allowed'] ? 'ds-btn-danger' : 'ds-btn-primary' }}" data-testid="google-allow-toggle-{{ $tool['key'] }}">
                            {{ $tool['allowed'] ? 'Turn off' : 'Allow' }}
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    </div>
@endif
</div>
@endsection
