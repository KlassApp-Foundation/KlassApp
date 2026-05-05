@extends('layouts.admin.layout')

@section('content')
<div class="py-6">
    
    <div class="container mx-auto p-6 bg-white shadow rounded">
        <h2 class="text-xl font-semibold mb-6">
            Setup Grading System
        </h2>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.grades.store') }}" method="POST">
            @csrf

            {{-- Standard --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Class / Standard</label>
                <select name="standard_id" class="w-full border rounded p-2">
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}"
                            {{ $standardId == $standard->id ? 'selected' : '' }}>
                            {{ $standard->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">Grade</th>
                            <th class="p-2 border">Min</th>
                            <th class="p-2 border">Max</th>
                            <th class="p-2 border">Remark</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($grades as $i => $g)
                        <tr>
                            <td class="border p-2">
                                <input type="text"
                                       name="grades[{{ $i }}][school_id]"
                                       value="{{ $g['school_id'] }}"
                                       class="w-full border p-1 bg-gray-100"
                                       readonly>
                            </td>

                             <td class="border p-2">
                                <input type="text"
                                       name="grades[{{ $i }}][standard_id]"
                                       value="{{ $g['standard_id'] }}"
                                       class="w-full border p-1 bg-gray-100"
                                       readonly>
                            </td>

                             <td class="border p-2">
                                <input type="text"
                                       name="grades[{{ $i }}][grade]"
                                       value="{{ $g['grade'] }}"
                                       class="w-full border p-1 bg-gray-100"
                                       readonly>
                            </td>

                            <td class="border p-2">
                                <input type="number"
                                       name="grades[{{ $i }}][min_score]"
                                       value="{{ $g['min_score'] }}"
                                       class="w-full border p-1">
                            </td>

                            <td class="border p-2">
                                <input type="number"
                                       name="grades[{{ $i }}][max_score]"
                                       value="{{ $g['max_score'] }}"
                                       class="w-full border p-1">
                            </td>

                            <td class="border p-2">
                                <input type="text"
                                       name="grades[{{ $i }}][remark]"
                                       value="{{ $g['remark'] }}"
                                       class="w-full border p-1">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

            <div class="mt-6">
                <button class="bg-blue-600 text-white px-6 py-2 rounded">
                    Save Full Grading System
                </button>
            </div>

        </form>
    </div>

</div>
@endsection