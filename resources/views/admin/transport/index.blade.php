@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Transport',
        'subtitle' => 'Manage school transport routes, vehicles, and drivers.',
    ])
    <div class="bg-white rounded-lg border border-gray-200 p-8 shadow-sm mt-4 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M5 17h14M5 17a2 2 0 01-2-2V7a2 2 0 012-2h10l3 4h2a2 2 0 012 2v4a2 2 0 01-2 2M5 17a2 2 0 102 2 2 2 0 00-2-2zm12 0a2 2 0 102 2 2 2 0 00-2-2z"/>
            </svg>
        </div>
        <p class="text-gray-500 text-sm">Transport module coming soon.</p>
    </div>
</div>
@endsection
