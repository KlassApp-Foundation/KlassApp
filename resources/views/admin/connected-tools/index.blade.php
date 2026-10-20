{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Connected tools',
    'subtitle' => 'A tool shows Connected only after this school has connected it.',
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
</div>
@endsection
