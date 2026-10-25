{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="ds-page" data-testid="admin-class-setup">
    <div class="ds-page-head">
        <div>
            <h1 class="admin-h1">Class setup</h1>
            <p class="ds-page-sub">Where each class is with its learners, subjects and subject teachers.</p>
        </div>
        <form method="POST" action="{{ route('admin.class-setup.staff-link.regenerate') }}">
            @csrf
            <button type="submit" class="ds-btn ds-btn-primary ds-btn-md" data-testid="staff-link-regenerate">
                {{ $hasStaffLink ? 'Revoke and regenerate' : 'New staff link' }}
            </button>
        </form>
    </div>

    @if($issued)
        <div class="ds-alert ds-alert-success" data-testid="teacher-setup-link">
            <p class="mb-1"><strong>Setup link for that teacher</strong> — share it from your own WhatsApp or copy it:</p>
            <code class="break-all">{{ $issued }}</code>
        </div>
    @endif

    @if($staffUrl)
        <section class="ds-card ds-card-pad" data-testid="staff-link-card">
            <h2 class="ds-section-title">Staff link</h2>
            <p class="ds-muted">Valid for 7 days. Share it in the staff group; only teachers already on your list get in, everyone else waits for your approval. Revoke and regenerate stops the previous link immediately.</p>
            <div class="flex items-start gap-6 flex-wrap">
                @if($qrSvg)<div class="staff-qr">{!! $qrSvg !!}</div>@endif
                <div class="min-w-0">
                    <code class="break-all block mb-3">{{ $staffUrl }}</code>
                    <a class="ds-btn ds-btn-outline ds-btn-md" target="_blank" rel="noopener"
                       href="https://wa.me/?text={{ rawurlencode('Staff setup link for '.$school->name.': '.$staffUrl) }}">Share on WhatsApp</a>
                </div>
            </div>
        </section>
    @endif

    @if($requests->isNotEmpty())
        <section class="ds-card ds-card-pad">
            <h2 class="ds-section-title">Waiting for approval</h2>
            <table class="ds-table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th></th></tr></thead>
                <tbody>
                @foreach($requests as $request)
                    <tr>
                        <td>{{ $request->name ?: '—' }}</td>
                        <td>{{ $request->email ?: '—' }}</td>
                        <td>{{ $request->phone ?: '—' }}</td>
                        <td class="flex gap-2">
                            <form method="POST" action="{{ route('admin.class-setup.approve', $request) }}">@csrf
                                <button class="ds-btn ds-btn-primary ds-btn-sm">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.class-setup.reject', $request) }}">@csrf
                                <button class="ds-btn ds-btn-outline ds-btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="ds-card ds-card-pad">
        <h2 class="ds-section-title">Classes</h2>
        <table class="ds-table" data-testid="class-progress">
            <thead><tr><th>Class</th><th>Students</th><th>Subjects with a teacher</th><th>Class teacher</th></tr></thead>
            <tbody>
            @forelse($classes as $class)
                <tr>
                    <td>{{ $class['name'] }}</td>
                    <td>{{ $class['students'] }}</td>
                    <td>{{ $class['subjects_with_teacher'] }} / {{ $class['subjects'] }}</td>
                    <td>{{ $class['class_teacher'] ?? 'waiting for class teacher' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="ds-muted">No classes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
