@extends('layouts.admin.layout')

@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Report Cards',
    'subtitle' => 'Generate and download student report cards by class.',
])

<div class="relative mt-4">
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Term:</label>
            <select name="term" onchange="this.form.submit()" class="border rounded px-3 py-1.5 text-sm">
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}" {{ $selectedTerm == $term->id ? 'selected' : '' }}>
                        {{ $term->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-6">
    @forelse ($stdLinks as $link)
        <div class="bg-white rounded-xl shadow-sm border hover:shadow-md transition p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-800">{{ $link->section->name }}</h3>
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ $link->standard->name }}</span>
            </div>

            <div class="text-sm text-gray-600 space-y-1 mb-4">
                <p><span class="font-medium">{{ $link->studentCount ?? 0 }}</span> students with marks</p>
                @if ($link->eotExam)
                    <p>Exam: {{ $link->eotExam->examType->name }} &middot; {{ $link->eotExam->academicTerm->name }}</p>
                @endif
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.reports.cards.download', $link) }}"
                   class="flex-1 text-center bg-green-600 hover:bg-green-500 text-white text-sm font-medium py-2 rounded-lg transition">
                    Download All
                </a>
                <a href="{{ route('admin.marks.filter', ['class' => $link->section_id, 'term' => $selectedTerm]) }}"
                   class="flex-1 text-center bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium py-2 rounded-lg transition">
                    View Marks
                </a>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-16 text-gray-500">
            <p class="text-lg">No classes with EOT exams found for this term.</p>
            <p class="text-sm mt-1">Create an End of Term exam first from the Exams page.</p>
        </div>
    @endforelse
</div>

</div>

@endsection