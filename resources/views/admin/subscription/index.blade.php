@extends('layouts.admin.layout')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Subscriptions</h1>
            <p class="text-gray-600 mt-1">Manage all subscription records across the school</p>
        </div>
        {{-- {{ dd($subscriptions->first()->plan->name) }} --}}
@if (in_array($subscriptions->first()?->status, ['canceled','expired']) || $subscriptions->first()?->plan->name === "free" )
<a href="{{ route('admin.subscriptions.create') }}" 
           class="mt-4 sm:mt-0 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded font-medium transition-all shadow-sm">
            <i class="fas fa-plus"></i>
            New Subscription
        </a>
@endif
        
    </div>

    <!-- Success Alert -->
    @include("partials.message")

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

        <div class="bg-white rounded shadow-md p-2 border border-gray-100">
            <p class="text-sm text-gray-500">Total Subscriptions</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $subscriptions->total() }}</p>
        </div>
        
        <div class="bg-white rounded shadow-sm p-2 border border-gray-100">
            <p class="text-sm text-gray-500">Active</p>
            <p class="text-2xl font-bold text-emerald-600 mt-2">
                {{ $subscriptions->where('status', 'approve')->count() }}
            </p>
        </div>

        <div class="bg-white rounded shadow-sm p-2 border border-gray-100">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-amber-600 mt-2">
                {{ $subscriptions->where('status', 'pending')->count() }}
            </p>
        </div>

        <div class="bg-white rounded shadow-sm p-2 border border-gray-100">
            <p class="text-sm text-gray-500">This Month</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">28</p>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-sm overflow-hidden border border-gray-100">
        <div class="p-2 text-xs">
            <span>Your current plan is {{ $currentPlan->plan->name }}</span>
            <a class="text-green-500" href="#">continue to subscription</a>
        </div>
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
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount(UGX)</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($subscriptions as $subscription)
                        @php
                            $statusClass = match($subscription->status) {
                                'approve' => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-yellow-700',
                                'cancel'  => 'bg-red-100 text-red-700',
                                'expired' => 'bg-gray-100 text-gray-600',
                                default   => 'bg-gray-100 text-gray-600'
                            };
                        @endphp

                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-5">
                                <span class="font-mono text-sm font-medium text-gray-700">#{{ $subscription->id }}</span>
                            </td>
                            {{-- <td class="px-6 py-5">
                                <div class="font-medium">{{ $subscription->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $subscription->user->email ?? '' }}</div>
                            </td> --}}
                            {{-- <td class="px-6 py-5 text-gray-700">
                                {{ $subscription->school->name ?? 'N/A' }}
                            </td> --}}
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center px-3 py-1 rounded-2xl bg-blue-50 text-blue-700 text-sm font-medium">
                                    {{ $subscription->plan->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center px-4 py-1.5 text-xs rounded-2xl {{ $statusClass }}">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </td>
                             
                            <td class="px-6 py-5 text-gray-700">
                                @if($subscription->amount_paid)
                                    {{ number_format($subscription->amount_paid, 2) }}
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm">
                                <div>{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') : '-' }}</div>
                                <div class="text-gray-400 text-xs">
                                    → {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') : 'No expiry' }}
                                </div>
                            </td>
                            {{-- {{ dd($subscription) }} --}}
                            <td class="px-6 py-5 text-center flex items-center justify-center gap-4">
                                @if ($subscription->plan->name !== "free")

                                @if (!in_array($subscription->status, ['approved','canceled','expired']) )
                                    
                                     <a href="{{ route('admin.subscriptions.edit', $subscription) }}"
                                   class="inline-flex items-center justify-center w-9 h-9 text-amber-600 hover:bg-amber-50 rounded-2xl transition-colors">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                {{-- ====== update --}}
                                <form action="{{ route("admin.subscriptions.update", $subscription->id) }}" method='POST'>
                                    @csrf
                                    @method("PUT")
                                    <button type="button">
                                        <i class="fas fa-ban"></i>
                                    </button> 
                                </form>

                                {{--  --}}
                                    
                                @endif
                                    
                            </td>
                        </tr>
                    @empty
                    
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <i class="fas fa-folder-open text-6xl text-gray-200 mb-4"></i>
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

@endsection