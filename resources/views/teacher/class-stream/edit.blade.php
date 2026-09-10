{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-2xl">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rename stream</h1>
            <p class="mt-1 text-sm text-gray-600">
                Update the class / stream name. Use the full name (for example
                <strong>Primary One East</strong>), matching how streams are encoded.
            </p>
        </div>
        <a href="{{ route('teacher.class-stream.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back</a>
    </div>

    @include('partials.message')

    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('teacher.class-stream.update', $section) }}" data-testid="ct-rename-stream-form">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1" for="name">Class / stream name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $section->name) }}"
                       class="form-control w-full @error('name') border-red-500 @enderror"
                       required
                       data-testid="ct-rename-name">
                @error('name')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium" data-testid="ct-rename-submit">
                Save name
            </button>
        </form>
    </div>
</div>
@endsection
