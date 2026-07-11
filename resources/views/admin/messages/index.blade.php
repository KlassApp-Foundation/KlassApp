@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Messaging</h1>
            <p class="dashboard-subtitle">Send messages to students, teachers, or review sent messages.</p>
        </div>
    </div>

    @include('partials.message')

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Message Students --}}
        <a href="{{ url('admin/students') }}"
           class="ds-card ds-card-hover p-6 flex flex-col items-center text-center gap-3 no-underline"
           style="color: inherit;">
            <div class="ds-kpi-icon-wrap" style="background: rgba(30,111,217,0.10); width: 56px; height: 56px;">
                <svg class="w-7 h-7" style="color: var(--d-blue);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                </svg>
            </div>
            <h3 class="ds-card-title text-lg">Message Students</h3>
            <p class="text-sm text-muted">Select students from the list and send them a message.</p>
            <span class="ds-btn ds-btn-primary ds-btn-sm mt-2">Go to Students</span>
        </a>

        {{-- Message Teachers --}}
        <a href="{{ url('admin/teachers') }}"
           class="ds-card ds-card-hover p-6 flex flex-col items-center text-center gap-3 no-underline"
           style="color: inherit;">
            <div class="ds-kpi-icon-wrap" style="background: rgba(34,197,94,0.10); width: 56px; height: 56px;">
                <svg class="w-7 h-7" style="color: var(--d-green);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h3 class="ds-card-title text-lg">Message Teachers</h3>
            <p class="text-sm text-muted">Select teachers from the list and send them a message.</p>
            <span class="ds-btn ds-btn-primary ds-btn-sm mt-2">Go to Teachers</span>
        </a>

        {{-- Sent Messages --}}
        <a href="{{ url('admin/sentmessages') }}"
           class="ds-card ds-card-hover p-6 flex flex-col items-center text-center gap-3 no-underline"
           style="color: inherit;">
            <div class="ds-kpi-icon-wrap" style="background: rgba(217,119,6,0.10); width: 56px; height: 56px;">
                <svg class="w-7 h-7" style="color: var(--d-amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="ds-card-title text-lg">Sent Messages</h3>
            <p class="text-sm text-muted">Review previously sent messages and delivery history.</p>
            <span class="ds-btn ds-btn-outline ds-btn-sm mt-2">View History</span>
        </a>
    </div>
</div>
@endsection
