{{--
  SPDX-License-Identifier: MIT

  Shared Chart.js v4 wrapper (Step 3).

  Informed by mary's Livewire/Chart.js bridge *pattern* (unique chart instance per
  mount so re-renders never reuse a stale canvas, the config carried as data, the
  instance created in an init hook and destroyed on teardown) — implemented here
  as KlassApp's own Alpine-based component on KlassApp's design tokens.

  Props
    id            optional DOM id (auto uuid when omitted) — also the handle key
    type          line | bar | doughnut | pie | radar
    labels        array of axis labels
    datasets      array of Chart.js v4 dataset arrays
    options       Chart.js v4 options (merged with the KlassApp defaults)
    height        canvas height in px (default 260)
    ariaLabel     accessible description of the chart
    emptyMessage  shown instead of the canvas when every dataset is empty
    centerValue   draws this value in the middle of a doughnut (KlassApp plugin)
    optionsJs     optional JS object literal deep-merged over `options` at init —
                  the only place callbacks live, because JSON cannot carry
                  functions. Kept explicit so data/options stay JSON-encoded.

  Usage
    <x-chart type="line" :labels="$labels" :datasets="$datasets" :height="180"
             aria-label="Fee collection trend" />
--}}
@props([
    'id' => null,
    'type' => 'line',
    'labels' => [],
    'datasets' => [],
    'options' => [],
    'height' => 260,
    'ariaLabel' => null,
    'emptyMessage' => 'No data yet',
    'centerValue' => null,
    'optionsJs' => null,
])

@php
    $chartId = $id ?: 'ds-chart-'.\Illuminate\Support\Str::uuid();

    // KlassApp defaults; page options are merged over them.
    $defaults = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => ['display' => false],
            'tooltip' => [
                'backgroundColor' => '#0F172A',
                'titleFont' => ['family' => 'Sora'],
                'bodyFont' => ['family' => 'DM Sans'],
            ],
        ],
        'scales' => [
            'x' => [
                'ticks' => ['font' => ['family' => 'DM Sans', 'size' => 11]],
                'grid' => ['display' => false],
            ],
            'y' => [
                'beginAtZero' => true,
                'ticks' => ['font' => ['family' => 'DM Sans', 'size' => 11]],
                'grid' => ['color' => '#F1F5F9'],
                'border' => ['display' => false],
            ],
        ],
    ];

    if (in_array($type, ['doughnut', 'pie'], true)) {
        unset($defaults['scales']);
    }

    $config = [
        'type' => $type,
        'data' => [
            'labels' => array_values($labels),
            'datasets' => array_values($datasets),
        ],
        'options' => array_replace_recursive($defaults, $options),
    ];

    // Doughnut centre value (KlassApp's own v4 plugin; replaces the old
    // Chart.pluginService.register afterDraw hack from the v2 code).
    if ($centerValue !== null) {
        $config['options']['plugins']['dsCenterValue'] = ['value' => (string) $centerValue];
    }

    $hasData = collect($datasets)->contains(
        fn ($d) => collect($d['data'] ?? [])->contains(fn ($v) => $v !== null && $v !== '' && (float) $v != 0.0)
    );
@endphp

@once
    @push('scripts')
        <script src="{{ asset('js/chart.umd.min.js') }}?v=4.5.1"></script>
        <script>
        (function () {
            if (window.__dsChartBoot) return;
            window.__dsChartBoot = true;

            // Doughnut centre value (KlassApp plugin; replaces the v2
            // Chart.pluginService.register afterDraw hack).
            if (window.Chart) {
                window.Chart.register({
                    id: 'dsCenterValue',
                    afterDraw(chart, args, opts) {
                        if (!opts || opts.value === undefined || opts.value === null) return;
                        const { ctx, chartArea } = chart;
                        if (!chartArea) return;
                        ctx.save();
                        const em = (chartArea.bottom - chartArea.top) / 140;
                        ctx.font = '700 ' + em.toFixed(2) + 'em Sora, sans-serif';
                        ctx.textBaseline = 'middle';
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#4d4c48';
                        ctx.fillText(opts.value, (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
                        ctx.restore();
                    },
                });
            }

            function merge(a, b) {
                Object.keys(b || {}).forEach(function (k) {
                    a[k] = (b[k] && typeof b[k] === 'object' && ! Array.isArray(b[k]))
                        ? merge(a[k] && typeof a[k] === 'object' ? a[k] : {}, b[k])
                        : b[k];
                });
                return a;
            }

            function build(shell) {
                var key = shell.dataset.chartId;
                var canvas = shell.querySelector('canvas');
                if (!canvas || !window.Chart) return;

                // Idempotent per LIVE node: Chart.js tracks the instance on the
                // canvas itself, so this is true only for the node currently in the
                // document — the reliable test when Vue has replaced an earlier one.
                if (window.Chart.getChart && window.Chart.getChart(canvas)) return;

                var rawCfg = shell.dataset.chartConfig;
                if (!rawCfg) return;

                window.__dsCharts = window.__dsCharts || {};
                var stale = window.__dsCharts[key];
                if (stale) { try { stale.destroy(); } catch (e) {} }

                var cfg = JSON.parse(rawCfg);
                var jsOpts = shell.dataset.chartJsopts;
                if (jsOpts && jsOpts.trim() !== '') {
                    cfg.options = merge(cfg.options || {}, (new Function('return (' + jsOpts + ');'))());
                }

                // Vue's deferred mount (and any later re-render) replaces the
                // server-rendered canvas; recreate on the live node instead of
                // drawing into an orphan, which rendered blank before.
                window.__dsCharts[key] = new window.Chart(canvas.getContext('2d'), cfg);
            }

            function bootAll() {
                document.querySelectorAll('.ds-chart-shell[data-chart-id]').forEach(build);
            }
            window.__dsChartBootAll = bootAll;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootAll, { once: true });
            } else {
                bootAll();
            }
            window.addEventListener('load', bootAll);
            [200, 600, 1200, 2000, 3000].forEach(function (t) { setTimeout(bootAll, t); });
            // Keep re-checking briefly while the SPA shell settles: each pass is a
            // no-op unless a live canvas lacks an instance.
            var settles = 0;
            var timer = setInterval(function () {
                bootAll();
                if (++settles >= 40) clearInterval(timer);
            }, 500);
            if (window.MutationObserver) {
                new MutationObserver(bootAll).observe(document.body, { childList: true, subtree: true });
            }
        })();
        </script>
    @endpush
@endonce

<div class="ds-chart-shell" style="height: {{ (int) $height }}px;"
     data-chart-id="{{ $chartId }}"
     @if($hasData)
     data-chart-config="{{ json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
     @if($optionsJs !== null) data-chart-jsopts="{{ $optionsJs }}" @endif
     @endif>
    @if($hasData)
        <canvas id="{{ $chartId }}" role="img" aria-label="{{ $ariaLabel ?? 'Chart' }}"></canvas>
    @else
        <div class="ds-chart-empty" role="img" aria-label="{{ $ariaLabel ?? 'Chart' }}: {{ $emptyMessage }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="22" height="22" aria-hidden="true">
                <path d="M3 3v18h18"/><path d="M7 15l3-4 3 3 4-6"/>
            </svg>
            <p>{{ $emptyMessage }}</p>
        </div>
    @endif
</div>
