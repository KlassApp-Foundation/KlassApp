{{-- TO DICTH later on --}}
@extends('layouts.admin.layout')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-10">
    @include('partials.message')
    <x-card padding="lg" shadow="lg" class="w-full max-w-xl">
        {{-- Header --}}
        <div class="mb-6">
            <h2 class="ds-page-head-title">Create Class</h2>
            <p class="ds-page-head-sub mt-1">Fill in the details below and save.</p>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.classes.store') }}">
            @csrf

            {{-- Standard --}}
            <x-form-group label="Standard" name="standard_id" type="select" :options="$standards->pluck('name', 'id')->prepend('Select standard', '')->toArray()" :error="$errors->first('standard_id') ?? null" required />

            {{-- Class --}}
            @if(!empty($typedClassName))
                <x-form-group label="Class" name="name" type="text" placeholder="Type this school's class name" :value="old('name')" :error="$errors->first('name') ?? null" required class="typed-class-name" />
            @else
            <x-form-group label="Class" name="name" type="select" :error="$errors->first('name') ?? null" required>
                <select name="name" id="name" data-testid="uganda-class-levels"
                    class="ds-form-input ds-form-select {{ $errors->has('name') ? 'ds-form-input-error' : '' }}"
                    required>
                    <option value="">Select class</option>

                    @if(isset($nursery_classes))
                        @foreach ($nursery_classes as $class)
                            <option value="{{ $class }}" {{ old('name') == $class ? 'selected' : '' }}>{{ $class }}</option>
                        @endforeach
                    @endif

                    @if(isset($primary_classes))
                        @foreach ($primary_classes as $class)
                            <option value="{{ $class }}" {{ old('name') == $class ? 'selected' : '' }}>{{ $class }}</option>
                        @endforeach
                    @endif

                    @if(isset($olevel_classes))
                        @foreach ($olevel_classes as $class)
                            <option value="{{ $class }}" {{ old('name') == $class ? 'selected' : '' }}>{{ $class }}</option>
                        @endforeach
                    @endif

                    @if(isset($alevel_classes))
                        @foreach ($alevel_classes as $class)
                            <option value="{{ $class }}" {{ old('name') == $class ? 'selected' : '' }}>{{ $class }}</option>
                        @endforeach
                    @endif
                </select>
            </x-form-group>
            @endif

            {{-- Submit --}}
            <div class="flex justify-end mt-6">
                <x-button type="submit" variant="primary" size="md">Save Class</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
