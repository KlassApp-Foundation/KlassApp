@extends('layouts.admin.layout')

@section('content')
<div class="container mx-auto p-2">
     {{-- Header --}}
    <div class="flex items-center justify-between mb-4 p-2 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-semibold">Fee Categories</h2>
        <a href="{{ route('admin.fees-categories.create') }}"
           class="bg-green-500 text-white py-1 px-3 rounded hover:bg-green-600">
            + Add Category
        </a>
    </div>

    {{-- Flash messages --}}
    <div class="py-2">
        @include('partials.message')
    </div>

       {{-- Terms table --}}
    <div class="overflow-x-auto">
        @if($fees->isEmpty())
            <p class="text-gray-500">No academic terms found for this year.</p>
        @else
            <table class="min-w-full bg-white border rounded">
                <thead class="bg-gray-200 text-left">
                    <tr>
                        <th class="py-2 px-4 border">No.</th>
                        <th class="py-2 px-4 border">Name</th>
                        <th class="py-2 px-4 border">Amount</th>
                        <th class="py-2 px-4 border">Level</th>
                        <th class="py-2 px-4 border">Class</th>
                        <th class="py-2 px-4 border">Term</th>
                        <th class="py-2 px-4 border text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fees as $fee)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border">{{ $loop->iteration }}</td>
                            <td class="py-2 px-4 border">{{ $fee->name }}</td>
                            <td class="py-2 px-4 border">
                                {{ $fee->amount ?? "-" }}
                            </td>

                            <td class="py-2 px-4 border">
                                {{ $fee->standard->name ?? '-' }}
                            </td>

                            <td class="py-2 px-4 border">
                                {{ $fee->section->name ?? 'All' }}
                            </td>

                            <td class="py-2 px-4 border">
                                {{ $fee->term->name ?? 'All' }}
                            </td>
                            <td class="py-2 px-4 border text-center space-x-2">
                                {{-- Edit --}}
                                <a href="$"
                                   class="text-blue-600 hover:underline">Edit</a>

                                {{-- Delete --}}
                                <form action="{{ route('admin.fees-categories.destroy', $fee->id) }}" method="POST" class="inline-block"
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