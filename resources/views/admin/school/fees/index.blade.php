{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    @include('layouts.partials.page-header', [
        'title' => 'Fee structure',
        'subtitle' => 'Each fee is one row. A whole-school fee covers every class.',
        'actions' => '<a href="' . route('admin.fees-categories.create') . '" class="ds-btn ds-btn-primary ds-btn-sm"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Add fee</a>'
    ])

    @include('partials.message')

    @if($fees === [])
        <div class="text-center py-12 text-gray-500">No fees yet. Add a fee to build the structure.</div>
    @else
        <x-table :headers="['Fee', 'Amount', 'Classes', 'Type', 'Duration', 'Actions']">
            @foreach($fees as $fee)
                <tr data-testid="fee-structure-row" data-name="{{ $fee['name'] }}" data-duration="{{ $fee['duration'] }}" data-classes="{{ $fee['classes'] }}">
                    <td class="font-medium">{{ $fee['name'] }}</td>
                    <td>{{ number_format($fee['amount'], 0) }}</td>
                    <td>{{ $fee['classes'] }}</td>
                    <td>{{ $fee['type'] }}</td>
                    <td>{{ $fee['duration'] }}</td>
                    <td>
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.fees-categories.edit', $fee['id']) }}" class="dt-action-btn" title="Edit">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form action="{{ route('admin.fees-categories.destroy', $fee['id']) }}" method="POST" class="inline m-0"
                                  onsubmit="return confirm('Are you sure you want to delete this fee category?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dt-action-btn text-red-500 hover:text-red-700" title="Delete">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>
    @endif

    <div class="mt-6" data-testid="fee-class-totals">
        <h2 class="text-base font-semibold mb-2" style="color:#0F172A;">Total per class</h2>
        @if($classTotals === [])
            <p class="text-gray-500">Add classes to see a total for each one.</p>
        @else
            <x-table :headers="['Class', 'Total']">
                @foreach($classTotals as $row)
                    <tr data-testid="fee-class-total" data-class="{{ $row['class'] }}" data-total="{{ number_format($row['total'], 2, '.', '') }}">
                        <td>{{ $row['class'] }}</td>
                        <td>{{ number_format($row['total'], 0) }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </div>

</div>
@endsection
