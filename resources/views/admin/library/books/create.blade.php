@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Add Book</h1>
            <p class="dashboard-subtitle">Add a new book to the library inventory.</p>
        </div>
        <a href="{{ route('admin.library.books') }}" class="ds-btn ds-btn-ghost ds-btn-sm">&larr; Back to Books</a>
    </div>

    @include('partials.message')

    <div class="ds-card max-w-2xl">
        <form method="POST" action="{{ route('admin.library.books.store') }}">
            @csrf

            <div class="mb-4">
                <label class="ds-label" for="title">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                       class="ds-form-input {{ $errors->has('title') ? 'ds-form-input-error' : '' }}"
                       placeholder="e.g. Introduction to Mathematics" required>
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="ds-label" for="author">Author</label>
                <input type="text" name="author" id="author" value="{{ old('author') }}"
                       class="ds-form-input {{ $errors->has('author') ? 'ds-form-input-error' : '' }}"
                       placeholder="e.g. John Doe">
                @error('author') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="ds-label" for="category_id">Category <span class="text-red-500">*</span></label>
                <select name="category_id" id="category_id"
                        class="ds-form-input ds-form-select {{ $errors->has('category_id') ? 'ds-form-input-error' : '' }}" required>
                    <option value="">Select category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->category }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="ds-label" for="book_code">Book Code</label>
                    <input type="text" name="book_code" id="book_code" value="{{ old('book_code') }}"
                           class="ds-form-input {{ $errors->has('book_code') ? 'ds-form-input-error' : '' }}"
                           placeholder="e.g. BK-001">
                    @error('book_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="ds-label" for="isbn_number">ISBN</label>
                    <input type="text" name="isbn_number" id="isbn_number" value="{{ old('isbn_number') }}"
                           class="ds-form-input {{ $errors->has('isbn_number') ? 'ds-form-input-error' : '' }}"
                           placeholder="e.g. 978-3-16-148410-0">
                    @error('isbn_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="ds-label" for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 1) }}"
                           class="ds-form-input {{ $errors->has('quantity') ? 'ds-form-input-error' : '' }}"
                           min="1">
                    @error('quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <a href="{{ route('admin.library.books') }}" class="ds-btn ds-btn-ghost mr-2">Cancel</a>
                <button type="submit" class="ds-btn ds-btn-primary">Save Book</button>
            </div>
        </form>
    </div>
</div>
@endsection
