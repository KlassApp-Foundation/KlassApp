@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Exams',
    'subtitle' => 'Schedule and manage all school examinations.',
    'actions' => '<a href="' . route('admin.exams.create') . '" class="ds-btn ds-btn-primary ds-btn-sm"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Add Exam</a>'
])

<div class="relative mt-4">
    </div>
    <div class="ds-table-wrap">
    @include('partials.message')
    <table class="ds-table-ledger">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($exams as $exam)
            {{-- {{ dd($exam) }} --}}
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $exam->academicTerm->name }}</td>
                <td>{{ $exam->examType->code }}</td>
                <td>
                    @if($exam->status === 'submitted' || $exam->status === 'done')
                        <span class="dt-badge dt-badge-active">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            {{ ucfirst($exam->status) }}
                        </span>
                    @else
                        <span class="dt-badge dt-badge-pending">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ ucfirst($exam->status) }}
                        </span>
                    @endif
                </td>
                <td>{{ $exam->standard->name ?? '-' }}</td>
                <td>{{ $exam->section->name }}</td>
                <td>{{ ucwords(strtolower($exam->subject->name)) ?? '-' }}</td>
                <td>{{ filled($exam->teacher?->name) ? $exam->teacher->name : $exam->teacher?->email }}</td>
                <td>
                    <div class="flex items-center gap-2">
                        <a href="{{ route("admin.exams.edit", $exam) }}" class="dt-action-btn" title="Edit">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <form action="{{route("admin.exams.archieve", $exam->id)}}" method="POST" class="inline m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dt-action-btn text-red-500 hover:text-red-700" title="Archive" onclick="return confirm('Are you sure?')">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-12 text-gray-500">No exams found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@endsection
