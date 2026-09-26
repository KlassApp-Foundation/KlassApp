{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--superadmin">
    <div class="ds-page-head">
        <h1 class="ds-page-head-title">Mail List</h1>
        <p class="ds-page-head-sub">{{ $subscribers->total() }} subscribers</p>
    </div>

    <x-table :headers="['Email', 'Name', 'Subscribed']">
        @forelse($subscribers as $s)
            <tr>
                <td>{{ $s->email }}</td>
                <td>{{ $s->name ?? '—' }}</td>
                <td>{{ $s->created_at->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center py-8 text-gray-400">No subscribers yet.</td></tr>
        @endforelse
    </x-table>

    <div class="mt-4">{{ $subscribers->links() }}</div>
</div>
@endsection
