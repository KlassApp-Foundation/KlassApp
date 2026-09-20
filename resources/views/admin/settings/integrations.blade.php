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
                 class="border rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                 style="background: #FFFFFF; color: #1F2937;">
                <div class="flex items-center gap-3">
                    @if ($c['type'] === 'slack')
                        <x-brand.slack />
                    @else
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-500 font-semibold">
                            {{ strtoupper(substr($c['label'], 0, 1)) }}
                        </span>
                    @endif
                    <div>
                        <div class="font-semibold flex items-center gap-2">
                            {{ $c['label'] }}
                            @if ($c['connected'])
                                <span data-testid="integration-status-{{ $c['type'] }}"
                                      class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium">Connected</span>
                            @else
                                <span data-testid="integration-status-{{ $c['type'] }}"
                                      class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">Not connected</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500">
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
                                        class="px-4 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 text-sm font-medium">
                                    Disconnect
                                </button>
                            </form>
                        @elseif ($slackConnectable)
                            <a href="{{ url('mcp/slack/connect') }}"
                               data-testid="integration-connect-slack"
                               class="px-4 py-2 rounded-lg text-white text-sm font-medium"
                               style="background: #15803D;">
                                Connect Slack
                            </a>
                        @else
                            <span class="text-sm text-gray-400" data-testid="integration-connect-unavailable">
                                Connect unavailable (instance not in live mode)
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-500">No integrations available yet.</p>
        @endforelse
    </div>

    <div class="mt-6 text-sm text-gray-500 max-w-2xl">
        <p><strong>Safety:</strong> Toshi reads (channels, search, history) run immediately and are fully audited.
        Writes (posting messages) always pause for a human approval before executing — the approver is recorded in the audit log.</p>
        <p class="mt-2">Disconnecting blocks Toshi from reaching the workspace. Re-connecting re-authorizes it.</p>
    </div>
</div>
</div>
</div>
@endsection
