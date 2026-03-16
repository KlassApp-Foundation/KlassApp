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

    <div class="" style="display:flex;justify-content: space-between;padding:10px;align-items:center;">
    <div class="">
        <p style="font-size:1.25rem; font-weight:700;">Subscriptions</p>
    </div>

    <x-filament::button
    size="lg"
    color="" style="background-color:green; padding-bottom: 10px !important;
    padding-top: 10px !important; padding-left: 14px !important;
    padding-right: 14px !important; background-color: rgba(5,122,85,var(--tw-bg-opacity));"
        href="{{ url('/superadmin/reports/subscription/create') }}"
        tag="a">
        Create Subscription
    </x-filament::button>

    </div>


    {{ $this->table }}
</div>