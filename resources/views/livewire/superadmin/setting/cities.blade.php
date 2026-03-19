{{-- SPDX-License-Identifier: MIT --}}
<div>
	@if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
<div class="" style="display:flex;justify-content: space-between;padding:10px;align-items:center;">
<div class="">
    <p style="font-size:1.25rem; font-weight:700;">Cities</p>
</div>
    <x-filament::button
    size="lg"
    color="" style="background-color:green; padding-bottom: 10px !important;
    padding-top: 10px !important; padding-left: 14px !important;
    padding-right: 14px !important; background-color: rgba(5,122,85,var(--tw-bg-opacity));"
        href="{{ url('/superadmin/setting/city/create') }}"
        tag="a"
       
    >
        Create City
    </x-filament::button>

</div>
<div class="" style="" >
    {{ $this->table }}</div>
</div>
