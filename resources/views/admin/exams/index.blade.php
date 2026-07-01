@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Exams',
    'subtitle' => 'Schedule and manage all school examinations.',
    'actions' => '<a href="' . route('admin.exams.create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><i class="fa-solid fa-plus text-xs"></i> Add Exam</a>'
])

<div class="relative mt-4">
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        {{-- Flash Success Message --}}
       @include('partials.message')
        <thead class="bg-gray-100 text-gray-700 text-sm">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-4 py-2 text-left font-bold capitalize tracking-wider border border-gray-400">
                        {{ $header }}
                    </th>
                @endforeach
                
                
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 text-sm">
            @forelse ($exams as $exam)
            {{-- {{ dd($exam) }} --}}
            <tr>
                <td class="px-2 py-3 border border-gray-400">{{ $loop->iteration }}</td>
                <td class="px-2 py-3 border border-gray-400">{{ $exam->academicTerm->name }}</td>
                <td class="px-2 py-3 border border-gray-400">{{ $exam->examType->code }}</td>
                <td class="px-2 py-3 border border-gray-400">{{ $exam->status }}</td>
                <td class="px-2 py-3 border border-gray-400">{{ $exam->standard->name ?? '-' }}</td>
                <td class="px-2 py-3 border border-gray-400">{{ $exam->section->name }}</td>
                <td class="px-2 py-3 border border-gray-400 ">
                    {{ ucwords(strtolower($exam->subject->name)) ?? '-' }}
                </td>
                <td class="px-2 py-3 border border-gray-400">
                   {{ filled($exam->teacher?->name) ? $exam->teacher->name : $exam->teacher?->email }}
                 </td>
                {{-- <td class="px-2 py-3 border border-gray-400">{{ $exam->academicYear->name ?? '-' }}</td> --}}
                <td class="p-2 space-x-2 border border-gray-400">
                    <a href="{{ route("admin.exams.edit", $exam) }}" class="text-blue-500 hover:text-blue-700">Edit</a>
                    <form action="{{route("admin.exams.archieve", $exam->id)}}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure?')">Archieve</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-2 text-center text-gray-500">No exams found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
