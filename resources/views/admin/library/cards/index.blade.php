@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Library — Student Cards</h1>
            <p class="dashboard-subtitle">View library cards and lending history per student.</p>
        </div>
    </div>

    @include('partials.message')

    {{-- Student selector --}}
    <div class="ds-card mb-6">
        <form method="GET" class="flex gap-2 items-end">
            <div class="flex-1">
                <label class="ds-label" for="user_id">Select Student</label>
                <select name="user_id" id="user_id"
                        class="ds-form-input ds-form-select" onchange="this.form.submit()">
                    <option value="">— Choose a student —</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" {{ request('user_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} @if($s->registration_number)({{ $s->registration_number }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            @if(request('user_id'))
                <a href="{{ route('admin.library.cards') }}" class="ds-btn ds-btn-ghost ds-btn-sm">Clear</a>
            @endif
        </form>
    </div>

    @if($student)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Library Card --}}
            <div class="ds-card md:col-span-1">
                <h3 class="ds-card-title">Library Card</h3>
                @if($card)
                    <div class="mt-3">
                        <p><strong>Card No:</strong> <code>{{ $card->library_card_no }}</code></p>
                        <p><strong>Status:</strong>
                            @if($card->status === 'active')
                                <span class="ds-badge ds-badge-paid">Active</span>
                            @else
                                <span class="ds-badge ds-badge-inactive">{{ ucfirst($card->status) }}</span>
                            @endif
                        </p>
                        <p><strong>Book Limit:</strong> {{ $card->book_limit ?? 'Unlimited' }}</p>
                        @if($card->expiry_date)
                            <p><strong>Expires:</strong> {{ date('d M Y', strtotime($card->expiry_date)) }}</p>
                        @endif
                    </div>
                @else
                    <p class="text-muted mt-2">No library card issued.</p>
                @endif
            </div>

            {{-- Student Info --}}
            <div class="ds-card md:col-span-2">
                <h3 class="ds-card-title">{{ $student->displayName ?: $student->name }}</h3>
                <div class="mt-2 grid grid-cols-2 gap-4">
                    <p><strong>Reg No:</strong> {{ $student->registration_number ?? '—' }}</p>
                    <p><strong>Email:</strong> {{ $student->email ?? '—' }}</p>
                    <p><strong>Phone:</strong> {{ $student->phone ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Lending History --}}
        <div class="ds-card">
            <h3 class="ds-card-title">Lending History</h3>
            <div class="ds-table-wrap mt-3">
                <table class="ds-table ds-table-striped ds-table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Book</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Returned</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lends as $i => $lend)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $lend->book->title ?? '—' }}<br>
                                <small class="text-muted">Code: {{ $lend->book_code_no }}</small>
                            </td>
                            <td>{{ $lend->issue_date ? date('d M Y', strtotime($lend->issue_date)) : '—' }}</td>
                            <td>{{ $lend->return_date ? date('d M Y', strtotime($lend->return_date)) : '—' }}</td>
                            <td>
                                @if($lend->status === 'returned')
                                    {{ $lend->updated_at ? date('d M Y', strtotime($lend->updated_at)) : '—' }}
                                @else
                                    <span class="text-muted">—</span>
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
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No lending history for this student.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(request('user_id'))
        <div class="ds-card">
            <p class="text-center py-6 text-muted">Student not found in this school.</p>
        </div>
    @endif
</div>
@endsection
