@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="ds-page-head">
        <div>
            <h1 class="ds-page-head-title">Edit Transport Route</h1>
            <p class="ds-page-head-sub">Update route details for <strong>{{ $route->name }}</strong>.</p>
        </div>
        <a href="{{ route('admin.transport') }}" class="ds-btn ds-btn-ghost ds-btn-sm">&larr; Back to Routes</a>
    </div>

    @include('partials.message')

    <div class="ds-card max-w-2xl">
        <form method="POST" action="{{ route('admin.transport.update', $route->id) }}">
            @csrf @method('POST')

            <div class="mb-4">
                <label class="ds-label" for="name">Route Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $route->name) }}"
                       class="ds-form-input {{ $errors->has('name') ? 'ds-form-input-error' : '' }}" required>
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="ds-label" for="vehicle_number">Vehicle Number <span class="text-red-500">*</span></label>
                <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number', $route->vehicle_number) }}"
                       class="ds-form-input {{ $errors->has('vehicle_number') ? 'ds-form-input-error' : '' }}" required>
                @error('vehicle_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="ds-label" for="start_time">Start Time <span class="text-red-500">*</span></label>
                    <input type="time" name="start_time" id="start_time" value="{{ old('start_time', $route->start_time) }}"
                           class="ds-form-input {{ $errors->has('start_time') ? 'ds-form-input-error' : '' }}" required>
                    @error('start_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="ds-label" for="end_time">End Time <span class="text-red-500">*</span></label>
                    <input type="time" name="end_time" id="end_time" value="{{ old('end_time', $route->end_time) }}"
                           class="ds-form-input {{ $errors->has('end_time') ? 'ds-form-input-error' : '' }}" required>
                    @error('end_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="ds-label" for="stops">Stops <span class="text-red-500">*</span></label>
                <textarea name="stops" id="stops" rows="4"
                          class="ds-form-input {{ $errors->has('stops') ? 'ds-form-input-error' : '' }}" required>{{ old('stops', $route->stops) }}</textarea>
                @error('stops') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="status" value="1" {{ old('status', $route->status) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="ds-btn ds-btn-primary">Update Route</button>
                <a href="{{ route('admin.transport') }}" class="ds-btn ds-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
