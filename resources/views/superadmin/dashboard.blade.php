{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.superadmin.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--superadmin">
        <div class="dashboard-live-badge dashboard-live-badge--superadmin">The Super Admin Command Center</div>
        <p class="dashboard-subtitle">Monitor school network growth and core operational signals from one control room.</p>

        @include('partials.message')

        <div class="dashboard-topfold dashboard-topfold--superadmin">
            <div class="flex flex-wrap my-1">
                <!-- Total Schools -->
                <div class="w-full lg:w-1/3 md:w-1/2 px-1 my-2">
                    <div class="dashboard-panel-card dashboard-panel-card--metric h-full">
                        <livewire:superadmin.academics.total-school-widget />
                    </div>
                </div>

                <!-- System Health / Overview Card -->
                <div class="w-full lg:w-1/3 md:w-1/2 px-1 my-2">
                    <div class="bg-white custom-shadow px-5 py-5 border dashboard-panel-card dashboard-panel-card--metric h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">System Status</h3>
                            <div class="w-3 h-3 rounded-full bg-green-500 shadow-sm"></div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Active Schools</span>
                                <span class="font-semibold text-gray-800">100%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Database Health</span>
                                <span class="font-semibold text-gray-800">Optimal</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">Last updated: Just now</p>
                    </div>
                </div>

                <!-- Quick Actions / Network Info -->
                <div class="w-full lg:w-1/3 md:w-1/2 px-1 my-2">
                    <div class="bg-white custom-shadow px-5 py-5 border dashboard-panel-card dashboard-panel-card--metric h-full">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Network Overview</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded superadmin-stat-row">
                                <span class="text-sm text-gray-600">Uptime</span>
                                <span class="font-semibold text-green-600">99.8%</span>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded superadmin-stat-row">
                                <span class="text-sm text-gray-600">Load</span>
                                <span class="font-semibold text-blue-600">Normal</span>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded superadmin-stat-row">
                                <span class="text-sm text-gray-600">Security</span>
                                <span class="font-semibold text-green-600">Secure</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
