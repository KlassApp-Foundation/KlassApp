@extends('layouts.admin.layout')

@section('content')
<div class="py-6 px-4">
    @php
        $fee = $fee ?? null;
        $appliesTo = old('applies_to', $appliesTo ?? 'whole_school');
        $selectedSections = array_map('intval', (array) old('section_ids', $selectedSections ?? []));
        $durationOptions = \App\Models\FeesCategories::durations();
        $typeOptions = \App\Models\FeesCategories::types();
        $termOptions = ['' => 'Every term'] + ($terms ?? collect())->pluck('name', 'id')->toArray();
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
                    label="Fee name"
                    name="name"
                    type="text"
                    required
                    placeholder="Tuition"
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
                />

                <x-form-group
                    label="Type"
                    name="fee_type"
                    type="select"
                    :options="$typeOptions"
                    :value="old('fee_type', $fee?->fee_type ?? 'tuition')"
                    :error="$errors?->first('fee_type') ?: null"
                />

                <x-form-group
                    label="Duration"
                    name="duration"
                    type="select"
                    :options="$durationOptions"
                    :value="old('duration', $fee?->duration ?? 'termly')"
                    :error="$errors?->first('duration') ?: null"
                />

                <x-form-group
                    label="Limit to one term"
                    name="academic_term_id"
                    type="select"
                    :options="$termOptions"
                    :value="old('academic_term_id', $fee?->academic_term_id)"
                    :error="$errors?->first('academic_term_id') ?: null"
                    help="Leave this on every term for a termly fee. A yearly fee with no term is due in the first term of the year, and it is counted once."
                    class="md:col-span-2"
                />
            </div>

            <fieldset class="ds-form-group mt-4">
                <legend class="ds-form-label">Classes</legend>
                <label class="flex items-center gap-2 mb-2">
                    <input type="radio" name="applies_to" value="whole_school" @checked($appliesTo !== 'classes')>
                    <span>All classes</span>
                </label>
                <label class="flex items-center gap-2 mb-2">
                    <input type="radio" name="applies_to" value="classes" @checked($appliesTo === 'classes')>
                    <span>Selected classes</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                    @forelse(($sections ?? collect()) as $section)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="section_ids[]" value="{{ $section->id }}" @checked(in_array((int) $section->id, $selectedSections, true))>
                            <span>{{ $section->name }}</span>
                        </label>
                    @empty
                        <p class="ds-form-help">No classes yet. This fee will cover the whole school.</p>
                    @endforelse
                </div>
                @if($errors?->first('section_ids'))
                    <p class="ds-form-error">{{ $errors->first('section_ids') }}</p>
                @endif
            </fieldset>

            <div class="mt-6 flex items-center justify-end">
                <x-button type="submit" variant="primary">
                    {{ $fee ? 'Update Fee' : 'Add Fee' }}
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
