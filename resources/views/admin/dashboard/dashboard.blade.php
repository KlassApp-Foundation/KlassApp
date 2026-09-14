{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--admin">
        <div class="dashboard-heading">
            <div>
                <h1 class="dashboard-section-title">Admin</h1>
                <p class="dashboard-subtitle">Live school activity, enrollment pulse, and updates in one place.</p>
            </div>
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
        <div class="flex flex-wrap my-2 dashboard-topfold">
            <div class="w-full xl:w-1/3 lg:w-2/3 my-2">
                <div class="dashboard-kpi-grid">
                    <x-ds-kpi-card icon="users" value="{{ $dashboard['studentCount'] }}" label="Students" color="green" :link="url('/admin/students')" />
                    <x-ds-kpi-card icon="classes" value="{{ $dashboard['teacherCount'] }}" label="Teachers" color="blue" :link="url('/admin/teachers')" />
                    <x-ds-kpi-card icon="users" value="{{ $dashboard['parentCount'] }}" label="Parents" color="amber" :link="url('/admin/parents')" />
                    <x-ds-kpi-card icon="users" value="{{ $dashboard['nonteachingCount'] }}" label="Non Teaching Staff" color="red" :link="url('/admin/staffs')" />
                    <x-ds-kpi-card icon="whatsapp" value="{{ $dashboard['whatsapp']['parentsOptedIn'] }}" label="WhatsApp Parents" color="green" />
                    <x-ds-kpi-card icon="message" value="{{ $dashboard['whatsapp']['messagesThisMonth'] }}" label="Messages This Month" color="blue" />
                </div>
            </div>
            <div class="w-full lg:w-1/3 px-1 my-3">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card">
                    <div>
                        <h1 class="text-gray-800 font-semibold text-xl dashboard-panel-title">Students</h1>
                    </div>
                    <canvas id="graph" class="dashboard-chart-canvas" style="max-width:100%;height:auto"></canvas>
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
            <div class="w-full xl:w-1/3 lg:w-full md:w-1/3 px-1 my-3">
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
                    <canvas id="barChart" class="dashboard-chart-canvas"></canvas>
                </div>
            </div>
        </div>

        {{-- Fee Collection Trend Chart --}}
        <div class="flex my-2 gap-4">
            <div class="w-full">
                <div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <h1 class="text-gray-800 font-semibold text-xl dashboard-panel-title">Fee Collection Trends</h1>
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
                    <canvas id="feeTrendChart" class="dashboard-chart-canvas" style="height:260px;"></canvas>
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
    <script src="{{ asset('js/Chart.min.js') }}?v=2.9.3"></script>
    <script>
        var ctx = document.getElementById('graph').getContext('2d');
        var femaleCount = {!! trans($dashboard['femaleCount'] ?? 0) !!};
        var maleCount = {!! trans($dashboard['maleCount'] ?? 0) !!};
        var unknownCount = {!! trans($dashboard['unknownCount'] ?? 0) !!};
        var totalStudents = {!! trans($dashboard['studentCount'] ?? 0) !!};

        if (totalStudents === 0) {
            var ctx2 = document.getElementById('graph').getContext('2d');
            ctx2.clearRect(0, 0, ctx2.canvas.width, ctx2.canvas.height);
            ctx2.textAlign = 'center';
            ctx2.textBaseline = 'middle';
            ctx2.font = "13px 'DM Sans', sans-serif";
            ctx2.fillStyle = '#94A3B8';
            ctx2.fillText('No gender data', ctx2.canvas.width / 2, ctx2.canvas.height / 2);
        }

        Chart.pluginService.register({
            afterDraw: function(chart) {
                if (chart.config.type !== 'doughnut') return;
                var width = chart.chart.width,
                    height = chart.chart.height,
                    ctx = chart.chart.ctx;
                ctx.save();
                var fontSize = (height / 140).toFixed(2);
                ctx.font = "700 " + fontSize + "em 'Sora', sans-serif";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#4d4c48";
                var text = totalStudents,
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2;
                ctx.fillText(text, textX, textY);
                ctx.restore();
            }
        });

        var chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ["Male Students", "Female Students", "Unspecified"],
                datasets: [{
                    label: " Students",
                    backgroundColor: [
                        "#ffa601", "#304ffe", "#cbd5e1"
                    ],
                    data: [maleCount,femaleCount,unknownCount],
                }]
            },
            options: {
                legend: {
                    display: false,
                },
                tooltips: {
                    enabled: true,
                    mode: 'index',
                    callbacks: {
                        label: function (tooltipItems, data) {
                            var i, label = [], l = data.datasets.length;
                            for (i = 0; i < l; i += 1) {
                                label[i] = data.datasets[i].label + ': ' + Math.round(data.datasets[i].data[tooltipItems.index] / totalStudents * 100) + '%';
                            }
                            return label;
                        }
                    }
                }
            }
        });

        // ── Fee Collection Trend Chart ──
        var trendCtx = document.getElementById('feeTrendChart');
        if (trendCtx) {
            var trendData = {!! json_encode($feeTrend ?? []) !!};
            new Chart(trendCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendData.map(function (d) { return d.label; }),
                    datasets: [{
                        label: 'Fee Collection',
                        data: trendData.map(function (d) { return d.amount; }),
                        borderColor: '#22C55E',
                        backgroundColor: 'rgba(34,197,94,0.06)',
                        borderWidth: 2,
                        pointBackgroundColor: '#22C55E',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false,
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#0F172A',
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var val = tooltipItem.yLabel;
                                return 'UGX ' + Number(val).toLocaleString();
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                fontFamily: 'DM Sans',
                                fontSize: 11,
                                callback: function (value) {
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                                    return value;
                                }
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
                            gridLines: {
                                display: false,
                            }
                        }]
                    }
                }
            });
        }

        var ctx = document.getElementById("barChart");
        if (ctx) {
            var standardData = {!! json_encode(($dashboard['standardStudentCounts'] ?? collect())->map(fn($l) => [
                'label' => $l->section->name ?? $l->section_name ?? ('Standard ' . $l->id),
                'count' => $l->studentCount ?? 0,
                'male'  => $l->maleCount ?? 0,
                'female'=> $l->femaleCount ?? 0,
                'unknown'=> $l->unknownCount ?? 0,
            ])->values()) !!};
            var barChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: standardData.map(function(d) { return d.label; }),
                    datasets: [{
                        label: 'Boys',
                        data: standardData.map(function(d) { return d.male; }),
                        backgroundColor: '#304ffe',
                        borderRadius: 6,
                    }, {
                        label: 'Girls',
                        data: standardData.map(function(d) { return d.female; }),
                        backgroundColor: '#ffa601',
                        borderRadius: 6,
                    }, {
                        label: 'Unspecified',
                        data: standardData.map(function(d) { return d.unknown; }),
                        backgroundColor: '#cbd5e1',
                        borderRadius: 6,
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1,
                                precision: 0,
                            }
                        }]
                    },
                    legend: { display: false },
                }
            });
        }

    </script>
    @endif
@endpush
