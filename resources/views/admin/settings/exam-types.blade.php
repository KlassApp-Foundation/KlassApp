{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Exam Type Settings',
    'subtitle' => 'Control which exam types contribute to term/report-card totals. Changes apply to future report generation only.',
])

@include('layouts.partials.settings-nav')

@include('partials.message')

<div class="relative mt-4">
<div class="w-full main-content">
    <form method="POST" action="{{ route('admin.settings.exam-types.update') }}">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left">
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
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4">{{ $type->name }}</td>
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
