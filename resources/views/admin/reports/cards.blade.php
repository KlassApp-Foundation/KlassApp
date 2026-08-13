@extends('layouts.admin.layout')

@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Report Cards',
    'subtitle' => 'Generate and download student report cards by class.',
])

<div class="relative mt-4">
    <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">
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
        <a href="{{ route('admin.reports.cards.missing') }}"
           class="bg-red-600 hover:bg-red-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            Missing Marks Report
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-4 mt-4">
    <form method="POST" action="{{ route('admin.reports.cards.template') }}" class="flex flex-wrap items-center gap-4">
        @csrf
        <span class="text-sm font-medium text-gray-700">Report Card Template:</span>
        <div class="flex gap-3">
            @foreach (\App\Http\Controllers\Admin\ReportCardsController::TEMPLATES as $key => $tpl)
                <label class="flex items-center gap-1.5 text-sm text-gray-700 border rounded-lg px-3 py-1.5 cursor-pointer {{ $reportTemplate === $key ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                    <input type="radio" name="report_template" value="{{ $key }}" {{ $reportTemplate === $key ? 'checked' : '' }} class="text-blue-600">
                    {{ $tpl['label'] }}
                </label>
            @endforeach
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition">
            Save
        </button>
        <span class="text-xs text-gray-400">Applies to all report cards generated from this point on (batch, individual, merged).</span>
    </form>
    <div class="mt-2 pt-2 border-t text-xs text-gray-500">
        Logo not showing correctly on the report card? Manage your school's logo on the
        <a href="{{ url('/admin/schooldetails/editdetail/' . $school->id) }}" class="text-blue-600 hover:underline">School Details</a> page.
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

            <div class="flex gap-2 mb-3">
                <a href="{{ route('admin.reports.cards.merged', $link) }}"
                   class="flex-1 text-center bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium py-2 rounded-lg transition">
                    Print All (Merged PDF)
                </a>
                <a href="{{ route('admin.reports.cards.download', $link) }}"
                   class="flex-1 text-center bg-green-600 hover:bg-green-500 text-white text-sm font-medium py-2 rounded-lg transition">
                    Download All (Zip)
                </a>
                <a href="{{ route('admin.marks.filter', ['class' => $link->section_id, 'term' => $selectedTerm]) }}"
                   class="flex-1 text-center bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium py-2 rounded-lg transition">
                    View Marks
                </a>
            </div>

            <div class="mb-3">
                <a href="{{ route('admin.reports.cards.combinedMarksheet', $link) }}"
                   class="block w-full text-center bg-amber-600 hover:bg-amber-500 text-white text-sm font-medium py-2 rounded-lg transition">
                    Combined Marksheet (Excel)
                </a>
            </div>

            @if (!empty($link->students) && count($link->students) > 0)
            <details class="mt-3 border rounded-lg">
                <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 bg-gray-50 rounded-lg">
                    Students ({{ count($link->students) }}) — Preview or Download Individual Reports
                </summary>
                <div class="max-h-64 overflow-y-auto mt-1">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-xs text-gray-500 uppercase">
                            <tr><th class="text-left px-2 py-1">Name</th><th class="px-2 py-1 w-24">Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($link->students as $student)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-2 py-1 text-gray-700">{{ $student->name }}</td>
                                <td class="px-2 py-1 flex gap-1 justify-center">
                                    <a href="{{ route('admin.reports.cards.student.preview', ['stdLink' => $link, 'learner' => $student]) }}"
                                       target="_blank"
                                       class="text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-2 py-0.5 rounded transition">
                                        Preview
                                    </a>
                                    <a href="{{ route('admin.reports.cards.student.download', ['stdLink' => $link, 'learner' => $student]) }}"
                                       class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-2 py-0.5 rounded transition">
                                        Download
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
            @endif
        </div>
    @empty
        <div class="col-span-full text-center py-16 text-gray-500">
            <p class="text-lg">No classes with EOT exams found for this term.</p>
            <p class="text-sm mt-1">Create an End of Term exam first from the Exams page.</p>
        </div>
    @endforelse
</div>

    @include('admin.reports._eot-kpi-card')

    @if (!empty($recentGenerations) && count($recentGenerations) > 0)
    <div class="bg-white rounded-xl shadow-sm border mt-6 p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Recent Generations</h3>
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="text-left px-3 py-2">Class</th>
                    <th class="text-left px-3 py-2">Type</th>
                    <th class="text-left px-3 py-2">Status</th>
                    <th class="text-left px-3 py-2">Requested</th>
                    <th class="px-3 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentGenerations as $g)
                <tr class="border-t">
                    <td class="px-3 py-2 font-medium text-gray-700">{{ $g->class_name }}</td>
                    <td class="px-3 py-2 text-gray-600">{{ $g->mode === 'merged' ? 'Merged PDF' : 'Zip' }}</td>
                    <td class="px-3 py-2">
                        @if ($g->status === 'completed')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Ready</span>
                        @elseif ($g->status === 'failed')
                            <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full" title="{{ $g->error }}">Failed</span>
                        @else
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">Generating…</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-gray-500 text-xs">{{ $g->created_at->format('d M H:i') }}</td>
                    <td class="px-3 py-2 text-center">
                        @if ($g->status === 'completed')
                            <a href="{{ route('admin.reports.cards.generation.download', $g) }}"
                               class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition">Download</a>
                        @elseif ($g->status === 'failed')
                            <span class="text-xs text-red-600">{{ $g->error ?? 'Error' }}</span>
                        @else
                            <span class="text-xs text-gray-400">Please wait…</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="text-xs text-gray-400 mt-2">Refresh this page to check status. Generation runs in the background.</p>
    </div>
    @endif
</div>

@endsection