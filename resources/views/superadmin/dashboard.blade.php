{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--superadmin">
    {{-- Header --}}
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title" style="font-size: 1.25rem;">Super Admin</h1>
            <p class="dashboard-subtitle" style="margin-top: 4px;">
                Platform overview across {{ number_format($stats['totalSchools']) }} schools
                &middot; refreshed {{ $stats['refreshedAt'] ?? 'now' }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ url('/superadmin/academics/schools') }}" class="superadmin-header-btn superadmin-header-btn--blue">
                <i class="fa-solid fa-school"></i> Schools
            </a>
            <a href="{{ url('/superadmin/reports/subscriptions') }}" class="superadmin-header-btn superadmin-header-btn--amber">
                <i class="fa-solid fa-credit-card"></i> Plans
            </a>
        </div>
    </div>

    @include('partials.message')

    {{-- ════════════════════════════════════════════════
          ROW 1 — Primary KPIs (6 cards)
          ════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        {{-- Schools --}}
        <a href="{{ url('/superadmin/academics/schools') }}" class="dashboard-kpi-card superadmin-kpi-link">
            <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                <i class="fa-solid fa-school"></i>
            </div>
            <div class="dashboard-kpi-value">{{ number_format($stats['totalSchools']) }}</div>
            <div class="dashboard-kpi-label">Schools</div>
            <div class="superadmin-kpi-sub">
                <span class="text-green-600">{{ $stats['activeSchools'] }} active</span>
                @if($stats['inactiveSchools'] > 0)
                    <span class="text-gray-300 mx-1">|</span>
                    <span class="text-red-500">{{ $stats['inactiveSchools'] }} inactive</span>
                @endif
            </div>
        </a>

        {{-- Users --}}
        <a href="{{ url('/superadmin/academics/schools') }}" class="dashboard-kpi-card superadmin-kpi-link">
            <div class="dashboard-kpi-icon" style="background: rgba(15,23,42,0.08); color: #0F172A;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="dashboard-kpi-value">{{ number_format($stats['totalUsers']) }}</div>
            <div class="dashboard-kpi-label">Users</div>
            <div class="superadmin-kpi-sub">
                @php
                    $userDelta = $stats['usersThisMonth'] - ($stats['usersLastMonth'] ?? 0);
                @endphp
                @if($userDelta !== 0)
                    <span class="{{ $userDelta > 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $userDelta > 0 ? '+' : '' }}{{ number_format($userDelta) }} this month
                    </span>
                @else
                    <span class="text-gray-400">No change this month</span>
                @endif
            </div>
        </a>

        {{-- Subscriptions --}}
        <a href="{{ url('/superadmin/reports/subscriptions') }}" class="dashboard-kpi-card superadmin-kpi-link">
            <div class="dashboard-kpi-icon" style="background: rgba(217,119,6,0.10); color: #D97706;">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <div class="dashboard-kpi-value">{{ number_format($stats['subscriptionsTotal']) }}</div>
            <div class="dashboard-kpi-label">Subscriptions</div>
            <div class="superadmin-kpi-sub">
                <span class="text-green-600">{{ $stats['activeSubs'] }} active</span>
                @if($stats['expiredSubs'] > 0)
                    <span class="text-gray-300 mx-1">|</span>
                    <span class="text-red-500">{{ $stats['expiredSubs'] }} expired</span>
                @endif
            </div>
        </a>

        {{-- MRR --}}
        <div class="dashboard-kpi-card">
            <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
            <div class="dashboard-kpi-value">${{ number_format($stats['estimatedMRR'], 0) }}</div>
            <div class="dashboard-kpi-label">Est. MRR</div>
            <div class="superadmin-kpi-sub">
                <span class="text-gray-400">Based on active plans</span>
            </div>
        </div>

        {{-- WhatsApp Users --}}
        <div class="dashboard-kpi-card">
            <div class="dashboard-kpi-icon" style="background: rgba(34,197,94,0.10); color: #22C55E;">
                <i class="fa-brands fa-whatsapp"></i>
            </div>
            <div class="dashboard-kpi-value">{{ number_format($stats['whatsappUsers']) }}</div>
            <div class="dashboard-kpi-label">WhatsApp</div>
            <div class="superadmin-kpi-sub">
                <span class="text-gray-400">{{ $stats['whatsappSuccessRate'] }}% delivered</span>
            </div>
        </div>

        {{-- Messages --}}
        <div class="dashboard-kpi-card">
            <div class="dashboard-kpi-icon" style="background: rgba(30,111,217,0.10); color: #1E6FD9;">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div class="dashboard-kpi-value">{{ number_format($stats['whatsappMessages']) }}</div>
            <div class="dashboard-kpi-label">Messages</div>
            <div class="superadmin-kpi-sub">
                @php
                    $msgDelta = $stats['whatsappMessages'] - ($stats['whatsappLastMonth'] ?? 0);
                @endphp
                @if($msgDelta !== 0)
                    <span class="{{ $msgDelta > 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $msgDelta > 0 ? '+' : '' }}{{ number_format($msgDelta) }} vs last month
                    </span>
                @else
                    <span class="text-gray-400">This month</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
          ROW 2 — Chart + Role Breakdown
          ════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Growth Chart --}}
        <div class="lg:col-span-2 dashboard-panel-card p-5">
            <h3 class="dashboard-section-title mb-4">Growth Trends (6 Months)</h3>
            <canvas id="growthChart" class="superadmin-chart"></canvas>
        </div>

        {{-- Users by Role --}}
        <div class="dashboard-panel-card p-5">
            <h3 class="dashboard-section-title mb-4">Users by Role</h3>
            <div class="space-y-3">
                @php
                    $roles = [
                        ['label' => 'School Admins', 'count' => $stats['schoolAdmins'], 'color' => '#1E6FD9', 'icon' => 'fa-user-tie'],
                        ['label' => 'Teachers',      'count' => $stats['teachers'],     'color' => '#22C55E', 'icon' => 'fa-chalkboard-user'],
                        ['label' => 'Students',      'count' => $stats['students'],     'color' => '#0F172A', 'icon' => 'fa-user-graduate'],
                        ['label' => 'Parents',       'count' => $stats['parents'],      'color' => '#D97706', 'icon' => 'fa-people-roof'],
                    ];
                    $maxRole = max(collect($roles)->max('count'), 1);
                @endphp
                @foreach($roles as $role)
                    <div class="superadmin-role-row">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="superadmin-role-icon" style="background: {{ $role['color'] }}20; color: {{ $role['color'] }};">
                                <i class="fa-solid {{ $role['icon'] }}"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-800 flex-1">{{ $role['label'] }}</span>
                            <span class="text-sm font-bold" style="font-family: 'Sora', sans-serif; color: {{ $role['color'] }};">
                                {{ number_format($role['count']) }}
                            </span>
                        </div>
                        <div class="superadmin-bar-track">
                            <div class="superadmin-bar-fill" style="width: {{ round(($role['count'] / $maxRole) * 100) }}%; background: {{ $role['color'] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($stats['failedJobs'] > 0)
                <div class="mt-4 pt-4 border-t" style="border-color: #E2E8F0;">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 text-sm"></i>
                        <span class="text-xs text-red-600 font-semibold">{{ $stats['failedJobs'] }} failed job{{ $stats['failedJobs'] > 1 ? 's' : '' }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
          ROW 3 — Plans + Subscription Status
          ════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        {{-- Plans — with revenue --}}
        <div class="dashboard-panel-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="dashboard-section-title">Plans</h3>
                <a href="{{ url('/superadmin/setting/plans') }}" class="text-xs font-semibold" style="color: #1E6FD9;">Manage</a>
            </div>
            <div class="space-y-3">
                @forelse($stats['plans'] as $plan)
                    @php
                        $name = $plan->display_name ?? $plan->name;
                        $icon = match(strtolower($plan->name)) {
                            'freemium' => 'fa-seedling',
                            'growth' => 'fa-chart-line',
                            'premium' => 'fa-crown',
                            default => 'fa-box',
                        };
                        $accent = match(strtolower($plan->name)) {
                            'freemium' => '#64748B',
                            'growth' => '#1E6FD9',
                            'premium' => '#D97706',
                            default => '#0F172A',
                        };
                    @endphp
                    <div class="superadmin-plan-row">
                        <div class="flex items-center gap-3">
                            <div class="superadmin-plan-icon" style="background: {{ $accent }}15; color: {{ $accent }};">
                                <i class="fa-solid {{ $icon }}"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-800">{{ $name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $plan->amount > 0 ? '$' . number_format($plan->amount) . ' / cycle' : 'Free' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold" style="font-family: 'Sora', sans-serif; color: {{ $accent }};">
                                    {{ number_format($plan->subscriptions_count) }}
                                </p>
                                <p class="text-xs text-gray-400">subscribers</p>
                            </div>
                            @if($plan->estimated_revenue > 0)
                                <div class="text-right" style="min-width: 80px;">
                                    <p class="text-sm font-semibold text-gray-700">${{ number_format($plan->estimated_revenue, 0) }}</p>
                                    <p class="text-xs text-gray-400">MRR</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6">
                        <p class="text-sm text-gray-500">No active plans configured.</p>
                        <a href="{{ url('/superadmin/setting/plans') }}" class="text-xs font-semibold mt-1" style="color: #1E6FD9;">Create your first plan →</a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Subscription Status + Health --}}
        <div class="dashboard-panel-card p-5">
            <h3 class="dashboard-section-title mb-4">Subscription Health</h3>
            @if($stats['subscriptionsTotal'] > 0)
                <div class="space-y-4">
                    @php
                        $statuses = [
                            ['label' => 'Active',    'count' => $stats['activeSubs'],    'color' => '#22C55E'],
                            ['label' => 'Expired',   'count' => $stats['expiredSubs'],   'color' => '#EF4444'],
                            ['label' => 'Cancelled', 'count' => $stats['cancelledSubs'], 'color' => '#64748B'],
                        ];
                    @endphp
                    @foreach($statuses as $s)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">{{ $s['label'] }}</span>
                                <span class="text-xs font-bold" style="color: {{ $s['color'] }};">{{ $s['count'] }}</span>
                            </div>
                            <div class="superadmin-bar-track">
                                <div class="superadmin-bar-fill" style="width: {{ $stats['subscriptionsTotal'] > 0 ? round(($s['count'] / $stats['subscriptionsTotal']) * 100) : 0 }}%; background: {{ $s['color'] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{-- Health ring --}}
                <div class="flex items-center gap-4 mt-5 pt-4 border-t" style="border-color: #E2E8F0;">
                    @php
                        $healthPct = $stats['subscriptionsTotal'] > 0
                            ? round(($stats['activeSubs'] / $stats['subscriptionsTotal']) * 100)
                            : 0;
                        $healthColor = $healthPct >= 80 ? '#22C55E' : ($healthPct >= 50 ? '#D97706' : '#EF4444');
                    @endphp
                    <div class="superadmin-health-ring" style="--health-pct: {{ $healthPct }}; --health-color: {{ $healthColor }};">
                        <svg viewBox="0 0 36 36">
                            <path class="superadmin-health-ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="superadmin-health-ring-fill" stroke="{{ $healthColor }}"
                                  stroke-dasharray="{{ $healthPct }}, 100"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <text x="18" y="20.5" text-anchor="middle" fill="{{ $healthColor }}" font-size="7" font-family="Sora, sans-serif" font-weight="700">{{ $healthPct }}%</text>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Subscription Health</p>
                        <p class="text-xs text-gray-500">{{ $stats['activeSubs'] }} of {{ $stats['subscriptionsTotal'] }} active</p>
                    </div>
                </div>
            @else
                <div class="text-center py-6">
                    <div class="superadmin-health-ring empty" style="--health-pct: 0; --health-color: #E2E8F0;">
                        <svg viewBox="0 0 36 36">
                            <path class="superadmin-health-ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">No subscriptions yet.</p>
                    <p class="text-xs text-gray-400 mt-1">Schools will appear here once they subscribe.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
          ROW 4 — Recent Activity
          ════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Recent Schools --}}
        <div class="dashboard-panel-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="dashboard-section-title">Recent Schools</h3>
                <a href="{{ url('/superadmin/academics/schools') }}" class="text-xs font-semibold" style="color: #1E6FD9;">View all →</a>
            </div>
            <div class="space-y-2">
                @forelse($stats['recentSchools'] as $school)
                    <a href="{{ url('/superadmin/academics/school/detail/' . $school->id) }}"
                       class="superadmin-activity-row">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="superadmin-avatar" style="background: {{ $school->status == 1 ? 'rgba(34,197,94,0.10)' : 'rgba(239,68,68,0.10)' }}; color: {{ $school->status == 1 ? '#22C55E' : '#EF4444' }};">
                                {{ strtoupper(substr($school->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $school->name }}</p>
                                <p class="text-xs text-gray-500">{{ $school->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $school->status == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $school->status == 1 ? 'Active' : 'Inactive' }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 text-center py-6">No schools registered yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Users --}}
        <div class="dashboard-panel-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="dashboard-section-title">Recent Users</h3>
                <a href="{{ url('/superadmin/users') }}" class="text-xs font-semibold" style="color: #1E6FD9;">View all →</a>
            </div>
            <div class="space-y-2">
                @forelse($stats['recentUsers'] as $user)
                    <div class="superadmin-activity-row">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="superadmin-avatar" style="background: rgba(30,111,217,0.08); color: #1E6FD9;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $user->school->name ?? 'No school' }}
                                    &middot; {{ $user->created_at->diffForHumans() }}
                                </p>
                            </div>
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
                    <p class="text-sm text-gray-500 text-center py-6">No users registered yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
<script>
(function () {
    var ctx = document.getElementById('growthChart');
    if (!ctx) return;

    var trendData = {!! json_encode($stats['monthlyTrends'] ?? []) !!};

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: trendData.map(function (d) { return d.label; }),
            datasets: [
                {
                    label: 'Schools',
                    data: trendData.map(function (d) { return d.schools; }),
                    borderColor: '#1E6FD9',
                    backgroundColor: 'rgba(30,111,217,0.06)',
                    borderWidth: 2,
                    pointBackgroundColor: '#1E6FD9',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                },
                {
                    label: 'Users',
                    data: trendData.map(function (d) { return d.users; }),
                    borderColor: '#22C55E',
                    backgroundColor: 'rgba(34,197,94,0.04)',
                    borderWidth: 2,
                    pointBackgroundColor: '#22C55E',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 24,
                    fontFamily: 'DM Sans',
                    fontSize: 12,
                }
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                backgroundColor: '#0F172A',
                titleFontFamily: 'Sora',
                bodyFontFamily: 'DM Sans',
                bodyFontSize: 12,
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        precision: 0,
                        fontFamily: 'DM Sans',
                        fontSize: 11,
                    },
                    gridLines: {
                        color: '#F1F5F9',
                        drawBorder: false,
                    }
                }],
                xAxes: [{
                    ticks: {
                        fontFamily: 'DM Sans',
                        fontSize: 11,
                    },
                    gridLines: { display: false },
                }]
            }
        }
    });
})();
</script>
@endpush
