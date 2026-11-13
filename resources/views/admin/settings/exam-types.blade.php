{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.settings-nav')

@include('partials.message')

@php
    $mixTotal = rtrim(rtrim(number_format((float) ($mix['total'] ?? 0), 2, '.', ''), '0'), '.');
    $mixAdds = (bool) ($mix['adds_up'] ?? false);
@endphp

<div class="ka-compose">
    <nav class="ka-crumb" aria-label="Breadcrumb"><a href="{{ route('admin.reports.cards.index') }}">Report cards</a> <span aria-hidden="true">/</span> Report settings</nav>
    <div>
        <h1 class="ka-h1">Combined report</h1>
        <p class="ka-compose-lead">Each exam set either counts toward the combined report, with a weight, or is shown on its own. Every exam set still has its own report when it is released. One combined report is kept for the term. Another, such as a year-end report, can be added later.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.exam-types.update') }}" data-testid="report-composition-form">
        @csrf
        @error('weight') <p class="ka-compose-error" data-testid="composition-weight-error">{{ $message }}</p> @enderror
        @error('include') <p class="ka-compose-error">{{ $message }}</p> @enderror

        <div class="ka-compose-layout">
            <section class="ka-card ka-card-pad ka-compose-main" aria-labelledby="h-mix">
                <h2 id="h-mix" class="ka-h2">How each exam set appears</h2>

                <div class="ka-quick-wrap">
                    <span class="ka-lbl">Quick choices</span>
                    <div class="ka-quick" role="group" aria-label="Quick choices">
                        <button type="button" class="ka-choice" data-choice="last" aria-pressed="{{ $preset === 'last' ? 'true' : 'false' }}">Last set only</button>
                        <button type="button" class="ka-choice" data-choice="equal" aria-pressed="{{ $preset === 'equal' ? 'true' : 'false' }}">All sets equally</button>
                    </div>
                </div>

                <div class="ka-mix-list" data-testid="exam-type-table">
                    @foreach($examTypes as $type)
                        @php
                            $checked = $prefs[$type->id] ?? $type->contributes_to_report_total;
                            $settings = $composition[(string) $type->id] ?? $composition[$type->id] ?? [];
                            $included = is_array($settings) && array_key_exists('include', $settings)
                                ? ($settings['include'] === true || $settings['include'] === 1 || $settings['include'] === '1')
                                : true;
                            $weight = is_array($settings) ? ($settings['weight'] ?? null) : null;
                            $weightNumber = is_numeric($weight) ? (float) $weight : null;
                            $weightValue = $weightNumber === null
                                ? ''
                                : rtrim(rtrim(number_format($weightNumber, 2, '.', ''), '0'), '.');
                        @endphp
                        <div class="ka-mix-row{{ $included ? '' : ' is-off' }}" data-mix-row data-name="{{ $type->name }}" data-testid="exam-type-row-{{ $type->id }}">
                            <input type="hidden" name="contributes[{{ $type->id }}]" value="{{ $checked ? '1' : '0' }}">
                            <div class="ka-mix-label">
                                <strong>{{ $type->name }}</strong>
                                <span data-mix-detail>{{ $included ? 'Counts toward the combined report' : 'Shown on its own' }}</span>
                                <span class="ka-sr" data-testid="exam-type-owner-{{ $type->id }}">{{ $type->code }} · {{ $type->isShared() ? '(shared)' : '(your school)' }}</span>
                                <label class="ka-hit" style="min-height:44px;">
                                    <input type="radio" name="mode[{{ $type->id }}]" value="count" data-mode="count" data-testid="exam-type-include-{{ $type->id }}" @checked($included)>
                                    Counts toward the combined report
                                </label>
                                <label class="ka-hit" style="min-height:44px;">
                                    <input type="radio" name="mode[{{ $type->id }}]" value="alone" data-mode="alone" @checked(! $included)>
                                    Shown on its own
                                </label>
                            </div>
                            <span class="ka-hide-sm ka-weight-label">Weight</span>
                            <input class="ds-input ka-weight" type="text" inputmode="numeric" name="weight[{{ $type->id }}]"
                                value="{{ $weightValue }}" @disabled(! $included)
                                aria-label="Weight for {{ $type->name }}, percent"
                                data-testid="exam-type-weight-{{ $type->id }}">
                            <span class="ka-weight-unit">%</span>
                        </div>
                    @endforeach
                    <div class="ka-mix-total" aria-live="polite">
                        <strong>Total</strong>
                        <span class="ka-adds{{ $mixAdds ? ' is-ok' : '' }}" data-mix-state>
                            <svg data-mix-tick width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
                            <span data-mix-state-text>{{ $mixAdds ? 'Adds up' : 'Does not add up' }}</span>
                        </span>
                        <strong class="ka-mix-sum" data-mix-total>{{ $mixTotal }}%</strong>
                    </div>
                </div>

                <div class="ka-mix-bar" role="img" aria-label="{{ $mix['aria'] ?? 'No exam sets count on the end-of-term report' }}" data-mix-bar>
                    @foreach(($mix['parts'] ?? []) as $index => $part)
                        <div class="{{ $index === count($mix['parts']) - 1 ? 'is-last' : 'is-part' }}" style="flex: {{ $part['weight'] }} 1 0;">{{ $part['name'] }} {{ rtrim(rtrim(number_format($part['weight'], 2, '.', ''), '0'), '.') }}%</div>
                    @endforeach
                </div>

                <div class="ka-note">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 7.5h.01"></path></svg>
                    <div><strong>Applies from the next release.</strong> Reports already released keep the mix they were released with.</div>
                </div>

                <div class="ka-compose-foot">
                    <p class="ka-compose-who">Admins and head teachers can change this.</p>
                    <div class="ka-compose-actions">
                        <a class="ds-btn ds-btn-outline ka-hit" href="{{ route('admin.settings.exam-types') }}">Cancel</a>
                        <button type="submit" class="ds-btn ds-btn-primary ka-hit" style="min-height:44px;" data-testid="exam-type-save">Save report settings</button>
                    </div>
                </div>
            </section>

            <div class="ka-compose-side">
                <section class="ka-card ka-card-pad" aria-labelledby="h-prev">
                    <h2 id="h-prev" class="ka-h2">How it appears on the report</h2>
                    @if($madeUpOf !== '')
                        <p class="ka-made" data-testid="made-up-of">{{ $madeUpOf }}</p>
                    @endif
                    @if($showMarksLater)
                        <p class="ka-made-wait">Report marks appear here after the counted exam sets are released.</p>
                    @endif
                </section>

                <section class="ka-card ka-card-pad" aria-labelledby="h-own">
                    <h2 id="h-own" class="ka-h2">Each exam set has its own report</h2>
                    @foreach($ownReports as $own)
                        <div class="ka-own-row" data-testid="own-report-{{ $own['id'] }}">
                            <strong>{{ $own['name'] }}</strong>
                            <span class="ka-pill {{ $own['released'] ? 'ka-pill-green' : 'ka-pill-muted' }}">{{ $own['status'] }}</span>
                        </div>
                    @endforeach
                    <p class="ka-own-note">These aren't affected by the weights above.</p>
                </section>

                <section class="ka-card ka-card-pad" aria-labelledby="h-log">
                    <h2 id="h-log" class="ka-h2">Changes to this setting</h2>
                    <p class="ka-log" data-testid="composition-log">Changes apply from the next release. A released report keeps the mix it was released with. Until you save, reports keep each exam set's current total setting.</p>
                </section>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.settings.exam-types.store') }}" class="mt-8 border-t pt-6" data-testid="exam-type-create">
        @csrf
        <h3 class="font-semibold text-sm mb-1">Add your own exam type</h3>
        <p class="text-sm text-gray-500 mb-3">
            A type your school uses that the shared catalog does not cover, such as a mock or a monthly test.
            It appears in your exam types, in the exam set form and in report settings. No other school sees it.
        </p>
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="form-label" for="exam-type-name">Name</label>
                <input type="text" name="name" id="exam-type-name" class="ds-form-input" style="width: 220px;"
                       value="{{ old('name') }}" maxlength="80" required placeholder="Mock Examination" data-testid="exam-type-name">
            </div>
            <div>
                <label class="form-label" for="exam-type-code">Short code</label>
                <input type="text" name="code" id="exam-type-code" class="ds-form-input" style="width: 110px;"
                       value="{{ old('code') }}" maxlength="10" required placeholder="MOCK" data-testid="exam-type-code">
            </div>
            <div>
                <label class="form-label" for="exam-type-short">Phone name</label>
                <input type="text" name="short_name" id="exam-type-short" class="ds-form-input" style="width: 120px;"
                       value="{{ old('short_name') }}" maxlength="24" placeholder="First word if empty" data-testid="exam-type-short">
            </div>
            <div style="padding-bottom: 9px;">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="contributes_to_report_total" value="1"
                           @checked(old('contributes_to_report_total')) data-testid="exam-type-contributes">
                    <span>Counts towards the report total</span>
                </label>
            </div>
            <button type="submit" class="ds-btn ds-btn-primary" data-testid="exam-type-create-submit">
                Add exam type
            </button>
        </div>
        @error('name') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
        @error('code') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
    </form>

    <form method="POST" action="{{ route('admin.settings.exam-types.profile') }}" class="mt-8 border-t pt-6">
        @csrf
        <h3 class="font-semibold text-sm mb-1">Curriculum profile</h3>
        <p class="text-sm text-gray-500 mb-3">
            Which grading scale applies to this school's O-level and A-level reports.
            Applying a profile refreshes the school's grading bands immediately.
        </p>
        <select name="curriculum_profile" class="ds-form-input" style="width: 340px;">
            @foreach($profiles as $key => $profile)
                <option value="{{ $key }}" {{ $profileKey === $key ? 'selected' : '' }}>{{ $profile['label'] }}</option>
            @endforeach
        </select>
        <button type="submit" class="ds-btn ds-btn-primary ml-3">
            Apply Profile
        </button>
    </form>

    <form method="POST" action="{{ route('admin.settings.exam-types.bands') }}" class="mt-8 border-t pt-6">
        @csrf
        <h3 class="font-semibold text-sm mb-1">Legacy O-level bands (this school's own scale)</h3>
        <p class="text-sm text-gray-500 mb-1">
            Used only when the curriculum profile is the old (pre-NLSC) one. UNEB never published its legacy cut-offs
            as one fixed national table, so these boundaries belong to your school. The letters and points are fixed;
            you set the marks.
        </p>
        <p class="text-sm {{ $legacyIsExample ? 'text-amber-700' : 'text-gray-500' }} mb-3" data-testid="legacy-bands-state">
            {{ $legacyIsExample
                ? 'Showing an example table, not a national standard. Nothing is saved until you save it.'
                : "Showing this school's saved table." }}
        </p>
        @error('bands')<p class="text-sm text-red-600 mb-2" data-testid="legacy-bands-error">{{ $message }}</p>@enderror
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b">
                    <th class="py-2 px-3 font-semibold">Grade</th>
                    <th class="py-2 px-3 font-semibold">Points</th>
                    <th class="py-2 px-3 font-semibold">From</th>
                    <th class="py-2 px-3 font-semibold">To</th>
                </tr>
            </thead>
            <tbody>
                @foreach($legacyBands as $band)
                    <tr class="border-b">
                        <td class="py-2 px-3 font-medium">{{ $band['grade'] }}</td>
                        <td class="py-2 px-3 text-gray-500">{{ $band['points'] ?? '—' }}</td>
                        <td class="py-2 px-3"><input type="number" min="0" max="100" name="bands[{{ $band['grade'] }}][min_score]" value="{{ old('bands.'.$band['grade'].'.min_score', $band['min_score']) }}" class="w-24 rounded border-gray-300"></td>
                        <td class="py-2 px-3"><input type="number" min="0" max="100" name="bands[{{ $band['grade'] }}][max_score]" value="{{ old('bands.'.$band['grade'].'.max_score', $band['max_score']) }}" class="w-24 rounded border-gray-300"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="submit" class="mt-3 px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900" data-testid="legacy-bands-save">
            Save this school's bands
        </button>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-testid="report-composition-form"]');
    if (!form) return;

    function rows() {
        return Array.prototype.slice.call(form.querySelectorAll('[data-mix-row]'));
    }

    function trimNum(value) {
        var text = String(Math.round(value * 100) / 100);
        return text;
    }

    function setRow(row, on, weight) {
        var count = row.querySelector('[data-mode="count"]');
        var alone = row.querySelector('[data-mode="alone"]');
        var input = row.querySelector('.ka-weight');
        if (count) count.checked = !!on;
        if (alone) alone.checked = !on;
        input.disabled = !on;
        input.value = on ? String(weight) : '';
        row.classList.toggle('is-off', !on);
    }

    function split(list, total) {
        if (!list.length || total <= 0) {
            list.forEach(function (row) { setRow(row, false, 0); });
            return;
        }
        var base = Math.floor(total / list.length);
        var extra = total - (base * list.length);
        list.forEach(function (row, index) {
            setRow(row, true, base + (index < extra ? 1 : 0));
        });
    }

    function paint() {
        var parts = [];
        var sum = 0;
        rows().forEach(function (row) {
            var count = row.querySelector('[data-mode="count"]');
            var input = row.querySelector('.ka-weight');
            var on = !!(count && count.checked);
            input.disabled = !on;
            row.classList.toggle('is-off', !on);
            var detail = row.querySelector('[data-mix-detail]');
            if (detail) detail.textContent = on ? 'Counts toward the combined report' : 'Shown on its own';
            var weight = parseFloat(input.value);
            if (on && isFinite(weight) && weight > 0) {
                sum += weight;
                parts.push({ name: row.getAttribute('data-name') || '', weight: weight });
            }
        });
        var adds = parts.length > 0 && Math.abs(sum - 100) < 0.011;
        var state = form.querySelector('[data-mix-state]');
        var text = form.querySelector('[data-mix-state-text]');
        var total = form.querySelector('[data-mix-total]');
        if (state) state.classList.toggle('is-ok', adds);
        if (text) text.textContent = adds ? 'Adds up' : 'Does not add up';
        if (total) total.textContent = trimNum(sum) + '%';
        var bar = form.querySelector('[data-mix-bar]');
        if (!bar) return;
        var label = parts.length
            ? parts.map(function (part) { return part.name + ' ' + trimNum(part.weight) + ' percent'; }).join(', ')
            : 'No exam sets count toward the combined report';
        bar.setAttribute('aria-label', label);
        bar.textContent = '';
        parts.forEach(function (part, index) {
            var el = document.createElement('div');
            el.className = index === parts.length - 1 ? 'is-last' : 'is-part';
            el.style.flex = String(part.weight) + ' 1 0';
            el.textContent = part.name + ' ' + trimNum(part.weight) + '%';
            bar.appendChild(el);
        });
    }

    function apply(choice) {
        var list = rows();
        if (choice === 'last') {
            list.forEach(function (row, index) {
                setRow(row, index === list.length - 1, index === list.length - 1 ? 100 : 0);
            });
        } else if (choice === 'equal') {
            split(list, 100);
        }
        form.querySelectorAll('[data-choice]').forEach(function (button) {
            button.setAttribute('aria-pressed', button.getAttribute('data-choice') === choice ? 'true' : 'false');
        });
        paint();
    }

    form.querySelectorAll('[data-choice]').forEach(function (button) {
        button.addEventListener('click', function () {
            apply(button.getAttribute('data-choice'));
        });
    });
    rows().forEach(function (row) {
        var count = row.querySelector('[data-mode="count"]');
        var alone = row.querySelector('[data-mode="alone"]');
        var input = row.querySelector('.ka-weight');
        function choose() {
            if (alone && alone.checked) input.value = '';
            form.querySelectorAll('[data-choice]').forEach(function (button) {
                button.setAttribute('aria-pressed', 'false');
            });
            paint();
        }
        if (count) count.addEventListener('change', choose);
        if (alone) alone.addEventListener('change', choose);
        input.addEventListener('input', function () {
            form.querySelectorAll('[data-choice]').forEach(function (button) {
                button.setAttribute('aria-pressed', 'false');
            });
            paint();
        });
    });
});
</script>
@endpush
