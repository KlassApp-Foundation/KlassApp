@extends('layouts.admin.layout')

@section('content')
<div class="container mx-auto p-2">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4 p-3 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-semibold">Current Grading System</h2>
        <a href="{{ route('admin.grades.create') }}"
           class="bg-green-500 text-white py-1 px-3 rounded hover:bg-green-600">
            + Add Grading Rule
        </a>
    </div>

    {{-- Flash messages --}}
    <div class="py-2">
        @include('partials.message')
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white shadow rounded">
        <table class="min-w-full text-sm text-left border border-gray-300">
            
            {{-- Head --}}
            <thead class="bg-gray-50 uppercase text-xs text-gray-600">
                <tr>
                    @php
                        $tx = $gradingRules->first()->standard->name;
                        $st = $tx === "primary" ? "Aggregates" : "Points"
                    @endphp
                    <th class="p-2 border">{{ucwords($st)}}</th>
                    {{-- <th class="p-2 border">Level</th> --}}
                    <th class="p-2 border">Grade</th>
                    <th class="p-2 border text-center">Range</th>
                    <th class="p-2 border">Remark</th>
                    <th class="p-2 border text-center">Actions</th>
                </tr>
            </thead>

            {{-- Body --}}
            <tbody class="divide-y">
                @forelse ($gradingRules as $rule)
                    <tr class="hover:bg-gray-50">

                        {{-- Index --}}
                        <td class="p-2 border">
                            {{ $rule->points }}
                        </td>

                        {{-- Standard --}}
                        {{-- <td class="p-2 border">
                            {{ $rule->standard?->name ?? '—' }}
                        </td> --}}

                        {{-- Grade --}}
                        <td class="p-2 border font-semibold">
                            {{ $rule->grade }}
                        </td>

                        {{-- Range --}}
                        <td class="p-2 border text-center">
                            {{ $rule->min_score }} - {{ $rule->max_score }}
                        </td>

                        {{-- Remark --}}
                        <td class="p-2 border">
                            {{ $rule->remark ?? '—' }}
                        </td>

                        {{-- Actions --}}
                        <td class="p-2 border text-center">
                            <div class="flex items-center justify-center gap-2">

                                {{-- Edit --}}
                                <a href="{{ route('admin.grades.edit', $rule->id) }}"
                                   class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs">
                                    Edit
                                </a>

                                {{-- Delete --}}
                                <form action="{{ route('admin.grades.remove', $rule->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs">
                                        Delete
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500">
                            No grading rules found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
@endsection