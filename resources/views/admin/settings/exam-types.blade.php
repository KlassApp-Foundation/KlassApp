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

<div class="relative mt-4">
<div class="w-full main-content">
    <form method="POST" action="{{ route('admin.settings.exam-types.update') }}">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left" data-testid="exam-type-table">
                <thead>
                    <tr class="border-b">
                        <th class="py-3 px-4 font-semibold text-sm">Exam Type</th>
                        <th class="py-3 px-4 font-semibold text-sm">Code</th>
                        <th class="py-3 px-4 font-semibold text-sm">Contributes to Report Total</th>
                        <th class="py-3 px-4 font-semibold text-sm">Show on Report Cards</th>
                        <th class="py-3 px-4 font-semibold text-sm">Weight %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($examTypes as $type)
                        @php
                            $checked = $prefs[$type->id] ?? $type->contributes_to_report_total;
                            $settings = $composition[(string) $type->id] ?? $composition[$type->id] ?? [];
                            $included = ($settings['include'] ?? true) !== false;
                            $weight = $settings['weight'] ?? null;
                        @endphp
                        <tr class="border-b hover:bg-gray-50" data-testid="exam-type-row-{{ $type->id }}">
                            <td class="py-3 px-4">
                                {{ $type->name }}
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
                            <td class="py-3 px-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="include[{{ $type->id }}]" value="0">
                                    <input type="checkbox" name="include[{{ $type->id }}]" value="1"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        {{ $included ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm {{ $included ? 'text-green-700 font-medium' : 'text-gray-400' }}">
                                        {{ $included ? 'Shown' : 'Hidden' }}
                                    </span>
                                </label>
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" name="weight[{{ $type->id }}]" min="1" max="100" step="any"
                                    value="{{ $weight !== null ? $weight : '' }}" placeholder="—"
                                    class="w-20 rounded border-gray-300 text-sm focus:ring-blue-500">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
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
                <input type="text" name="name" id="exam-type-name" class="rounded border-gray-300 text-sm focus:ring-blue-500"
                       value="{{ old('name') }}" maxlength="80" required placeholder="Mock Examination" data-testid="exam-type-name">
            </div>
            <div>
                <label class="form-label" for="exam-type-code">Short code</label>
                <input type="text" name="code" id="exam-type-code" class="w-24 rounded border-gray-300 text-sm focus:ring-blue-500"
                       value="{{ old('code') }}" maxlength="10" required placeholder="MOCK" data-testid="exam-type-code">
            </div>
            <div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="contributes_to_report_total" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                           @checked(old('contributes_to_report_total')) data-testid="exam-type-contributes">
                    <span>Counts towards the report total</span>
                </label>
            </div>
            <button type="submit" class="px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900" data-testid="exam-type-create-submit">
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
        <select name="curriculum_profile" class="rounded border-gray-300 text-sm focus:ring-blue-500">
            @foreach($profiles as $key => $profile)
                <option value="{{ $key }}" {{ $profileKey === $key ? 'selected' : '' }}>{{ $profile['label'] }}</option>
            @endforeach
        </select>
        <button type="submit" class="ml-3 px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
            Apply Profile
        </button>
    </form>
</div>
</div>
</div>
@endsection
