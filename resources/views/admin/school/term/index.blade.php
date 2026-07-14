@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    @include('layouts.partials.page-header', [
        'title' => 'Academic Terms',
        'subtitle' => 'Manage academic terms for ' . ($academic_year->name ?? 'current year'),
        'actions' => '<a href="' . route('admin.academic-term.create') . '" class="ds-btn ds-btn-primary ds-btn-sm"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Add Term</a>'
    ])

    @include('partials.message')

    <div class="ds-table-wrap">
        @if($terms->isEmpty())
            <div class="text-center py-12 text-gray-500">No academic terms found for this year.</div>
        @else
            <table class="ds-table-ledger">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Term</th>
                        <th>Starts On</th>
                        <th>Ends On</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($terms as $term)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="font-medium">{{ $term->name }}</td>
                            <td>{{ $term->starts_on ? \Carbon\Carbon::parse($term->starts_on)->format('d M, Y') : '-' }}</td>
                            <td>{{ $term->ends_on ? \Carbon\Carbon::parse($term->ends_on)->format('d M, Y') : '-' }}</td>
                            <td>
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.academic-term.edit', $term) }}" class="dt-action-btn" title="Edit">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
@endsection