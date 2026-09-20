{{-- SPDX-License-Identifier: MIT --}}
{{-- Wrapper for the maintenance settings route. NOTE: maintenance_settings.blade.php
     itself does @extends('layouts.app') + @section('content'), so its content
     OVERRIDES this wrapper's markup below - the include is kept for parity with
     the other settings wrappers, but treat maintenance_settings as the renderer. --}}
@extends('layouts.admin.layout')
@section('content')
<div class="w-full main-content flex h-auto">
<div class="flex flex-col lg:flex-row w-full">
<!-- settings sidebar start -->
@include('admin.settings.maintenance_settings')
</div>
</div>
@endsection