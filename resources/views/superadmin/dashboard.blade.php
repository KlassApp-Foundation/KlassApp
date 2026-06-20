{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--superadmin">
        <div class="dashboard-heading">
            <div>
                <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Super Admin</h1>
                <p class="dashboard-subtitle" style="margin-top: 4px;">Monitor every school, user, and subscription on the platform from one place.</p>
            </div>
        </div>

        @include('partials.message')

        @livewire('agent-toshi')

        <!-- Platform Overview KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                    <i class="fa-solid fa-school"></i>
                </div>
                <div class="dashboard-kpi-value">{{ number_format($stats['totalSchools']) }}</div>
                <div class="dashboard-kpi-label">Total Schools</div>
                <div class="text-xs text-gray-500 mt-1">
                    <span class="text-green-600 font-semibold">{{ $stats['activeSchools'] }} active</span>
                    @if($stats['inactiveSchools'] > 0)
                        <span class="text-gray-400 mx-1">|</span>
                        <span class="text-red-500 font-semibold">{{ $stats['inactiveSchools'] }} inactive</span>
                    @endif
                </div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(15,23,42,0.08); color: #0F172A;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="dashboard-kpi-value">{{ number_format($stats['activeSchools']) }}</div>
                <div class="dashboard-kpi-label">Active Schools</div>
                <div class="text-xs text-gray-500 mt-1">
                    @if($stats['totalSchools'] > 0)
                        {{ round(($stats['activeSchools'] / $stats['totalSchools']) * 100) }}% of network
                    @else
                        —
                    @endif
                </div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="dashboard-kpi-value">{{ number_format($stats['totalUsers']) }}</div>
                <div class="dashboard-kpi-label">Total Users</div>
                <div class="text-xs text-gray-500 mt-1">Across all schools</div>
            </div>

            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(217,119,6,0.10); color: #D97706;">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <div class="dashboard-kpi-value">{{ number_format($stats['subscriptionsTotal']) }}</div>
                <div class="dashboard-kpi-label">Subscriptions</div>
                <div class="text-xs text-gray-500 mt-1">
                    @forelse($stats['subscriptionsByStatus'] as $status => $count)
                        <span class="capitalize">{{ $status }}: {{ $count }}</span>@if(!$loop->last), @endif
                    @empty
                        No subscriptions yet
                    @endforelse
                </div>
            </div>
        </div>

        <!-- WhatsApp KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <div class="dashboard-kpi-value">{{ number_format($stats['whatsappUsers']) }}</div>
                <div class="dashboard-kpi-label">WhatsApp Users</div>
                <div class="text-xs text-gray-500 mt-1">Linked across all schools</div>
            </div>
            <div class="dashboard-kpi-card">
                <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <div class="dashboard-kpi-value">{{ number_format($stats['whatsappMessages']) }}</div>
                <div class="dashboard-kpi-label">Messages This Month</div>
                <div class="text-xs text-gray-500 mt-1">Platform-wide outbound</div>
            </div>
        </div>

        <!-- Users by Role -->
        <div class="mb-6">
            <h2 class="dashboard-section-title mb-3">Users by Role</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="dashboard-panel-card dashboard-panel-card--metric text-center p-4">
                    <div class="text-2xl font-bold text-gray-800" style="font-family: 'Sora', sans-serif;">{{ number_format($stats['schoolAdmins']) }}</div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mt-1">School Admins</div>
                </div>
                <div class="dashboard-panel-card dashboard-panel-card--metric text-center p-4">
                    <div class="text-2xl font-bold text-gray-800" style="font-family: 'Sora', sans-serif;">{{ number_format($stats['teachers']) }}</div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mt-1">Teachers</div>
                </div>
                <div class="dashboard-panel-card dashboard-panel-card--metric text-center p-4">
                    <div class="text-2xl font-bold text-gray-800" style="font-family: 'Sora', sans-serif;">{{ number_format($stats['students']) }}</div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mt-1">Students</div>
                </div>
                <div class="dashboard-panel-card dashboard-panel-card--metric text-center p-4">
                    <div class="text-2xl font-bold text-gray-800" style="font-family: 'Sora', sans-serif;">{{ number_format($stats['parents']) }}</div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mt-1">Parents</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Recent Schools -->
            <div class="dashboard-panel-card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="dashboard-section-title">Recent Schools</h3>
                    <a href="{{ url('/superadmin/academics/schools') }}" class="text-sm font-semibold" style="color: #1E6FD9;">View all</a>
                </div>
                <div class="space-y-3">
                    @forelse($stats['recentSchools'] as $school)
                        <div class="flex items-center justify-between p-3 rounded-lg" style="background: #F8FAFC;">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $school->name }}</p>
                                <p class="text-xs text-gray-500">{{ $school->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $school->status == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $school->status == 1 ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No schools registered yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Users -->
            <div class="dashboard-panel-card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="dashboard-section-title">Recent Users</h3>
                    <a href="{{ url('/superadmin/users') }}" class="text-sm font-semibold" style="color: #1E6FD9;">View all</a>
                </div>
                <div class="space-y-3">
                    @forelse($stats['recentUsers'] as $user)
                        <div class="flex items-center justify-between p-3 rounded-lg" style="background: #F8FAFC;">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $user->school->name ?? 'No school' }} — {{ $user->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="text-xs font-medium text-gray-500">
                                @switch($user->usergroup_id)
                                    @case(1) Site Admin @break
                                    @case(3) School Admin @break
                                    @case(5) Teacher @break
                                    @case(6) Student @break
                                    @case(7) Parent @break
                                    @default User
                                @endswitch
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No users registered yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Subscriptions & Plans -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Subscription Status -->
            <div class="dashboard-panel-card p-5">
                <h3 class="dashboard-section-title mb-4">Subscription Status</h3>
                @if($stats['subscriptionsTotal'] > 0)
                    <div class="space-y-3">
                        @foreach($stats['subscriptionsByStatus'] as $status => $count)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 capitalize">{{ $status }}</span>
                                <div class="flex items-center gap-3 flex-1 ml-4">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full"
                                             style="width: {{ round(($count / $stats['subscriptionsTotal']) * 100) }}%; background: #1E6FD9;">
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800 w-8 text-right">{{ $count }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <p class="text-sm text-gray-500">No subscriptions yet.</p>
                        <p class="text-xs text-gray-400 mt-1">Schools will appear here once they subscribe.</p>
                    </div>
                @endif
            </div>

            <!-- Plan Distribution -->
            <div class="dashboard-panel-card p-5">
                <h3 class="dashboard-section-title mb-4">Plans</h3>
                <div class="space-y-3">
                    @forelse($stats['plans'] as $plan)
                        <div class="flex items-center justify-between p-3 rounded-lg" style="background: #F8FAFC;">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $plan->display_name ?? $plan->name }}</p>
                                <p class="text-xs text-gray-500">
                                    @if(in_array($plan->name, ['growth', 'premium']))
                                        Contact us
                                    @else
                                        ${{ number_format($plan->amount) }} / cycle
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold text-gray-800" style="font-family: 'Sora', sans-serif;">{{ number_format($plan->subscriptions_count) }}</span>
                                <p class="text-xs text-gray-500">subscribers</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No active plans configured.</p>
                    @endforelse
                </div>
    </div>
@endsection
