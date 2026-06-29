{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="container-body">
    <h1 class="admin-h1 my-3 flex items-center">
        <a href="{{ url('/admin/standardlinks') }}" class="rounded-full bg-gray-100 p-2 mr-3" title="Back to Classes">
            <svg class="w-3 h-3 fill-current text-gray-700" viewBox="0 0 492 492"><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788C492,219.198,479.172,207.418,464.344,207.418z"/></svg>
        </a>
        Bulk Teacher-Subject-Class Links
        <span class="text-sm font-normal text-gray-500 ml-3">({{ $count }} total)</span>
    </h1>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Import Form --}}
        <div class="bg-white shadow rounded p-4">
            <h2 class="text-lg font-semibold mb-3">Import Teacher Links</h2>
            <p class="text-sm text-gray-600 mb-3">Paste or upload teacher-subject-class assignments. Format:</p>
            <pre class="bg-gray-50 p-2 rounded text-xs mb-3">Teacher Name | Subject | Class | Phone
John Ssali | Mathematics | P.5 | +256701234567
Jane Okello | English | P.5
John Ssali | Science | P.6</pre>

            <form method="POST" action="{{ url('/admin/teacher-links/import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Paste data</label>
                    <textarea name="data" rows="6" class="w-full border rounded p-2 text-sm" placeholder="Teacher Name | Subject | Class | Phone"></textarea>
                </div>
                <div class="text-center text-gray-400 text-sm my-2">— OR —</div>
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Upload CSV/XLSX</label>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls,.txt" class="w-full border rounded p-2 text-sm">
                    <p class="text-xs text-gray-500 mt-1">CSV must have header row: Teacher, Subject, Class, Phone</p>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">Import Links</button>
            </form>
        </div>

        {{-- Existing Links List --}}
        <div class="bg-white shadow rounded p-4">
            <h2 class="text-lg font-semibold mb-3">Existing Links</h2>
            @if($links->isEmpty())
                <p class="text-sm text-gray-500">No teacher links yet. Use the form to import.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="py-2 pr-2">Teacher</th>
                                <th class="py-2 pr-2">Subject</th>
                                <th class="py-2 pr-2">Class</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($links as $link)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 pr-2">{{ $link->teacher?->name ?? '—' }}</td>
                                <td class="py-2 pr-2">{{ $link->subject?->name ?? '—' }}</td>
                                <td class="py-2 pr-2">{{ $link->standardLink?->section?->name ?? '—' }}</td>
                                <td class="py-2">
                                    <form method="POST" action="{{ url('/admin/teacher-links/delete/' . $link->id) }}" onsubmit="return confirm('Delete this link?')">
                                        @csrf
                                        <button class="text-red-500 hover:text-red-700 text-xs">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($links->hasPages())
                <div class="mt-3">{{ $links->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection