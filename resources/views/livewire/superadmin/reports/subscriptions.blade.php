{{-- SPDX-License-Identifier: MIT --}}
<div>
	@if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

    {{-- <x-filament::button
    size="lg"
    color="success"
        href="{{ url('/superadmin/reports/subscription/create') }}"
        tag="a"
        outlined
    >
        Create Subscription
    </x-filament::button> --}}

    <div class="ds-page-head">
        <div>
            <h1 class="ds-page-head-title">Subscriptions</h1>
        </div>
        <div>
            <a href="{{ url('/superadmin/reports/subscription/create') }}" class="ds-btn ds-btn-primary ds-btn-sm">
                Create Subscription
            </a>
        </div>
    </div>


    {{ $this->table }}
</div>