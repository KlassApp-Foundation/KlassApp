@extends('layouts.admin.layout')

@section('content')
<div class="py-6 px-4">
    @php
        $fee = $fee ?? null;
        $levelOptions = ['______' => 'Select Level'] + ($standards ?? collect())->pluck('name', 'id')->toArray();
        $sectionOptions = ['' => 'All'] + ($sections ?? collect())->pluck('name', 'id')->toArray();
        $termOptions = ['' => 'All'] + ($terms ?? collect())->pluck('name', 'id')->toArray();
    @endphp

    <div class="ds-page-head mb-4">
        <h1 class="ds-page-head-title">{{ $fee ? 'Update Fee' : 'Add Fee' }}</h1>
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

    <x-card padding="lg" shadow="md" class="max-w-3xl">
        <form action="{{ $fee ? route('admin.fees-categories.update', $fee->id) : route('admin.fees-categories.store') }}" method="POST">
            @csrf
            @if ($fee !== null)
                @method('PATCH')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form-group
                    label="Level"
                    name="standard_id"
                    type="select"
                    required
                    :options="$levelOptions"
                    :value="old('standard_id', $fee?->standard_id ?? '______')"
                    :error="$errors?->first('standard_id') ?: null"
                />

                <x-form-group
                    label="Class"
                    name="section_id"
                    type="select"
                    :options="$sectionOptions"
                    :value="old('section_id', $fee?->section_id)"
                    :error="$errors?->first('section_id') ?: null"
                />

                <x-form-group
                    label="Academic Term (Optional)"
                    name="academic_term_id"
                    type="select"
                    :options="$termOptions"
                    :value="old('academic_term_id', $fee?->academic_term_id)"
                    :error="$errors?->first('academic_term_id') ?: null"
                />

                <x-form-group
                    label="Fee Name"
                    name="name"
                    type="text"
                    required
                    placeholder="Academics"
                    :value="old('name', $fee?->name)"
                    :error="$errors?->first('name') ?: null"
                />

                <x-form-group
                    label="Amount"
                    name="amount"
                    type="number"
                    required
                    :value="old('amount', $fee?->amount)"
                    :error="$errors?->first('amount') ?: null"
                    class="md:col-span-2"
                />
            </div>

            <div class="mt-6 flex items-center justify-end">
                <x-button type="submit" variant="primary">
                    {{ $fee ? 'Update Fee' : 'Add Fee' }}
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
