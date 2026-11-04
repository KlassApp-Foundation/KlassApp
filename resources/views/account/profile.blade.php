{{-- SPDX-License-Identifier: MIT --}}
{{-- Profile, the same page for every role (routes/account.php). --}}
@extends($layout)

@section('content')
@php
    $user = auth()->user();
    $name = trim(($profile?->firstname ?? '').' '.($profile?->lastname ?? '')) ?: (string) $user->name;
@endphp
<div class="container-fluid w-full py-3 lg:mx-2" data-testid="account-profile">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Profile</h1>

    <x-card>
        <div class="flex flex-wrap items-center gap-4 mb-4">
            <x-profile-photo :user="$user" size="md" />
            <div>
                <p class="text-xl font-semibold text-gray-900" data-testid="account-profile-name">{{ $name }}</p>
                <p class="text-gray-800">{{ $roleLabel }}@if($school) &bull; {{ $school->name }}@endif</p>
            </div>
        </div>

        <dl class="grid gap-x-6 gap-y-3" style="grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));">
            <div><dt class="text-sm text-gray-700">Email</dt><dd class="text-gray-900" data-testid="account-profile-email">{{ $user->email ?: 'Not set' }}</dd></div>
            <div><dt class="text-sm text-gray-700">Phone</dt><dd class="text-gray-900">{{ $user->mobile_no ?: 'Not set' }}</dd></div>
            <div><dt class="text-sm text-gray-700">Role</dt><dd class="text-gray-900">{{ $roleLabel }}</dd></div>
        </dl>

        <div class="flex flex-wrap gap-3 mt-6">
            @if($links['change_password'])
                <x-button variant="primary" :href="url($links['change_password'])" data-testid="account-profile-change-password">Change password</x-button>
            @endif
            @if($links['edit_profile'])
                <x-button variant="outline" :href="url($links['edit_profile'])" data-testid="account-profile-edit">Edit profile details</x-button>
            @endif
            @if($links['change_avatar'])
                <x-button variant="outline" :href="url($links['change_avatar'])" data-testid="account-profile-change-photo">Change photo</x-button>
            @endif
        </div>
    </x-card>
</div>
@endsection
