{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Integrations',
    'subtitle' => 'Connect your school\'s workspace apps so Toshi can work in them. Reads are always audited; every write pauses for your approval.',
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
                                Toshi can list channels, search messages, and read history once connected.
                                Posting requires your approval each time.
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

    <div class="mt-6 text-sm max-w-2xl" style="color: var(--d-text-secondary, #64748B);">
        <p><strong>Safety:</strong> Toshi reads (channels, search, history) run immediately and are fully audited.
        Writes (posting messages) always pause for a human approval before executing — the approver is recorded in the audit log.</p>
        <p class="mt-2">Disconnecting blocks Toshi from reaching the workspace. Re-connecting re-authorizes it.</p>
    </div>
</div>
</div>
</div>
@endsection
