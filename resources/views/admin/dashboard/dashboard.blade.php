{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--admin" data-testid="admin-dashboard-shell">
        <div class="dashboard-home-head" data-testid="dashboard-home-head">
            <div>
                <h1 class="dashboard-title" data-testid="dashboard-greeting">
                    {{ ($greeting['phrase'] ?? 'Hello') }}, {{ $greeting['name'] ?? 'Admin' }}
                </h1>
                <p class="dashboard-subtitle" data-testid="dashboard-context-line">
                    {{ $dashboardContextLine ?? 'School overview' }}
                </p>
            </div>
            <span class="dashboard-live-badge" data-testid="dashboard-live-badge">
                <span class="dashboard-live-dot" aria-hidden="true"></span>
                Live
            </span>
        </div>
        @include('partials.message')
        @if(!empty($setupIncomplete))
            {{-- Top: product demo · Bottom: setup banner (Wave 3 empty-state layout) --}}
            <div class="dashboard-kpi-placeholder my-4" data-testid="dashboard-kpi-placeholder">
                @include('partials.empty-state-product-demo')
            </div>
            @include('partials.setup-banner')
        @else
            @include('partials.setup-banner')
        @endif

        @if(empty($setupIncomplete))
        <div class="dashboard-kpi-grid" data-testid="dashboard-kpi-grid">
            <x-ds-kpi-card icon="users" value="{{ $dashboard['studentCount'] }}" label="Students" color="green" :link="url('/admin/students')" />
            <x-ds-kpi-card icon="classes" value="{{ $dashboard['teacherCount'] }}" label="Teachers" color="blue" :link="url('/admin/teachers')" />
            <x-ds-kpi-card icon="users" value="{{ $dashboard['parentCount'] }}" label="Parents" color="amber" :link="url('/admin/parents')" />
            <x-ds-kpi-card icon="users" value="{{ $dashboard['nonteachingCount'] }}" label="Non Teaching Staff" color="red" :link="url('/admin/staffs')" />
            <x-ds-kpi-card icon="whatsapp" value="{{ $dashboard['whatsapp']['parentsOptedIn'] }}" label="WhatsApp Parents" color="green" />
            <x-ds-kpi-card icon="message" value="{{ $dashboard['whatsapp']['messagesThisMonth'] }}" label="Messages This Month" color="blue" />
        </div>

        {{-- Kit topfold: fees pulse + connected tools --}}
        <div class="dashboard-topfold dashboard-topfold--kit" data-testid="dashboard-topfold-kit">
            <div class="dashboard-topfold-fees" data-testid="dashboard-topfold-fees">
                <div class="flex flex-wrap items-center justify-between mb-3 gap-2">
                    <div>
                        <h2 class="ds-section-title">Fee collection trends</h2>
                        <p class="ds-section-subtitle">Live intake across the selected period</p>
                    </div>
                    <div class="flex gap-1 bg-gray-100 rounded-lg p-0.5" role="group">
                        <a href="{{ request()->fullUrlWithQuery(['period' => 'day']) }}"
                           class="px-3 py-2.5 text-xs font-semibold rounded-md transition-colors duration-150 {{ $trendPeriod === 'day' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Days
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['period' => 'week']) }}"
                           class="px-3 py-2.5 text-xs font-semibold rounded-md transition-colors duration-150 {{ $trendPeriod === 'week' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Weeks
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['period' => 'month']) }}"
                           class="px-3 py-2.5 text-xs font-semibold rounded-md transition-colors duration-150 {{ $trendPeriod === 'month' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Months
                        </a>
                    </div>
                </div>
                @php $feeTrendPoints = collect($feeTrend ?? []); @endphp
                <div data-testid="dashboard-fee-trend-chart">
                    <x-chart type="line" :height="180"
                             aria-label="Fee collection trend"
                             empty-message="No fee collections recorded yet"
                             :labels="$feeTrendPoints->pluck('label')->all()"
                             :datasets="[[
                                 'label' => 'Fee Collection',
                                 'data' => $feeTrendPoints->pluck('amount')->all(),
                                 'borderColor' => '#22C55E',
                                 'backgroundColor' => 'rgba(34,197,94,0.06)',
                                 'borderWidth' => 2,
                                 'pointBackgroundColor' => '#22C55E',
                                 'pointRadius' => 3,
                                 'pointHoverRadius' => 5,
                                 'tension' => 0.3,
                                 'fill' => true,
                             ]]"
                             :options="['plugins' => ['tooltip' => ['mode' => 'index', 'intersect' => false]]]" />
                </div>
            </div>
            <div class="dashboard-connected-tools" data-testid="dashboard-connected-tools">
                <h2 class="ds-section-title">Connected tools</h2>
                <p class="ds-section-subtitle">KlassApp runs inside what the school already uses</p>
                <ul class="dashboard-connected-list">
                    <li class="dashboard-connected-row">
                        <span class="dashboard-connected-label">
                            <x-brand.whatsapp class="dashboard-connected-mark" />
                            {{ number_format((int) ($dashboard['whatsapp']['parentsOptedIn'] ?? 0)) }} parents reachable
                        </span>
                        <x-badge variant="active">Live</x-badge>
                    </li>
                    <li class="dashboard-connected-row">
                        <span class="dashboard-connected-label">
                            <x-brand.google-drive class="dashboard-connected-mark" />
                            Reports filed to Drive
                        </span>
                        <x-badge variant="active">Live</x-badge>
                    </li>
                    <li class="dashboard-connected-row">
                        <span class="dashboard-connected-label">
                            <svg class="dashboard-connected-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                            Notice board
                        </span>
                        @php $noticeCount = is_countable($dashboard['noticeboard'] ?? null) ? count($dashboard['noticeboard']) : 0; @endphp
                        @if($noticeCount > 0)
                            <x-badge variant="pending">{{ $noticeCount }} {{ \Illuminate\Support\Str::plural('item', $noticeCount) }}</x-badge>
                        @else
                            <x-badge variant="info">Empty</x-badge>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex flex-wrap my-2 gap-2">
            <div class="w-full lg:w-1/2 px-1 my-3">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-xl dashboard-panel-title">Students</h1>
                    </div>
                    <x-chart type="doughnut" :height="240"
                             aria-label="Students by gender"
                             empty-message="No gender data"
                             :center-value="$dashboard['studentCount'] ?? 0"
                             :labels="['Male Students', 'Female Students', 'Unspecified']"
                             :datasets="[[
                                 'label' => ' Students',
                                 'backgroundColor' => ['#ffa601', '#304ffe', '#cbd5e1'],
                                 'data' => [
                                     $dashboard['maleCount'] ?? 0,
                                     $dashboard['femaleCount'] ?? 0,
                                     $dashboard['unknownCount'] ?? 0,
                                 ],
                             ]]"
                             options-js='{ plugins: { tooltip: { callbacks: { label: function (c) { var t = {{ (int) ($dashboard['studentCount'] ?? 0) }}; return c.dataset.label + ": " + Math.round((c.parsed || 0) / (t || 1) * 100) + "%"; } } } } }' />
                    <div class="flex items-center justify-between my-1">
                        @php
                            $hasGenderData = ($dashboard['femaleCount'] ?? 0) > 0 || ($dashboard['maleCount'] ?? 0) > 0 || ($dashboard['unknownCount'] ?? 0) > 0;
                        @endphp
                        <div class="border-r w-1/3 mt-4 bar-bg-blue relative student_count dashboard-gender-stat">
                            <a href="{{ url('/admin/students?gender=female') }}">
                                <p class="text-sm item-title font-semibold">Girls</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $hasGenderData ? $dashboard['femaleCount'] : '—' }}</p>
                            </a>
                        </div>
                        <div class="w-1/3 text-center mt-4 bar-bg-orange relative student_count student_male_count dashboard-gender-stat">
                            <a href="{{ url('/admin/students?gender=male') }}" target="_blank">
                                <p class="text-sm item-title font-semibold ">Boys</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $hasGenderData ? $dashboard['maleCount'] : '—' }}</p>
                            </a>
                        </div>
                        <div class="w-1/3 text-right mt-4 relative student_count dashboard-gender-stat">
                            <p class="text-sm item-title font-semibold">Unspecified</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $hasGenderData ? $dashboard['unknownCount'] : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/2 px-1 my-3">
                <div class="bg-white custom-shadow px-3 py-2 border dashboard-notice-card">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-lg border-b mx-2 py-1 pb-3 dashboard-panel-title">Notice Board</h1>
                    </div>
                    <div class="notice-box">
                        @if(count($dashboard['noticeboard']) > 0)
                            @foreach($dashboard['noticeboard'] as $noticeboard)
                                <div class="notice-box-list py-3 mx-3 border-b">
                                    <div class="bg-green-600 text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2">
                                        <p>{{ $noticeboard->title }}</p>
                                    </div>
                                    <div class="text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2" style="background:#c96442;">
                                        <p>{{ date('d M Y',strtotime($noticeboard->publish_date)) }}</p>
                                    </div>
                                    <div class="text-xs rounded-full inline-block text-white px-2 py-1 my-1 mb-2" style="background:#D97706;">
                                        <p>{{ ucwords($noticeboard->type) }}</p>
                                    </div>
                                    <div class="my-1">
                                        <p class="text-sm text-gray-900 font-semibold">{!! $noticeboard->description !!}</p>
                                    </div>
                                    <div class="text-sm my-1">
                                        <p class="text-gray-500">
                                            <span class="text-gray-500">{{ $noticeboard->created_at->diffForHumans() }}</span>
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="my-4 text-center text-gray-400">No notices yet</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending Approvals KPI --}}
        <div class="dashboard-kpi-grid mb-4" style="max-width: 280px;">
            <x-ds-kpi-card icon="check" value="{{ $pendingApprovals }}" label="Pending Approvals" color="amber" :link="url('admin/approvals')" />
        </div>

        <div class="flex flex-col lg:flex-row my-2 gap-4">
            @if(config('gexam.enabled', false))
            <div class="w-full lg:w-2/3">
                <div class="bg-white custom-shadow py-1 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Upcoming Exams</h1>
                    </div>
                    <div class="exam-box mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Exam Name</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base w-40">Subject</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Class</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Time</th>
                                </tr>
                            </thead>
                            @if(!empty($dashboard['upcomingExam']) && count($dashboard['upcomingExam']) > 0)
                                <tbody>
                                    @foreach($dashboard['upcomingExam'] as $key => $upcomingExams)
                                        <tr>
                                            <td colspan="4">
                                                <p class="bg-gray-100 px-4 py-2 border-t border-b text-base font-semibold text-gray-700">{{ date('d-m-Y H:i:s',strtotime($key)) }}</p>
                                            </td>
                                        </tr>
                                        @foreach($upcomingExams as $upcomingExam)
                                            <tr>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ $upcomingExam->exam->name }}</td>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ $upcomingExam->subject->name }}</td>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ $upcomingExam->standardLink->StandardSection }}</td>
                                                <td class="text-left px-3 pt-2 pb-3 text-base">{{ date('H:i:s',strtotime($key)) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center py-6 text-gray-400">No exams yet</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="w-full lg:w-1/3">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Feedbacks</h1>
                    </div>
                    <div class="mt-2">
                        @if(count($dashboard['feedbacks']) != 0)
                            @foreach($dashboard['feedbacks'] as $feedback)
                                <div class="border-b pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                    <div class="text-sm">
                                        <span class="font-semibold text-gray-800">{{ $feedback->parent->name ?? 'Parent' }}</span>
                                        <span class="text-gray-500"> - {{ $feedback->feedbackMessage->message ?? '' }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ $feedback->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        @else
                            <div class="my-4 text-center text-gray-400">No feedbacks available</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row my-2 gap-4">
            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Events</h1>
                    </div>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Event Name</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Date</th>
                                </tr>
                            </thead>
                            @if(count($dashboard['events']) != 0)
                                <tbody>
                                    @foreach($dashboard['events'] as $event)
                                        <tr>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $event->name }}</td>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ date('d M Y',strtotime($event->event_date)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="text-center py-6 text-gray-400">No events yet</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Products</h1>
                    </div>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Product</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Price</th>
                                    <th class="font-semibold text-left px-3 pt-2 pb-3 text-base">Stock</th>
                                </tr>
                            </thead>
                            @if(count($dashboard['products']) > 0)
                                <tbody>
                                    @foreach($dashboard['products'] as $product)
                                        <tr>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $product->name }}</td>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $product->price }}</td>
                                            <td class="text-left px-3 pt-2 pb-3 text-base">{{ $product->stock }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="text-center py-6 text-gray-400">No products yet</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row my-2 gap-4">
            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <h3 class="dashboard-panel-title mb-3">Today's Absentees — Students</h3>
                    <absentees-student url="{{ url('/') }}"></absentees-student>
                </div>
            </div>
            <div class="w-full lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <h3 class="dashboard-panel-title mb-3">Today's Absentees — Staff</h3>
                    <absentees-staff url="{{ url('/') }}"></absentees-staff>
                </div>
            </div>
            @if(count($dashboard['products']) > 0)
            <div class="w-full xl:w-2/3 lg:w-1/2">
                <div class="bg-white custom-shadow px-4 pt-3 pb-6 border h-full dashboard-panel-card">
                    <div>
                        <h1 class="dashboard-panel-title px-3 py-2 pb-3">Latest Products</h1>
                    </div>
                    <div class="mt-2">
                        <table class="w-full text-sm">
                            @foreach($dashboard['products'] as $product)
                                <tr class="border-b">
                                    <td class="py-2 px-3 text-base font-semibold text-gray-800">{{ $product->name }}</td>
                                    <td class="py-2 px-3 text-base text-gray-600">{{ $product->price }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="flex my-2 gap-4">
            <div class="w-full">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-xl dashboard-panel-title">Students Per Class</h1>
                    </div>
                    @php $classRows = collect($dashboard['standardStudentCounts'] ?? collect()); @endphp
                    <x-chart type="bar" :height="260"
                             aria-label="Students per class by gender"
                             empty-message="No classes to chart yet"
                             :labels="$classRows->map(fn ($l) => $l->section->name ?? $l->section_name ?? ('Standard '.$l->id))->values()->all()"
                             :datasets="[
                                 ['label' => 'Boys', 'data' => $classRows->pluck('maleCount')->map(fn ($v) => (int) $v)->values()->all(), 'backgroundColor' => '#304ffe', 'borderRadius' => 6],
                                 ['label' => 'Girls', 'data' => $classRows->pluck('femaleCount')->map(fn ($v) => (int) $v)->values()->all(), 'backgroundColor' => '#ffa601', 'borderRadius' => 6],
                                 ['label' => 'Unspecified', 'data' => $classRows->pluck('unknownCount')->map(fn ($v) => (int) $v)->values()->all(), 'backgroundColor' => '#cbd5e1', 'borderRadius' => 6],
                             ]"
                             :options="['scales' => ['y' => ['ticks' => ['stepSize' => 1, 'precision' => 0]]]]" />
                </div>
            </div>
        </div>
        @endif
    </div>

    @include('admin.reports._eot-kpi-card')

    @if(!empty($openToshiOnboarding))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.remove('toshi-collapsed');
        // Wave 3: open Toshi maximized (existing modal layout), not the narrow pill panel.
        window.dispatchEvent(new CustomEvent('toshi-maximize'));
      });
    </script>
    @endif

@endsection

@push('scripts')
    @if(!empty($setupIncomplete))
    <script src="{{ asset('js/empty-state-product-demo.js') }}" defer></script>
    @endif
    @if(empty($setupIncomplete))
    @endif
@endpush
