@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Library — Book Lending</h1>
            <p class="dashboard-subtitle">Check out and return books.</p>
        </div>
        <a href="{{ route('admin.library.lends.create') }}" class="ds-btn ds-btn-primary ds-btn-sm">
            + Check Out
        </a>
    </div>

    @include('partials.message')

    {{-- Status filter --}}
    <div class="flex gap-2 mb-4 items-center">
        <a href="{{ route('admin.library.lends', ['status' => 'pending']) }}"
           class="ds-btn ds-btn-sm {{ request('status', 'pending') === 'pending' ? 'ds-btn-primary' : 'ds-btn-ghost' }}">
            Active
        </a>
        <a href="{{ route('admin.library.lends', ['status' => 'returned']) }}"
           class="ds-btn ds-btn-sm {{ request('status') === 'returned' ? 'ds-btn-primary' : 'ds-btn-ghost' }}">
            Returned
        </a>
        <a href="{{ route('admin.library.lends', ['status' => 'cancel']) }}"
           class="ds-btn ds-btn-sm {{ request('status') === 'cancel' ? 'ds-btn-primary' : 'ds-btn-ghost' }}">
            Cancelled
        </a>
        <span class="text-muted text-xs ml-2">|</span>
        <form method="GET" class="flex gap-2 items-center">
            <input type="hidden" name="status" value="{{ request('status', 'pending') }}">
            <input type="text" name="q" placeholder="Search card or book code..."
                   value="{{ request('q') }}" class="ds-form-input max-w-xs">
            <button type="submit" class="ds-btn ds-btn-sm ds-btn-outline">Search</button>
            @if(request('q'))
                <a href="{{ route('admin.library.lends', ['status' => request('status', 'pending')]) }}"
                   class="ds-btn ds-btn-ghost ds-btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <div class="ds-card">
        <div class="ds-table-wrap">
            <table class="ds-table ds-table-striped ds-table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Book</th>
                        <th>Card No.</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lends as $i => $lend)
                    @php
                        $isOverdue = $lend->status === 'pending' && $lend->return_date && $lend->return_date < now()->toDateString();
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $lends->firstItem() + $i }}</td>
                        <td>
                            <strong>{{ $lend->userlent->name ?? '—' }}</strong>
                            @if($lend->userlent && $lend->userlent->registration_number)
                                <br><span class="text-xs text-muted">{{ $lend->userlent->registration_number }}</span>
                            @endif
                        </td>
                        <td>{{ $lend->book->title ?? '—' }}</td>
                        <td><code>{{ $lend->library_card_no }}</code></td>
                        <td>{{ $lend->issue_date ? date('d M Y', strtotime($lend->issue_date)) : '—' }}</td>
                        <td class="{{ $isOverdue ? 'text-red-500 font-semibold' : '' }}">
                            {{ $lend->return_date ? date('d M Y', strtotime($lend->return_date)) : '—' }}
                            @if($isOverdue)
                                <span class="ds-badge ds-badge-danger">Overdue</span>
                            @endif
                        </td>
                        <td>
                            @if($lend->status === 'pending')
                                <span class="ds-badge ds-badge-pending">Checked Out</span>
                            @elseif($lend->status === 'returned')
                                <span class="ds-badge ds-badge-paid">Returned</span>
                            @else
                                <span class="ds-badge ds-badge-inactive">{{ ucfirst($lend->status) }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($lend->status === 'pending')
                                <form method="POST" action="{{ route('admin.library.lends.return', $lend->id) }}"
                                      class="inline" onsubmit="return confirm('Mark this book as returned?');">
                                    @csrf
                                    <button type="submit" class="ds-btn ds-btn-success ds-btn-sm">Check In</button>
                                </form>
                            @else
                                <span class="text-xs text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-6 text-muted">
                            @if(request('q'))
                                No lendings match your search.
                            @elseif(request('status', 'pending') === 'pending')
                                No active check-outs. <a href="{{ route('admin.library.lends.create') }}" class="underline">Check out a book</a>.
                            @else
                                No {{ request('status') }} lendings found.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $lends->links() }}
    </div>
</div>
@endsection
