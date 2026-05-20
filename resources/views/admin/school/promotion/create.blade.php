@extends('layouts.admin.layout')

@section('content')
<div class="container mx-auto p-4 max-w-3xl">

    @php
        $type = old('rule_type', $rule->rule_type ?? null);
    @endphp

    {{-- Header --}}
    <div class="mb-6 bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold text-gray-800">
            Create Promotion Rule
        </h2>
        <p class="text-sm text-gray-500">
            Set minimum passing requirement for a class.
        </p>
    </div>

    {{-- Flash Messages --}}
    <div class="mb-4">
        @include('partials.message')
    </div>

    {{-- Form --}}
    <form method="POST"
          action="{{ $rule ? route('students.promotion.update', $rule->id) : route('students.promotion.store') }}">

        @csrf
        @if (isset($rule))
            @method('PUT')
        @endif

        <div class="bg-white shadow rounded p-6 space-y-5">

            {{-- Section --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Section
                </label>

                <select name="section_id"
                        class="w-full border border-gray-400 rounded px-3 py-2"
                        required>

                    <option value="">-- Select Class --</option>

                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}"
                            {{ old('section_id', $rule->section_id ?? '') == $section->id ? 'selected' : '' }}>
                            {{ $section->name }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Rule Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Rule Type
                </label>

                <select name="rule_type"
                        id="rule_type"
                        class="w-full border border-gray-400 rounded px-3 py-2"
                        required>

                    <option value="">-- Select Rule Type --</option>

                    @foreach ($ruletypes as $ruletype)
                        <option value="{{ old('rule_type', $ruletype ?? '') }}"
                            {{ old('rule_type', $rule->rule_type ?? '') == $ruletype ? 'selected' : '' }}>
                            {{ ucfirst($ruletype) }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- MIN AVERAGE --}}
            <div id="average-box" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Minimum Average
                </label>

                <input type="number"
                       name="min_average"
                       class="w-full border border-gray-400 rounded px-3 py-2"
                       min="0"
                       max="100"
                       value="{{ old('min_average', $rule->min_average ?? '') }}">
            </div>

            {{-- MIN AGGREGATE --}}
            <div id="aggregate-box" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Maximum Aggregate
                </label>

                <input type="number"
                       name="min_aggregate"
                       class="w-full border border-gray-400 rounded px-3 py-2"
                       value="{{ old('min_aggregate', $rule->min_aggregate ?? '') }}">
            </div>

            {{-- MIN POINTS --}}
            <div id="points-box" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Minimum Points
                </label>

                <input type="number"
                       name="min_points"
                       class="w-full border border-gray-400 rounded px-3 py-2"
                       min="0"
                       max="100"
                       value="{{ old('min_points', $rule->min_points ?? '') }}">
            </div>

            {{-- Submit --}}
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded shadow">
                    Save Rule
                </button>
            </div>

        </div>
    </form>

</div>

{{-- JS --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const select = document.getElementById('rule_type');

    const boxes = {
        average: document.getElementById('average-box'),
        aggregate: document.getElementById('aggregate-box'),
        points: document.getElementById('points-box'),
    };

    function showOnly(type) {
        Object.values(boxes).forEach(box => box.classList.add('hidden'));

        if (boxes[type]) {
            boxes[type].classList.remove('hidden');
        }
    }

    select.addEventListener('change', function () {
        showOnly(this.value);
    });

    // initial state (edit + old input support)
    showOnly(select.value);
});
</script>

@endsection