{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Exam Type Settings',
    'subtitle' => 'Each exam set has its own report. The end-of-term report starts as End of Term only. Weights must total 100, and a report already released keeps the mix it was released with.',
])

@include('layouts.partials.settings-nav')

@include('partials.message')

<div class="relative mt-4">
<div class="w-full main-content">
    <form method="POST" action="{{ route('admin.settings.exam-types.update') }}">
        @csrf
        <p data-testid="end-of-term-composition" style="margin:0 0 12px;max-width:40rem;">
            Tick the exam sets that count on the end-of-term report and give them weights that total 100.
            Each other set still has its own report. Changing this does not alter a report that is already released.
        </p>
        @error('weight') <p class="text-xs text-red-600 mb-2" data-testid="composition-weight-error">{{ $message }}</p> @enderror
        @error('include') <p class="text-xs text-red-600 mb-2">{{ $message }}</p> @enderror
        <div data-testid="exam-type-table" style="display:flex;flex-direction:column;gap:12px;max-width:40rem;">
            @foreach($examTypes as $type)
                @php
                    $checked = $prefs[$type->id] ?? $type->contributes_to_report_total;
                    $settings = $composition[(string) $type->id] ?? $composition[$type->id] ?? [];
                    $included = is_array($settings) && array_key_exists('include', $settings)
                        ? ($settings['include'] === true || $settings['include'] === 1 || $settings['include'] === '1')
                        : true;
                    $weight = is_array($settings) ? ($settings['weight'] ?? null) : null;
                @endphp
                <div data-testid="exam-type-row-{{ $type->id }}" style="border:1px solid var(--d-border, #e5e7eb);border-radius:12px;padding:12px;">
                    <div style="font-weight:600;">
                        {{ $type->name }}
                        <span class="text-xs" data-testid="exam-type-owner-{{ $type->id }}"
                              style="color: {{ $type->isShared() ? 'var(--d-text-secondary)' : 'var(--d-accent)' }};">
                            {{ $type->code }} · {{ $type->isShared() ? '(shared)' : '(your school)' }}
                        </span>
                    </div>
                    <label class="inline-flex items-center" style="min-height:44px;gap:8px;">
                        <input type="hidden" name="contributes[{{ $type->id }}]" value="0">
                        <input type="checkbox" name="contributes[{{ $type->id }}]" value="1"
                            style="width:20px;height:20px;"
                            {{ $checked ? 'checked' : '' }}>
                        <span>Counts on its own</span>
                    </label>
                    <label class="inline-flex items-center" style="min-height:44px;gap:8px;">
                        <input type="hidden" name="include[{{ $type->id }}]" value="0">
                        <input type="checkbox" name="include[{{ $type->id }}]" value="1"
                            style="width:20px;height:20px;"
                            data-testid="exam-type-include-{{ $type->id }}"
                            {{ $included ? 'checked' : '' }}>
                        <span>Counts on the end-of-term report</span>
                    </label>
                    <label style="display:block;margin-top:4px;">
                        <span class="text-sm">Weight</span>
                        <input type="number" name="weight[{{ $type->id }}]" min="1" max="100" step="any"
                            value="{{ $weight !== null ? $weight : '' }}" placeholder="—"
                            inputmode="decimal"
                            data-testid="exam-type-weight-{{ $type->id }}"
                            class="ds-form-input" style="min-height:44px;width:100%;max-width:8rem;">
                    </label>
                </div>
            @endforeach
        </div>
        <div class="mt-6">
            <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px;" data-testid="exam-type-save">
                Save Preferences
            </button>
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
