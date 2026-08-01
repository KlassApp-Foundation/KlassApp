{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')
@section('content')
    <div class="relative px-4 py-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Platform ops</p>
                <h1 class="text-2xl font-bold" style="color:#0F172A;">Tool approval review</h1>
                <p class="text-sm text-gray-600">Conversation {{ $conversation->id }}</p>
            </div>
            <a href="{{ route('superadmin.dashboard') }}" class="text-sm font-semibold text-gray-700 underline">Back to dashboard</a>
        </div>

        <livewire:superadmin.toshi.platform-approval-gate :conversation="$conversation" />
    </div>
@endsection
