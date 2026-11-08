{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Integrations',
    'subtitle' => app(\App\Services\Toshi\ToshiUiSwitch::class)->enabled()
        ? 'Connect your school\'s workspace apps so Toshi can work in them. Reads are always audited; every write pauses for your approval.'
        : 'Connect your school\'s workspace apps. Reads are always audited; every write pauses for your approval.',
])

@include('layouts.partials.settings-nav')

@include('partials.message')

<div class="relative mt-4">
<div class="w-full main-content">
    <div data-testid="integrations-list" class="space-y-4">
        @forelse ($connectors as $c)
            <div data-testid="integration-card-{{ $c['type'] }}"
                 class="ds-card ds-card-padding-default flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    @if ($c['type'] === 'slack')
                        <x-brand.slack />
                    @else
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-semibold" style="background: var(--d-surface, #FAFAF5); color: var(--d-text-secondary, #64748B);">
                            {{ strtoupper(substr($c['label'], 0, 1)) }}
                        </span>
                    @endif
                    <div>
                        <div class="font-semibold flex items-center gap-2" style="color: var(--d-dark, #0F172A);">
                            {{ $c['label'] }}
                            @if ($c['connected'])
                                <span data-testid="integration-status-{{ $c['type'] }}"
                                      class="ds-badge ds-badge-active">Connected</span>
                            @else
                                <span data-testid="integration-status-{{ $c['type'] }}"
                                      class="ds-badge ds-badge-inactive">Not connected</span>
                            @endif
                        </div>
                        <div class="text-sm" style="color: var(--d-text-secondary, #64748B);">
                            @if ($c['connected'])
                                Workspace: {{ $c['team_name'] ?? 'connected workspace' }}
                                &middot; Write mode: {{ $c['write_mode'] }}
                                @if ($c['last_used_at'])
                                    &middot; Last used {{ $c['last_used_at']->diffForHumans() }}
                                @endif
                            @else
                                @toshiUi
                                    Toshi can list channels, search messages, and read history once connected.
                                    Posting requires your approval each time.
                                @else
                                    Once connected, channels can be listed, messages searched, and history read.
                                    Posting requires your approval each time.
                                @endtoshiUi
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if ($c['type'] === 'slack')
                        @if ($c['connected'])
                            <form method="POST" action="{{ route('admin.settings.integrations.disconnect', ['type' => 'slack']) }}">
                                @csrf
                                <button type="submit"
                                        data-testid="integration-disconnect-slack"
                                        class="ds-btn ds-btn-danger ds-btn-md">
                                    Disconnect
                                </button>
                            </form>
                        @elseif ($slackConnectable)
                            <a href="{{ url('mcp/slack/connect') }}"
                               data-testid="integration-connect-slack"
                               class="ds-btn ds-btn-primary ds-btn-md">
                                Connect Slack
                            </a>
                        @else
                            <span class="text-sm" style="color: var(--d-muted, #94A3B8);" data-testid="integration-connect-unavailable">
                                Connect unavailable (instance not in live mode)
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <p style="color: var(--d-text-secondary, #64748B);">No integrations available yet.</p>
        @endforelse
    </div>

    <div class="dashboard-connected-tools mt-8" data-testid="connected-tools-card">
        <h2 style="color:#0F172A;font-size:18px;font-weight:700;margin:0 0 4px">Connected tools</h2>
        <p style="color:#64748B;font-size:14px;margin:0 0 14px">A tool shows Live only after you have connected your own Google account.</p>
        <ul class="dashboard-connected-list">
            @foreach ($googleConnectors as $tool)
                <li class="dashboard-connected-row ds-card ds-card-padding-default" data-testid="google-connector-card-{{ $tool['key'] }}" style="background:#FFFFFF;color:#0F172A">
                    <span class="dashboard-connected-label">
                        <span>
                            <span class="font-semibold" style="color:#0F172A">{{ $tool['label'] }}</span>
                            @if ($tool['live'])
                                <span class="ds-badge ds-badge-active" data-testid="google-connector-live-{{ $tool['key'] }}">Live</span>
                            @else
                                <span class="ds-badge ds-badge-inactive" data-testid="google-connector-status-{{ $tool['key'] }}">Not connected</span>
                            @endif
                            <span class="block text-sm" style="color:#64748B">{{ $tool['why'] }}</span>
                            @if ($tool['live'] && $tool['email'])
                                <span class="block text-sm" style="color:#64748B" data-testid="google-connector-email-{{ $tool['key'] }}">Account: {{ $tool['email'] }}</span>
                            @endif
                            @if (! ($tool['allowed'] ?? false))
                                <span class="block text-sm" style="color:#64748B" data-testid="google-connector-blocked-{{ $tool['key'] }}">Your school has not turned this on.</span>
                            @endif
                        </span>
                    </span>
                    @if ($tool['live'])
                        <form method="POST" action="{{ route('admin.google.disconnect', ['connector' => $tool['key']]) }}">
                            @csrf
                            <button type="submit" class="ds-btn ds-btn-danger ds-btn-md" data-testid="google-connector-disconnect-{{ $tool['key'] }}">Disconnect</button>
                        </form>
                    @elseif ($tool['configured'] && ($tool['allowed'] ?? false))
                        <a href="{{ route('admin.google.connect', ['connector' => $tool['key']]) }}" class="ds-btn ds-btn-primary ds-btn-md" data-testid="google-connector-connect-{{ $tool['key'] }}">Connect</a>
                    @else
                        <span class="text-sm" style="color:#94A3B8" data-testid="google-connector-unconfigured-{{ $tool['key'] }}">Connect unavailable</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-8" data-testid="google-allow-list">
        <h2 style="color:#0F172A;font-size:18px;font-weight:700;margin:0 0 4px">Connectors this school allows</h2>
        <p style="color:#64748B;font-size:14px;margin:0 0 14px">People at this school can connect a tool only after you turn it on. This list does not show who connected, or their Google account.</p>
        <ul class="dashboard-connected-list">
            @foreach ($googleAllow as $tool)
                <li class="dashboard-connected-row ds-card ds-card-padding-default" data-testid="google-allow-{{ $tool['key'] }}" style="background:#FFFFFF;color:#0F172A">
                    <span>
                        <span class="font-semibold" style="color:#0F172A">{{ $tool['label'] }}</span>
                        <span class="block text-sm" style="color:#64748B" data-testid="google-allow-count-{{ $tool['key'] }}">{{ $tool['count'] }} connected</span>
                    </span>
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

    <div class="mt-6 text-sm max-w-2xl" style="color: var(--d-text-secondary, #64748B);">
        @toshiUi
            <p><strong>Safety:</strong> Toshi reads (channels, search, history) run immediately and are fully audited.
            Writes (posting messages) always pause for a human approval before executing — the approver is recorded in the audit log.</p>
            <p class="mt-2">Disconnecting blocks Toshi from reaching the workspace. Re-connecting re-authorizes it.</p>
        @else
            <p><strong>Safety:</strong> Reads (channels, search, history) run immediately and are fully audited.
            Writes (posting messages) always pause for a human approval before executing — the approver is recorded in the audit log.</p>
            <p class="mt-2">Disconnecting blocks access to the workspace. Re-connecting re-authorizes it.</p>
        @endtoshiUi
    </div>
</div>
</div>
</div>
@endsection
