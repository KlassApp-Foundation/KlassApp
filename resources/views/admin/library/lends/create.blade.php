@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Check Out Book</h1>
            <p class="dashboard-subtitle">Issue a book to a student.</p>
        </div>
        <a href="{{ route('admin.library.lends') }}" class="ds-btn ds-btn-ghost ds-btn-sm">&larr; Back to Lending</a>
    </div>

    @include('partials.message')

    <div class="ds-card max-w-xl">
        <form method="POST" action="{{ route('admin.library.lends.store') }}">
            @csrf

            <div class="mb-4">
                <label class="ds-label" for="library_card_no">Library Card No. <span class="text-red-500">*</span></label>
                <input type="text" name="library_card_no" id="library_card_no" value="{{ old('library_card_no') }}"
                       class="ds-form-input {{ $errors->has('library_card_no') ? 'ds-form-input-error' : '' }}"
                       placeholder="e.g. 1001" required>
                <p class="text-xs text-muted mt-1">Enter the student's library card number.</p>
                @error('library_card_no') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="ds-label" for="book_code">Book Code <span class="text-red-500">*</span></label>
                <select name="book_code" id="book_code"
                        class="ds-form-input ds-form-select {{ $errors->has('book_code') ? 'ds-form-input-error' : '' }}" required>
                    <option value="">Select a book</option>
                    @foreach($books as $book)
                        <option value="{{ $book->book_code }}" {{ old('book_code') == $book->book_code ? 'selected' : '' }}>
                            [{{ $book->book_code }}] {{ $book->title }} — {{ $book->author ?? 'No author' }}
                        </option>
                    @endforeach
                </select>
                @error('book_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="ds-label" for="issue_date">Issue Date</label>
                    <input type="date" name="issue_date" id="issue_date"
                           value="{{ old('issue_date', date('Y-m-d')) }}"
                           class="ds-form-input {{ $errors->has('issue_date') ? 'ds-form-input-error' : '' }}">
                    @error('issue_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="ds-label" for="return_date">Due Date</label>
                    <input type="date" name="return_date" id="return_date"
                           value="{{ old('return_date', date('Y-m-d', strtotime('+5 days'))) }}"
                           class="ds-form-input {{ $errors->has('return_date') ? 'ds-form-input-error' : '' }}">
                    @error('return_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <a href="{{ route('admin.library.lends') }}" class="ds-btn ds-btn-ghost mr-2">Cancel</a>
                <button type="submit" class="ds-btn ds-btn-primary">Check Out</button>
            </div>
        </form>
    </div>
</div>
@endsection
