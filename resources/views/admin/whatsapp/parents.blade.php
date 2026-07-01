{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="container-body">
    <h1 class="admin-h1 my-3 flex items-center">📱 WhatsApp Linked Parents</h1>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white shadow rounded p-3 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-500">Total Numbers</div>
        </div>
        <div class="bg-white shadow rounded p-3 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $stats['linked'] }}</div>
            <div class="text-xs text-gray-500">Linked to Parents</div>
        </div>
        <div class="bg-white shadow rounded p-3 text-center">
            <div class="text-2xl font-bold text-amber-600">{{ $stats['opted_in'] }}</div>
            <div class="text-xs text-gray-500">Opted In</div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="bg-white shadow rounded p-3 mb-4">
        <form method="GET" class="flex gap-3 items-center">
            <input type="text" name="search" placeholder="Search by phone or parent name..." value="{{ request('search') }}" class="border rounded p-2 text-sm flex-1">
            <select name="linked" class="border rounded p-2 text-sm">
                <option value="">All</option>
                <option value="yes" {{ request('linked') === 'yes' ? 'selected' : '' }}>Linked</option>
                <option value="no" {{ request('linked') === 'no' ? 'selected' : '' }}>Unlinked</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Filter</button>
            <a href="{{ url('/admin/whatsapp/parents') }}" class="text-gray-500 text-sm hover:text-gray-700">Reset</a>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow rounded overflow-hidden">
        @if($users->isEmpty())
            <div class="p-6 text-center text-gray-500">No WhatsApp users found.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 text-left">
                        <th class="p-3">Phone Number</th>
                        <th class="p-3">Linked Parent</th>
                        <th class="p-3">Linked Children</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Verified</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $wa)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3 font-mono text-xs">{{ $wa->phone }}</td>
                        <td class="p-3">{{ $wa->user?->name ?? '—' }}</td>
                        <td class="p-3 text-xs">
                            @if($wa->user && $wa->user->children->isNotEmpty())
                                {{ $wa->user->children->pluck('name')->take(3)->implode(', ') }}
                                @if($wa->user->children->count() > 3)
                                    +{{ $wa->user->children->count() - 3 }} more
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-3">
                            @if($wa->opted_in)
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Opted In</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs">Opted Out</span>
                            @endif
                        </td>
                        <td class="p-3 text-xs">{{ $wa->verified_at ? \Carbon\Carbon::parse($wa->verified_at)->format('d M Y') : '—' }}</td>
                        <td class="p-3">
                            @if($wa->user_id)
                            <form method="POST" action="{{ url('/admin/whatsapp/parents/unlink/' . $wa->id) }}" onsubmit="return confirm('Unlink this parent?')">
                                @csrf
                                <button class="text-red-500 hover:text-red-700 text-xs">Unlink</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($users->hasPages())
            <div class="p-3">{{ $users->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection