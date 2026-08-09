@extends('layouts.admin.layout')

@section('content')
<div class="py-6 px-4">
    @php
        $new_term = $new_term ?? null;
        $termOptions = ['______' => 'Select Term'] + collect($terms ?? [])->mapWithKeys(fn ($term) => [$term => $term])->all();
    @endphp

    <div class="ds-page-head mb-4">
        <h1 class="ds-page-head-title">{{ $new_term ? 'Update Academic Term' : 'Add Academic Term' }}</h1>
    </div>

    @include('partials.message')

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card padding="lg" shadow="md" class="max-w-xl">
        <form action="{{ $new_term ? route('admin.academic-term.update', $new_term->id) : route('admin.academic-term.store') }}" method="POST">
            @csrf
            @if ($new_term !== null)
                @method('PATCH')
            @endif

            <x-form-group
                label="Name"
                name="name"
                type="select"
                :options="$termOptions"
                :value="old('name', $new_term?->name ?? '______')"
                :error="$errors?->first('name') ?: null"
            />

            <x-form-group
                label="Starts On"
                name="starts_on"
                type="date"
                :value="old('starts_on', optional($new_term)->starts_on?->format('Y-m-d'))"
                :error="$errors?->first('starts_on') ?: null"
            />

            <x-form-group
                label="Ends On"
                name="ends_on"
                type="date"
                :value="old('ends_on', optional($new_term)->ends_on?->format('Y-m-d'))"
                :error="$errors?->first('ends_on') ?: null"
            />

            <div class="mt-6 flex justify-end">
                <x-button type="submit" variant="primary">
                    {{ $new_term ? 'Update Term' : 'Save Academic Term' }}
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
