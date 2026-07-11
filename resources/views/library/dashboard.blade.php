{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.library.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--library px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Library',
        'subtitle' => 'Monitor books, lending activity, categories, and updates from one organized library console.',
    ])

    @include('partials.message')

    {{-- KPI Cards --}}
    <div class="dashboard-kpi-grid">
        <x-ds-kpi-card icon="book" value="{{ $dashboard['bookCount'] ?? 0 }}" label="Total Books" color="blue" link="{{ url('/library/books/index') }}" />
        <x-ds-kpi-card icon="check" value="{{ $dashboard['booklendingCount'] ?? 0 }}" label="Active Lendings" color="green" />
        <x-ds-kpi-card icon="users" value="{{ $dashboard['cardHolderCount'] ?? 0 }}" label="Library Cards" color="purple" />
        <x-ds-kpi-card icon="book" value="{{ $dashboard['categoryCount'] ?? 0 }}" label="Categories" color="amber" />
    </div>

    {{-- Main grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Overdue Books --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-red);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Overdue Books
                </h2>
            </div>
            <div class="p-5">
                @if(isset($dashboard['booklendings']) && count($dashboard['booklendings']) > 0)
                    <div class="space-y-3">
                        @foreach($dashboard['booklendings'] as $lending)
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium" style="color: var(--d-text);">{{ $lending->book->name ?? 'Unknown' }}</p>
                                    <p class="text-xs mt-0.5" style="color: var(--d-muted);">
                                        Borrowed {{ $lending->created_at ? \Carbon\Carbon::parse($lending->created_at)->format('d M Y') : '—' }}
                                        @if($lending->member) · {{ $lending->member->name ?? '' }} @endif
                                    </p>
                                </div>
                                <span class="ds-badge ds-badge-sm ds-badge-rejected">Overdue</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No overdue books. All caught up!</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Notice Board --}}
        <div class="ds-card ds-card-padding-none">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                    <svg class="w-5 h-5 inline mr-2" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Notice Board
                </h2>
            </div>
            <div class="p-5">
                @if(isset($dashboard['noticeboard']) && count($dashboard['noticeboard']) > 0)
                    <div class="space-y-4">
                        @foreach($dashboard['noticeboard'] as $notice)
                            <div class="pb-4 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="ds-badge ds-badge-sm ds-badge-active">{{ $notice->title }}</span>
                                    <span class="ds-badge ds-badge-sm ds-badge-info">{{ date('d M Y', strtotime($notice->publish_date)) }}</span>
                                </div>
                                <p class="text-sm" style="color: var(--d-text);">{!! $notice->description !!}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <p class="text-sm" style="color: var(--d-muted);">No notices published yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Expiring Cards --}}
    @if(isset($dashboard['expiringCards']) && count($dashboard['expiringCards']) > 0)
    <div class="ds-card ds-card-padding-none mt-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">
                <svg class="w-5 h-5 inline mr-2" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Expiring Library Cards
            </h2>
        </div>
        <div class="p-5">
            <div class="space-y-3">
                @foreach($dashboard['expiringCards'] as $card)
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--d-text);">{{ $card->holder_name ?? $card->user->name ?? '—' }}</p>
                            <p class="text-xs" style="color: var(--d-muted);">Expires: {{ $card->valid_until ? \Carbon\Carbon::parse($card->valid_until)->format('d M Y') : '—' }}</p>
                        </div>
                        <span class="ds-badge ds-badge-sm ds-badge-warning">Expiring Soon</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
