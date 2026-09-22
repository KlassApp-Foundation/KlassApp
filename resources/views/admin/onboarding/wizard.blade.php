{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
{{-- Manual path: Toshi is hidden entirely so its checklist cannot compete with the
     wizard. The body class is applied server-side (layouts/app.blade.php) and the
     panel plus toggle are hidden in public/css/dashboard-refresh.css; no inline
     script, so there is no ordering race against the toggle element. --}}
<div class="manual-wizard-page py-8 px-5" data-testid="manual-wizard-page" data-toshi-manual-wizard="1" style="background: var(--d-canvas, #FAFAF5); min-height: 60vh;">
    @livewire('manual-onboarding-wizard')
</div>@endsection
