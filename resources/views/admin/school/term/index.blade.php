@extends('layouts.admin.layout')

@section('content')
<div class="container mx-auto p-2">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4 p-2 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-semibold">Academic Terms for the year {{ $academic_year->name }}</h2>
        <a href="{{ route('admin.academic-term.create') }}"
           class="bg-green-500 text-white py-1 px-3 rounded hover:bg-green-600">
            + Add Term
        </a>
    </div>

    {{-- Flash messages --}}
    <div class="py-2">
        @include('partials.message')
    </div>

    {{-- Terms table --}}
    <div class="overflow-x-auto">
        @if($terms->isEmpty())
            <p class="text-gray-500">No academic terms found for this year.</p>
        @else
            <table class="min-w-full bg-white border rounded">
                <thead class="bg-gray-200 text-left">
                    <tr>
                        <th class="py-2 px-4 border">#</th>
                        <th class="py-2 px-4 border">Term</th>
                        <th class="py-2 px-4 border">Starts On</th>
                        <th class="py-2 px-4 border">Ends On</th>
                        <th class="py-2 px-4 border text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($terms as $term)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border">{{ $loop->iteration }}</td>
                            <td class="py-2 px-4 border">{{ $term->name }}</td>
                            <td class="py-2 px-4 border">
                                {{ $term->starts_on ? \Carbon\Carbon::parse($term->starts_on)->format('d M, Y') : '-' }}
                            </td>

                            <td class="py-2 px-4 border">
                                {{ $term->ends_on ? \Carbon\Carbon::parse($term->ends_on)->format('Y-m-d') : '-' }}
                            </td>
                            <td class="py-2 px-4 border text-center space-x-2">
                                {{-- Edit --}}
                                <a href="$"
                                   class="text-blue-600 hover:underline">Edit</a>

                                {{-- Delete --}}
                                <form action="{{ route('admin.academic-term.destroy', $term->id) }}" method="POST" class="inline-block"
                                      onsubmit="return confirm('Are you sure you want to delete this term?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection