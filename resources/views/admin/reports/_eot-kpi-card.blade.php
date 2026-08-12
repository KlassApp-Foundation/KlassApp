{{-- SPDX-License-Identifier: MIT --}}
@php
    $hasAnyData = !empty($eotKpis)
        && (
            !empty($eotKpis['perClass'])
            || !empty($eotKpis['perSubject'])
            || !empty($eotKpis['perGender'])
        );
@endphp

@if($hasAnyData)
<div class="bg-white custom-shadow px-5 py-4 border dashboard-kpi-card mt-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-gray-800 font-semibold text-xl dashboard-panel-title">EOT Performance</h3>
    </div>

    <div class="flex gap-1 border-b border-gray-200 mb-4" id="eot-tab-bar">
        @if(!empty($eotKpis['perClass']))
        <button onclick="switchEotTab('perClass')"
                class="eot-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-indigo-500 text-indigo-600 -mb-px bg-transparent transition"
                data-tab="perClass">
            Per Class
        </button>
        @endif
        @if(!empty($eotKpis['perSubject']))
        <button onclick="switchEotTab('perSubject')"
                class="eot-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 -mb-px bg-transparent transition"
                data-tab="perSubject">
            Per Subject
        </button>
        @endif
        @if(!empty($eotKpis['perGender']))
        <button onclick="switchEotTab('perGender')"
                class="eot-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 -mb-px bg-transparent transition"
                data-tab="perGender">
            Per Gender
        </button>
        @endif
    </div>

    <div style="height: 320px; position: relative;">
        <canvas id="eotKpiChart" class="dashboard-chart-canvas"></canvas>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/Chart.min.js') }}?v=2.9.3"></script>
<script>
(function () {
    var kpi = @json($eotKpis);

    var tabKeys = Object.keys(kpi).filter(function (k) { return kpi[k] && kpi[k].length > 0; });
    if (tabKeys.length === 0) return;

    var eotChart = null;
    var defaultTab = tabKeys[0];

    function barColor(index, total) {
        var hues = ['#4F46E5', '#7C3AED', '#2563EB', '#0891B2', '#0D9488',
                    '#059669', '#65A30D', '#CA8A04', '#EA580C', '#DC2626'];
        var ratio = total > 1 ? index / (total - 1) : 0;
        var idx = Math.round(ratio * (hues.length - 1));
        return hues[idx] || hues[0];
    }

    function buildChart(tab) {
        var data = kpi[tab];
        if (!data || data.length === 0) return;

        var labels = data.map(function (d) { return d.label; });
        var values = data.map(function (d) { return parseFloat(d.value); });
        var colors = values.map(function (_, i) { return barColor(i, values.length); });

        if (eotChart) { eotChart.destroy(); eotChart = null; }

        var ctx = document.getElementById('eotKpiChart').getContext('2d');
        eotChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Score',
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: {
                    callbacks: {
                        label: function (item) { return 'Avg: ' + item.yLabel; }
                    }
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 0,
                            fontFamily: "'DM Sans', sans-serif",
                            fontSize: 11,
                        },
                        gridLines: { display: false }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontFamily: "'DM Sans', sans-serif",
                            fontSize: 11,
                        },
                        gridLines: {
                            color: '#F1F5F9',
                            zeroLineColor: '#E2E8F0',
                        }
                    }]
                }
            }
        });
    }

    function activateTab(tab) {
        document.querySelectorAll('.eot-tab-btn').forEach(function (btn) {
            if (btn.dataset.tab === tab) {
                btn.classList.add('border-indigo-500', 'text-indigo-600');
                btn.classList.remove('border-transparent', 'text-gray-500');
            } else {
                btn.classList.remove('border-indigo-500', 'text-indigo-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            }
        });
        buildChart(tab);
    }

    window.switchEotTab = function (tab) {
        activateTab(tab);
    };

    // Initial render
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { activateTab(defaultTab); });
    } else {
        activateTab(defaultTab);
    }
})();
</script>
@endpush
@endif
