{{-- SPDX-License-Identifier: MIT --}}
@php
    $name = $childName ?? 'this child';
    $errors = $bundle['errors'] ?? [];
@endphp
@if(!empty($loading))
    <section class="family-state" aria-busy="true" data-testid="family-loading" aria-label="Loading">
        <p class="family-state__label">Loading</p>
        <div class="family-sk" style="height: 24px; width: 60%;"></div>
        <div class="family-sk" style="height: 56px;"></div>
        <div class="family-sk" style="height: 56px;"></div>
        <div class="family-sk" style="height: 56px;"></div>
        <p class="family-state__hint">Loading {{ $name }}'s results…</p>
    </section>
@else
    @if(($panel ?? '') === 'marks' && empty($bundle['grades']['exam_groups'] ?? []))
        <section class="family-state" data-testid="family-empty" aria-label="Empty">
            <div class="family-empty">
                <h2>No results yet this term</h2>
                <p>Results appear here when the school releases them. You'll also get a WhatsApp message.</p>
            </div>
        </section>
    @endif
    @if(!empty($errors['fees']) || !empty($errors['marks']) || !empty($errors['attendance']))
        <section class="family-state" data-testid="family-error" aria-label="Error">
            <div class="family-alert" role="alert">
                @if(!empty($errors['fees']))
                    <strong>Couldn't load {{ $name }}'s fees</strong>
                    <p>Marks and attendance are up to date. Fees will show when the connection is back.</p>
                @elseif(!empty($errors['marks']))
                    <strong>Couldn't load {{ $name }}'s marks</strong>
                    <p>Attendance and fees are up to date. Marks will show when the connection is back.</p>
                @else
                    <strong>Couldn't load {{ $name }}'s attendance</strong>
                    <p>Marks and fees are up to date. Attendance will show when the connection is back.</p>
                @endif
                <a class="ds-btn ds-btn-primary" href="{{ url()->current() }}">Try again</a>
            </div>
        </section>
    @endif
@endif
