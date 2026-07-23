{{-- @extends('layouts.admin.layout')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Subscriptions</h1>
            <p class="text-gray-600 mt-1">Manage all subscription records across the platform</p>
        </div>

        <a href="{{ route('admin.subscriptions.create') }}" 
           class="mt-4 sm:mt-0 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-2xl font-medium transition-all shadow-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Subscription
        </a>
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 text-green-700 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500">Total Subscriptions</p>
            <p class="text-4xl font-bold text-gray-900 mt-2">{{ $subscriptions->total() }}</p>
        </div>
        
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500">Active</p>
            <p class="text-4xl font-bold text-emerald-600 mt-2">
                {{ $subscriptions->where('status', 'approve')->count() }}
            </p>
        </div>

        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-4xl font-bold text-amber-600 mt-2">
                {{ $subscriptions->where('status', 'pending')->count() }}
            </p>
        </div>

        <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500">This Month</p>
            <p class="text-4xl font-bold text-gray-900 mt-2">28</p>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-sm overflow-hidden border border-gray-100">

        <div class="px-6 py-5 border-b flex items-center justify-between bg-gray-50">
            <h5 class="font-semibold text-lg text-gray-800">All Subscriptions</h5>
            <span class="px-4 py-1.5 bg-blue-100 text-blue-700 text-sm font-medium rounded-2xl">
                {{ $subscriptions->total() }} Total
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($subscriptions as $subscription)
                        @php
                            $statusClass = match($subscription->status) {
                                'approve' => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'cancel'  => 'bg-red-100 text-red-700',
                                'expired' => 'bg-gray-100 text-gray-600',
                                default   => 'bg-gray-100 text-gray-600'
                            };
                        @endphp

                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-5">
                                <span class="font-mono text-sm font-medium text-gray-700">#{{ $subscription->id }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-medium">{{ $subscription->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $subscription->user->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-5 text-gray-700">
                                {{ $subscription->school->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center px-3 py-1 rounded-2xl bg-blue-50 text-blue-700 text-sm font-medium">
                                    {{ $subscription->plan->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center px-4 py-1.5 text-xs font-semibold rounded-2xl {{ $statusClass }}">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-5 font-semibold text-gray-800">
                                @if($subscription->amount_paid)
                                    ₦{{ number_format($subscription->amount_paid, 2) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm">
                                <div>{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') : '-' }}</div>
                                <div class="text-gray-400 text-xs">
                                    → {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') : 'No expiry' }}
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <a href="{{ route('admin.subscriptions.edit', $subscription) }}"
                                   class="inline-flex items-center justify-center w-9 h-9 text-amber-600 hover:bg-amber-50 rounded-2xl transition-colors">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-12 h-12 text-gray-200 mb-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                                <h5 class="text-gray-400 font-medium">No subscriptions yet</h5>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($subscriptions->hasPages())
            <div class="px-6 py-5 border-t bg-white">
                {{ $subscriptions->links() }}
            </div>
        @endif

    </div>
</div>

@endsection --}}