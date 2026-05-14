@extends('layouts.admin.layout')

@section('content')
<div class="container mx-auto p-4 max-w-3xl">

    {{-- Header --}}
    <div class="mb-6 bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold text-gray-800">
            Create Promotion Rule
        </h2>
        <p class="text-sm text-gray-500">
            Set minimum passing average for a class.
        </p>
    </div>

    {{-- Flash Messages --}}
    <div class="mb-4">
        @include('partials.message')
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ $rule ? route("students.promotion.update", $rule->id) : route('students.promotion.store') }}">
        @csrf
        @if (isset($rule))
            @method("PUT")
        @endif

        <div class="bg-white shadow rounded p-6 space-y-5">

            {{-- Section Select --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Section
                </label>

                <select name="section_id"
                        class="w-full border border-gray-400 rounded px-3 py-2"
                        required>

                    <option value="">-- Select Section --</option>

                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}"
                          {{ old('section_id', $rule->section_id ?? '') == $section->id ? 'selected' : '' }}>
                          {{ $section->name }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Min Average --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Minimum Average (%)
                </label>
                {{-- {{ dd($rule) }} --}}

                <input type="number"
                       name="min_average"
                       class="w-full border border-gray-400 rounded px-3 py-2"
                       min="0"
                       max="100"
                       value="{{ old('min_average', $rule->min_average ?? '') }}"
                       required>
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
@endsection