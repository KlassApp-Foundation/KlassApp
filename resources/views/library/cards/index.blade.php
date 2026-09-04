@extends('layouts.library.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Library — Student Cards</h1>
            <p class="dashboard-subtitle">View library cards and lending history per student (read-only).</p>
        </div>
    </div>

    @include('partials.message')

    <div class="ds-card mb-6">
        <form method="GET" action="{{ route('library.cards') }}" class="flex flex-col md:flex-row gap-3 items-end">
            <div class="flex-1 w-full">
                <label class="ds-label" for="user_id">Select Student</label>
                <select name="user_id" id="user_id"
                        class="ds-form-input ds-form-select" onchange="this.form.submit()">
                    <option value="">— Choose a student —</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" {{ (string) request('user_id') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->name }} @if($s->registration_number)({{ $s->registration_number }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 w-full">
                <label class="ds-label" for="library_card_no">Or card number</label>
                <input type="text" name="library_card_no" id="library_card_no"
                       value="{{ request('library_card_no') }}"
                       class="ds-form-input" placeholder="e.g. 1001">
            </div>
            <button type="submit" class="ds-btn ds-btn-primary ds-btn-sm">Look up</button>
            @if(request('user_id') || request('library_card_no'))
                <a href="{{ route('library.cards') }}" class="ds-btn ds-btn-ghost ds-btn-sm">Clear</a>
            @endif
        </form>
    </div>

    @if($student)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="ds-card md:col-span-1">
                <h3 class="ds-card-title">Library Card</h3>
                @if($card)
                    <div class="mt-3">
                        <p><strong>Card No:</strong> <code>{{ $card->library_card_no }}</code></p>
                        <p><strong>Status:</strong>
                            @if($card->status == 1 || $card->status === 'active' || $card->status === true)
                                <span class="ds-badge ds-badge-paid">Active</span>
                            @else
                                <span class="ds-badge ds-badge-inactive">{{ is_bool($card->status) || is_numeric($card->status) ? ($card->status ? 'Active' : 'Inactive') : ucfirst((string) $card->status) }}</span>
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

            <div class="ds-card md:col-span-2">
                <h3 class="ds-card-title">{{ $student->displayName ?: $student->name }}</h3>
                <div class="mt-2 grid grid-cols-2 gap-4">
                    <p><strong>Reg No:</strong> {{ $student->registration_number ?? '—' }}</p>
                    <p><strong>Email:</strong> {{ $student->email ?? '—' }}</p>
                    <p><strong>Phone:</strong> {{ $student->phone ?? '—' }}</p>
                </div>
            </div>
        </div>

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
                            <td>{{ optional($lend->book->first())->title ?? '—' }}<br>
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
    @elseif(request('user_id') || request('library_card_no'))
        <div class="ds-card">
            <p class="text-center py-6 text-muted">
                @if(request('library_card_no') && ! $card)
                    No library card found for that number in this school.
                @else
                    Student not found in this school.
                @endif
            </p>
        </div>
    @endif
</div>
@endsection
