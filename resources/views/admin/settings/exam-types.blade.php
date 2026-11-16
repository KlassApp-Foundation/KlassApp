{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Exam Type Settings',
    'subtitle' => 'Shared exam types sit next to the ones your school adds. Toggle which types count towards term/report-card totals; changes apply to future report generation only.',
])

@include('layouts.partials.settings-nav')

@include('partials.message')

@include('admin.reports.partials.report-kind')

@if (($reportKind ?? 'progressive') !== 'set_based')
    <p data-testid="composition-hidden" style="margin:0 0 12px;color:#334155;font-size:.9rem;">
        Show on report cards and weight are for set-based reports. This school uses the Progressive report, so those columns stay hidden. Choose Set-based reports above to edit them.
    </p>
@endif

<div class="relative mt-4">
<div class="w-full main-content">
    @if (($reportKind ?? 'progressive') !== 'set_based')
    <form method="POST" action="{{ route('admin.settings.exam-types.update') }}">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left" data-testid="exam-type-table">
                <thead>
                    <tr class="border-b">
                        <th class="py-3 px-4 font-semibold text-sm">Exam Type</th>
                        <th class="py-3 px-4 font-semibold text-sm">Code</th>
                        <th class="py-3 px-4 font-semibold text-sm">Contributes to Report Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($examTypes as $type)
                        @php
                            $checked = $prefs[$type->id] ?? $type->contributes_to_report_total;
                            $shown = ($labelNames[(int) $type->id] ?? null) ?: $type->name;
                        @endphp
                        <tr class="border-b hover:bg-gray-50" data-testid="exam-type-row-{{ $type->id }}">
                            <td class="py-3 px-4">
                                {{ $shown }}
                                <span class="ml-2 text-xs" data-testid="exam-type-owner-{{ $type->id }}"
                                      style="color: {{ $type->isShared() ? 'var(--d-text-secondary)' : 'var(--d-accent)' }};">
                                    {{ $type->isShared() ? '(shared)' : '(your school)' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-gray-500">{{ $type->code }}</td>
                            <td class="py-3 px-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="contributes[{{ $type->id }}]" value="0">
                                    <input type="checkbox" name="contributes[{{ $type->id }}]" value="1"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        {{ $checked ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm {{ $checked ? 'text-green-700 font-medium' : 'text-gray-400' }}">
                                        {{ $checked ? 'Yes' : 'No' }}
                                    </span>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            <button type="submit" class="ds-btn ds-btn-primary">
                Save Preferences
            </button>
        </div>
    </form>
    @else
    @php
        $mixTotal = rtrim(rtrim(number_format((float) ($mix['total'] ?? 0), 2, '.', ''), '0'), '.');
        $mixAdds = (bool) ($mix['adds_up'] ?? false);
    @endphp
    <form method="POST" action="{{ route('admin.settings.exam-types.update') }}" data-testid="report-composition-form">
        @csrf
        @error('weight') <p data-testid="composition-weight-error">{{ $message }}</p> @enderror
        @error('include') <p>{{ $message }}</p> @enderror
        <p>Show on Report Cards</p>
        <p>Weight %</p>
        <div role="group" aria-label="Quick choices">
            <button type="button" data-choice="last" aria-pressed="{{ ($preset ?? '') === 'last' ? 'true' : 'false' }}">Last set only</button>
            <button type="button" data-choice="equal" aria-pressed="{{ ($preset ?? '') === 'equal' ? 'true' : 'false' }}">All sets equally</button>
        </div>
        <div data-testid="exam-type-table">
            @foreach($examTypes as $type)
                @php
                    $checked = $prefs[$type->id] ?? $type->contributes_to_report_total;
                    $settings = $composition[(string) $type->id] ?? $composition[$type->id] ?? [];
                    $included = is_array($settings) && array_key_exists('include', $settings)
                        ? ($settings['include'] === true || $settings['include'] === 1 || $settings['include'] === '1')
                        : true;
                    $weight = is_array($settings) ? ($settings['weight'] ?? null) : null;
                    $weightNumber = is_numeric($weight) ? (float) $weight : null;
                    $weightValue = $weightNumber === null ? '' : rtrim(rtrim(number_format($weightNumber, 2, '.', ''), '0'), '.');
                    $shown = ($labelNames[(int) $type->id] ?? null) ?: $type->name;
                @endphp
                <div data-mix-row data-name="{{ $shown }}" data-testid="exam-type-row-{{ $type->id }}">
                    <input type="hidden" name="contributes[{{ $type->id }}]" value="{{ $checked ? '1' : '0' }}">
                    <strong>{{ $shown }}</strong>
                    <span data-mix-detail>{{ $included ? 'Counts toward the combined report' : 'Shown on its own' }}</span>
                    <span data-testid="exam-type-owner-{{ $type->id }}">{{ $type->code }} · {{ $type->isShared() ? '(shared)' : '(your school)' }}</span>
                    <input type="text" name="type_name[{{ $type->id }}]" value="{{ $shown }}">
                    <label style="min-height:44px;">
                        <input type="radio" name="mode[{{ $type->id }}]" value="count" data-mode="count" data-testid="exam-type-include-{{ $type->id }}" @checked($included)>
                        Counts toward the combined report
                    </label>
                    <label style="min-height:44px;">
                        <input type="radio" name="mode[{{ $type->id }}]" value="alone" data-mode="alone" @checked(! $included)>
                        Shown on its own
                    </label>
                    <input type="text" inputmode="numeric" name="weight[{{ $type->id }}]" value="{{ $weightValue }}" @disabled(! $included)
                        aria-label="Weight for {{ $shown }}, percent" data-testid="exam-type-weight-{{ $type->id }}">
                </div>
            @endforeach
            <div>
                <strong>Total</strong>
                <span data-mix-state>
                    <span data-mix-state-text>{{ $mixAdds ? 'Adds up' : 'Does not add up' }}</span>
                </span>
                <strong data-mix-total>{{ $mixTotal }}%</strong>
            </div>
        </div>
        <div data-mix-bar role="img" aria-label="{{ $mix['aria'] ?? 'No exam sets count toward the combined report' }}"></div>
        <div><strong>Applies from the next release.</strong> Reports already released keep the mix they were released with.</div>
        <p>Admins and head teachers can change this.</p>
        <a href="{{ route('admin.settings.exam-types') }}">Cancel</a>
        <button type="submit" style="min-height:44px;" data-testid="exam-type-save">Save report settings</button>
        @if(($madeUpOf ?? '') !== '')
            <p data-testid="made-up-of">{{ $madeUpOf }}</p>
        @endif
        @if($showMarksLater ?? false)
            <p>Report marks appear here after the counted exam sets are released.</p>
        @endif
        <h2>Each exam set has its own report</h2>
        @foreach(($ownReports ?? []) as $own)
            <div data-testid="own-report-{{ $own['id'] }}">
                <strong>{{ $own['name'] }}</strong>
                <span>{{ $own['status'] }}</span>
            </div>
        @endforeach
        <p data-testid="composition-log">Changes apply from the next release. A released report keeps the mix it was released with. Until you save, reports keep each exam set's current total setting.</p>
    </form>
    @endif

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
</div>
@endsection

@push('scripts')
@if (($reportKind ?? 'progressive') === 'set_based')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-testid="report-composition-form"]');
    if (!form) return;

    function rows() {
        return Array.prototype.slice.call(form.querySelectorAll('[data-mix-row]'));
    }

    function setRow(row, on, weight) {
        var count = row.querySelector('[data-mode="count"]');
        var alone = row.querySelector('[data-mode="alone"]');
        var input = row.querySelector('[data-testid^="exam-type-weight-"]');
        if (count) count.checked = !!on;
        if (alone) alone.checked = !on;
        if (!input) return;
        input.disabled = !on;
        input.value = on ? String(weight) : '';
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
        var sum = 0;
        var parts = [];
        rows().forEach(function (row) {
            var count = row.querySelector('[data-mode="count"]');
            var input = row.querySelector('[data-testid^="exam-type-weight-"]');
            var on = !!(count && count.checked);
            if (input) input.disabled = !on;
            var detail = row.querySelector('[data-mix-detail]');
            if (detail) detail.textContent = on ? 'Counts toward the combined report' : 'Shown on its own';
            var weight = input ? parseFloat(input.value) : NaN;
            if (on && isFinite(weight) && weight > 0) {
                sum += weight;
                parts.push({ name: row.getAttribute('data-name') || '', weight: weight });
            }
        });
        var adds = parts.length > 0 && Math.abs(sum - 100) < 0.011;
        var text = form.querySelector('[data-mix-state-text]');
        var total = form.querySelector('[data-mix-total]');
        if (text) text.textContent = adds ? 'Adds up' : 'Does not add up';
        if (total) total.textContent = String(Math.round(sum * 100) / 100) + '%';
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
        paint();
    }

    form.querySelectorAll('[data-choice]').forEach(function (button) {
        button.addEventListener('click', function () {
            apply(button.getAttribute('data-choice'));
        });
    });
});
</script>
@endif
@endpush
