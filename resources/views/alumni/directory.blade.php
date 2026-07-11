{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.alumni.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--alumni px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Alumni Directory',
        'subtitle' => 'Connect with fellow graduates from your school.',
    ])

    @if($alumni->isEmpty())
        <div class="ds-card text-center py-12 mt-4">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <p class="text-gray-400 text-sm">No other alumni registered yet.</p>
            <a href="{{ url('/alumni/dashboard') }}" class="text-blue-600 text-sm mt-2 inline-block hover:underline">← Back to Dashboard</a>
        </div>
    @else
        <div class="ds-card mt-4">
            <div class="ds-table-wrap">
                <table class="ds-table ds-table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alumni as $member)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background: rgba(30,111,217,0.10);">
                                            <span class="text-xs font-semibold" style="color: var(--d-blue);">
                                                {{ $member->userprofile ? strtoupper(substr($member->userprofile->firstname, 0, 1) . substr($member->userprofile->lastname ?? '', 0, 1)) : '?' }}
                                            </span>
                                        </div>
                                        <span class="font-medium text-sm">{{ $member->name }}</span>
                                    </div>
                                </td>
                                <td class="text-sm">
                                    @if($member->mobile_no)
                                        <a href="tel:{{ $member->mobile_no }}" class="hover:underline" style="color: var(--d-blue);">{{ $member->mobile_no }}</a>
                                    @else
                                        <span class="text-xs" style="color: var(--d-muted);">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="ds-badge ds-badge-sm ds-badge-active">Active</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">
            {{ $alumni->links() }}
        </div>
    @endif
</div>
@endsection
