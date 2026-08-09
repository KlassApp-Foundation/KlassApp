@php
    $classOptions = collect($classes ?? [])->mapWithKeys(fn ($class) => [$class => $class])->all();
@endphp

<div class="py-6 flex justify-center px-4">
    <x-card padding="lg" shadow="lg" class="w-full max-w-md">
        <form method="POST" action="{{ route('admin.class.add') }}">
            @csrf

            <x-form-group
                label="Class Name"
                name="name"
                type="select"
                required
                placeholder="-- Select Class --"
                :options="$classOptions"
                :value="old('name')"
                :error="$errors?->first('name') ?: null"
            />

            <div class="mt-6 flex justify-end">
                <x-button type="submit" variant="primary">Submit</x-button>
            </div>
        </form>
    </x-card>
</div>
