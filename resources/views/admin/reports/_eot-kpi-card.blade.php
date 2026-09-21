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
<div class="bg-white custom-shadow px-5 py-4 border dashboard-chart-card mt-4">
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

    @php
        // The tab bar switches between these three series; the chart itself is the
        // shared chart component (Chart.js v4), driven through its handle.
        $eotFirstTab = collect(['perClass', 'perSubject', 'perGender'])
            ->first(fn ($k) => ! empty($eotKpis[$k]));
        $eotSeries = $eotFirstTab ? collect($eotKpis[$eotFirstTab]) : collect();
        $eotHues = ['#4F46E5', '#7C3AED', '#2563EB', '#0891B2', '#0D9488', '#059669', '#65A30D', '#CA8A04', '#EA580C', '#DC2626'];
        $eotCount = max(1, $eotSeries->count());
    @endphp
    <x-chart id="eotKpiChart" type="bar" :height="320"
             aria-label="End of term performance"
             empty-message="No performance data yet"
             :labels="$eotSeries->pluck('label')->all()"
             :datasets="[[
                 'label' => 'Avg Score',
                 'data' => $eotSeries->map(fn ($r) => (float) $r->value)->values()->all(),
                 'backgroundColor' => $eotSeries->keys()->map(fn ($i) => $eotHues[(int) round($i / max(1, $eotCount - 1) * (count($eotHues) - 1))])->all(),
                 'borderRadius' => 4,
             ]]"
             :options="['scales' => ['x' => ['ticks' => ['autoSkip' => false, 'maxRotation' => 45, 'minRotation' => 0]]]]"
             options-js='{ plugins: { tooltip: { callbacks: { label: function (c) { return "Avg: " + c.parsed.y; } } } } }' />
</div>

@push('scripts')
<script>
(function () {
    var kpi = @json($eotKpis);

    var tabKeys = Object.keys(kpi).filter(function (k) { return kpi[k] && kpi[k].length > 0; });
    if (tabKeys.length === 0) return;

    var defaultTab = tabKeys[0];

    function barColor(index, total) {
        var hues = ['#4F46E5', '#7C3AED', '#2563EB', '#0891B2', '#0D9488',
                    '#059669', '#65A30D', '#CA8A04', '#EA580C', '#DC2626'];
        var ratio = total > 1 ? index / (total - 1) : 0;
        var idx = Math.round(ratio * (hues.length - 1));
        return hues[idx] || hues[0];
    }

    // The shared chart component owns the Chart.js v4 instance; we only push
    // new labels/data into it. Retries briefly while the component boots.
    function buildChart(tab, attempt) {
        var data = kpi[tab];
        if (!data || data.length === 0) return;

        var chart = (window.__dsCharts || {})['eotKpiChart'];
        if (!chart) {
            if ((attempt || 0) < 12) { setTimeout(function () { buildChart(tab, (attempt || 0) + 1); }, 250); }
            return;
        }

        var values = data.map(function (d) { return parseFloat(d.value); });
        chart.data.labels = data.map(function (d) { return d.label; });
        chart.data.datasets[0].data = values;
        chart.data.datasets[0].backgroundColor = values.map(function (_, i) { return barColor(i, values.length); });
        chart.update();
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { activateTab(defaultTab); });
    } else {
        activateTab(defaultTab);
    }
})();
</script>
@endpush
@endif
