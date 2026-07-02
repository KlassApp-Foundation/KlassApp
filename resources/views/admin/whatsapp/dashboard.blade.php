{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="admin-h1 my-3">WhatsApp Delivery Dashboard</h1>
            <p class="text-gray-600 text-sm">Message delivery stats, failure monitoring, and activity log</p>
        </div>

        {{-- Period filter --}}
        <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
            @foreach(['24h' => '24H', '7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days'] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['period' => $val]) }}"
                   class="px-3 py-1.5 rounded-md text-sm font-medium transition
                          {{ $period === $val ? 'bg-white shadow text-green-700' : 'text-gray-600 hover:text-gray-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $totalSent }}</div>
                    <div class="text-xs text-gray-500">Messages Sent</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $deliveryRate }}%</div>
                    <div class="text-xs text-gray-500">Delivery Rate ({{ $delivered }}/{{ $totalSent > 0 ? $totalSent : 0 }})</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold {{ $failureRate > 10 ? 'text-red-600' : 'text-gray-900' }}">{{ $failureRate }}%</div>
                    <div class="text-xs text-gray-500">Failure Rate ({{ $failed }} failed)</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $linkedUsers }}</div>
                    <div class="text-xs text-gray-500">Linked Users ({{ $optedIn }} opted in)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Daily Trend Chart --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow border border-gray-100 p-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Daily Trend</h2>
            <div class="space-y-2">
                @foreach($dailyStats as $day)
                    @php
                        $maxVal = max($dailyStats->max('sent'), 1);
                        $sentWidth = $maxVal > 0 ? ($day['sent'] / $maxVal) * 100 : 0;
                        $deliveredWidth = $maxVal > 0 ? ($day['delivered'] / $maxVal) * 100 : 0;
                        $failedWidth = $maxVal > 0 ? ($day['failed'] / $maxVal) * 100 : 0;
                    @endphp
                    <div class="grid grid-cols-[40px_1fr] gap-3 items-center">
                        <span class="text-xs text-gray-500 text-right">{{ $day['label'] }}</span>
                        <div>
                            <div class="flex items-center gap-1">
                                <div class="flex-1 h-5 rounded bg-gray-50 overflow-hidden flex">
                                    <div class="h-full bg-blue-500 transition-all" style="width: {{ $sentWidth }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600 w-6 text-right">{{ $day['sent'] }}</span>
                            </div>
                            <div class="flex items-center gap-1 mt-0.5">
                                <div class="flex-1 h-2 rounded bg-gray-50 overflow-hidden flex">
                                    <div class="h-full bg-green-400 transition-all" style="width: {{ $deliveredWidth }}%"></div>
                                    <div class="h-full bg-red-400 transition-all" style="width: {{ $failedWidth ? max(4, $failedWidth) : 0 }}%"></div>
                                </div>
                                <span class="text-xs text-gray-400 w-6 text-right">{{ $day['delivered'] }}/{{ $day['failed'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex gap-4 mt-4 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-blue-500 inline-block"></span> Sent</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-green-400 inline-block"></span> Delivered</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-red-400 inline-block"></span> Failed</span>
            </div>
        </div>

        {{-- Flow Breakdown --}}
        <div class="bg-white rounded-lg shadow border border-gray-100 p-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">By Flow Type</h2>
            @if($flowBreakdown->isEmpty())
                <p class="text-sm text-gray-400">No data yet</p>
            @else
                <div class="space-y-3">
                    @foreach($flowBreakdown as $flow)
                        @php
                            $maxFlow = $flowBreakdown->max('total');
                            $width = $maxFlow > 0 ? ($flow->total / $maxFlow) * 100 : 0;
                            $failPct = $flow->total > 0 ? round(($flow->failed_count / $flow->total) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700 capitalize">{{ $flow->flow_type ?? 'Unknown' }}</span>
                                <span class="text-gray-500">{{ $flow->total }} ({{ $failPct }}% fail)</span>
                            </div>
                            <div class="h-2 rounded bg-gray-100 overflow-hidden flex">
                                <div class="h-full bg-green-400 transition-all" style="width: {{ $width * (1 - $failPct / 100) }}%"></div>
                                @if($failPct > 0)
                                    <div class="h-full bg-red-400 transition-all" style="width: {{ $width * ($failPct / 100) }}%"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white rounded-lg shadow border border-gray-100 p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Recent Activity</h2>
            <div class="flex gap-2 text-xs">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span> Outbound</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span> Inbound</span>
            </div>
        </div>

        @if($recentActivity->isEmpty())
            <p class="text-sm text-gray-400 py-4 text-center">No activity in this period</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="pb-2 font-medium">Time</th>
                            <th class="pb-2 font-medium">Phone</th>
                            <th class="pb-2 font-medium">Direction</th>
                            <th class="pb-2 font-medium">Status</th>
                            <th class="pb-2 font-medium">Type</th>
                            <th class="pb-2 font-medium hidden md:table-cell">Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivity as $log)
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 pr-3 text-gray-600 whitespace-nowrap">
                                    {{ $log->sent_at ? \Carbon\Carbon::parse($log->sent_at)->format('H:i') : '-' }}
                                </td>
                                <td class="py-2.5 pr-3 text-gray-800 font-medium font-mono text-xs">
                                    {{ $log->phone }}
                                </td>
                                <td class="py-2.5 pr-3">
                                    @if($log->direction === 'inbound')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">In</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Out</span>
                                    @endif
                                </td>
                                <td class="py-2.5 pr-3">
                                    @php
                                        $statusColors = [
                                            'sent' => 'bg-gray-50 text-gray-600',
                                            'delivered' => 'bg-green-50 text-green-700',
                                            'read' => 'bg-blue-50 text-blue-700',
                                            'failed' => 'bg-red-50 text-red-700',
                                            'received' => 'bg-purple-50 text-purple-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$log->status] ?? 'bg-gray-50 text-gray-600' }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-3 text-gray-600 text-xs capitalize">
                                    {{ $log->flow_type ?? $log->template_name ?? '-' }}
                                </td>
                                <td class="py-2.5 text-gray-400 text-xs hidden md:table-cell max-w-xs truncate">
                                    {{ \Illuminate\Support\Str::limit($log->content_preview, 40) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
