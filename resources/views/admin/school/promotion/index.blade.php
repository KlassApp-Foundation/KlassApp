@extends('layouts.admin.layout')

@section('content')
<div class="container mx-auto p-2">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4 p-3 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-semibold">Current Promotion Rules</h2>

        <a href="{{ route('students.promotion.create') }}"
           class="bg-green-500 text-white py-1 px-3 rounded hover:bg-green-600">
            + Add Promotion Rule
        </a>
    </div>

    {{-- Flash Messages --}}
    <div class="py-2">
        @include('partials.message')
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white shadow rounded">
        <table class="min-w-full text-sm text-left border border-gray-300">

            {{-- Head --}}
            <thead class="bg-gray-50 uppercase text-xs text-gray-600">
                <tr>
                    <th class="p-2 border">#</th>
                    <th class="p-2 border">Section</th>
                    <th class="p-2 border text-center">Minimum Average</th>
                    <th class="p-2 border text-center">Actions</th>
                </tr>
            </thead>

            {{-- Body --}}
            <tbody class="divide-y">
                @forelse ($rules as $rule)

                    <tr class="hover:bg-gray-50">

                        {{-- Index --}}
                        <td class="p-2 border">
                            {{ $loop->iteration }}
                        </td>

                        {{-- Section --}}
                        <td class="p-2 border">
                            {{ $rule->section?->name ?? 'All Sections' }}
                        </td>

                        {{-- Minimum Average --}}
                        <td class="p-2 border text-center">
                            {{ $rule->min_average }}
                        </td>

                        {{-- Actions --}}
                        <td class="p-2 border text-center">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Edit --}}
                                <a href="{{ route('students.promotion.edit', $rule->id) }}"
                                   class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs">
                                    Edit
                                </a>

                                {{-- Delete --}}
                                <form action="{{ route('students.promotion.remove', $rule->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this promotion rule?')">

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
                        <td colspan="5" class="p-4 text-center text-gray-500">
                            No promotion rules found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
@endsection