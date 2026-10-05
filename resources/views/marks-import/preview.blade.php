@extends($layout)

@section('content')
@php
    $counts = $plan->counts();
    $toSave = $counts['new'] + $counts['update'];
    $newOrUnchanged = array_merge($plan->rowsWithOutcome('new'), $plan->rowsWithOutcome('update'), $plan->rowsWithOutcome('unchanged'));
@endphp
<div class="container-fluid w-full py-3 lg:mx-2" data-testid="marks-import-preview">
    @include('partials.message')
    @include('marks-import._header', ['title' => 'Check before saving'])

    <div role="status" class="mb-4 rounded-lg border border-blue-300 bg-blue-50 p-4 text-blue-900">
        <strong>Nothing has been saved yet.</strong> This is a preview of <span>{{ $plan->fileName }}</span>.
    </div>

    @if(!empty($plan->warnings))
        <div role="alert" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900" data-testid="marks-import-warnings">
            <p class="font-semibold">Heads up</p>
            <ul class="list-disc pl-5">@foreach($plan->warnings as $message) <li>{{ $message }}</li> @endforeach</ul>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-red-900">
            <ul class="list-disc pl-5">@foreach($errors->all() as $message) <li>{{ $message }}</li> @endforeach</ul>
        </div>
    @endif

    @if($plan->isBlocked())
        <div role="alert" class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-red-900" data-testid="marks-import-blocked">
            <p class="font-semibold">This file cannot be saved.</p>
            <ul class="list-disc pl-5">@foreach($plan->blockers as $message) <li>{{ $message }}</li> @endforeach</ul>
        </div>
    @endif

    @if(! $plan->isBlocked())
    <dl class="grid gap-3 mb-6" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));" data-testid="marks-import-counts">
        <div class="ds-card ds-card-padding-sm"><dt class="text-sm text-gray-800">Rows in file</dt><dd class="text-2xl font-bold text-gray-900">{{ $counts['total'] }}</dd></div>
        <div class="ds-card ds-card-padding-sm"><dt class="text-sm text-gray-800">New marks to save</dt><dd class="text-2xl font-bold text-gray-900" data-testid="count-new">{{ $counts['new'] }}</dd></div>
        <div class="ds-card ds-card-padding-sm"><dt class="text-sm text-gray-800">Differ from saved marks</dt><dd class="text-2xl font-bold text-gray-900" data-testid="count-update">{{ $counts['update'] }}</dd></div>
        <div class="ds-card ds-card-padding-sm"><dt class="text-sm text-gray-800">Already saved, same mark</dt><dd class="text-2xl font-bold text-gray-900" data-testid="count-unchanged">{{ $counts['unchanged'] }}</dd></div>
        <div class="ds-card ds-card-padding-sm"><dt class="text-sm text-gray-800">Skipped</dt><dd class="text-2xl font-bold text-gray-900" data-testid="count-skipped">{{ $counts['skipped'] }}</dd></div>
    </dl>

    @if($counts['skipped'] > 0)
        <section class="mb-6" aria-labelledby="skipped-heading">
            <h2 id="skipped-heading" class="text-lg font-semibold text-gray-900 mb-1">Skipped rows ({{ $counts['skipped'] }})</h2>
            <p class="text-gray-800 mb-2">These rows will not be saved. Fix them in the file and upload again, or enter them on the marks page.</p>
            <ul class="mb-3 list-disc pl-5 text-gray-800">
                @foreach($plan->skippedByReason() as $code => $n) <li>{{ $n }} &times; {{ $reasons[$code] ?? $code }}</li> @endforeach
            </ul>
            @include('marks-import._rows', ['rows' => $plan->rowsWithOutcome('skipped')])
        </section>
    @endif

    @if(count($newOrUnchanged) > 0)
        <section class="mb-6" aria-labelledby="matched-heading">
            <h2 id="matched-heading" class="text-lg font-semibold text-gray-900 mb-2">Matched rows ({{ count($newOrUnchanged) }})</h2>
            @include('marks-import._rows', ['rows' => $newOrUnchanged, 'showExisting' => true])
        </section>
    @else
        <div class="ds-empty-state mb-6">
            <p class="ds-empty-state-title">No rows can be saved</p>
            <p class="ds-empty-state-desc">Check the skipped rows above, fix the file and upload it again.</p>
        </div>
    @endif

    @endif

    @if(! $plan->isBlocked() && $toSave > 0)
        <form method="POST" action="{{ route($routePrefix.'.confirm', $exam) }}" class="ds-card ds-card-padding-default space-y-4" data-testid="marks-import-confirm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <p class="text-gray-900">This will save <strong>{{ $counts['new'] }}</strong> new mark(s).</p>

            @if($counts['update'] > 0)
                <label for="overwrite" class="flex items-start gap-3 text-gray-900" style="min-height:44px; cursor:pointer;">
                    <input id="overwrite" type="checkbox" name="overwrite" value="1" class="mt-1" style="width:24px;height:24px;flex-shrink:0;" @checked(old('overwrite'))>
                    <span><strong>Overwrite {{ $counts['update'] }} mark(s) that already have a different value.</strong>
                        <span class="block text-gray-800">Leave this unticked to keep the saved marks; those rows will be reported as skipped.</span></span>
                </label>
            @endif

            @if($plan->requiresReason)
                <div class="ds-form-group">
                    <label for="correction_reason" class="ds-form-label">Reason for correcting submitted marks (at least 10 characters)</label>
                    <textarea id="correction_reason" name="correction_reason" rows="3" required minlength="10" maxlength="500"
                              class="ds-form-input @error('correction_reason') ds-form-input-error @enderror">{{ old('correction_reason') }}</textarea>
                    @error('correction_reason') <p class="ds-form-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <x-button type="submit" variant="primary">Save marks</x-button>
                <x-button variant="ghost" :href="route($routePrefix.'.page', $exam)">Cancel</x-button>
            </div>
        </form>
    @else
        <x-button variant="outline" :href="route($routePrefix.'.page', $exam)">Upload a different file</x-button>
    @endif
</div>
@endsection
