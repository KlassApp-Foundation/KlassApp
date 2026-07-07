{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--superadmin">
    <div class="ds-page-head">
        <h1 class="ds-page-head-title">Mail List</h1>
        <p class="ds-page-head-sub">{{ $subscribers->total() }} subscribers</p>
    </div>
    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Subscribed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $s)
                    <tr>
                        <td>{{ $s->email }}</td>
                        <td>{{ $s->name ?? '—' }}</td>
                        <td>{{ $s->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-8 text-gray-400">No subscribers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $subscribers->links() }}</div>
</div>
@endsection
