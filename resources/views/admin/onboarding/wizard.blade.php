{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
{{-- Manual path: hide Toshi entirely so its stale 1/18 checklist cannot compete with the wizard. --}}
<div class="manual-wizard-page py-8 px-5" data-testid="manual-wizard-page" data-toshi-manual-wizard="1" style="background: var(--d-canvas, #FAFAF5); min-height: 60vh;">
    @livewire('manual-onboarding-wizard')
</div>
<script>
(function () {
    document.body.classList.add('toshi-collapsed', 'toshi-manual-wizard');
    var toggle = document.getElementById('toshi-toggle');
    if (toggle) toggle.textContent = '◀';
})();
</script>
@endsection
