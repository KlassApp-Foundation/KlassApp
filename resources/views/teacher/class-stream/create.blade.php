{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-2xl">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add stream</h1>
            <p class="mt-1 text-sm text-gray-600">
                Add a stream under <strong>{{ $base->name }}</strong>.
                The undivided base class stays; the new stream is named
                <strong>{{ $base->name }} &lt;stream&gt;</strong>
                (for example {{ $base->name }} A).
            </p>
        </div>
        <a href="{{ route('teacher.class-stream.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back</a>
    </div>

    @include('partials.message')

    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('teacher.class-stream.store', $section) }}" data-testid="ct-add-stream-form">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1" for="stream">Stream name</label>
                <input id="stream" type="text" name="stream" value="{{ old('stream') }}"
                       class="form-control w-full @error('stream') border-red-500 @enderror"
                       placeholder="e.g. A, East, Science" required
                       data-testid="ct-stream-name">
                @error('stream')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium" data-testid="ct-stream-submit">
                Add stream
            </button>
        </form>
    </div>
</div>
@endsection
