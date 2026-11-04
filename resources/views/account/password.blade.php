{{-- SPDX-License-Identifier: MIT --}}
{{-- Change password, the same page for every role (routes/account.php). --}}
@extends($layout)

@section('content')
<div class="container-fluid w-full py-3 lg:mx-2" data-testid="account-password">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Change password</h1>
    <p class="text-gray-800 mb-4">You will stay signed in on this device. Any other device is signed out.</p>

    @if(session('status'))
        <div role="status" class="mb-4 rounded-lg border border-green-300 bg-green-50 p-4 text-green-900" data-testid="account-password-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-red-900" data-testid="account-password-errors">
            <p class="font-semibold">The password was not changed.</p>
            <ul class="list-disc pl-5">@foreach($errors->all() as $message) <li>{{ $message }}</li> @endforeach</ul>
        </div>
    @endif

    <x-card>
        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4" style="max-width: 32rem;">
            @csrf
            <div class="ds-form-group">
                <label for="oldpassword" class="ds-form-label">Current password</label>
                <input id="oldpassword" name="oldpassword" type="password" required autocomplete="current-password"
                       class="ds-form-input @error('oldpassword') ds-form-input-error @enderror">
            </div>
            <div class="ds-form-group">
                <label for="newpassword" class="ds-form-label">New password</label>
                <input id="newpassword" name="newpassword" type="password" required minlength="8" autocomplete="new-password" aria-describedby="newpassword-help"
                       class="ds-form-input @error('newpassword') ds-form-input-error @enderror">
                <p id="newpassword-help" class="ds-form-help">At least 8 characters, different from your current password.</p>
            </div>
            <div class="ds-form-group">
                <label for="confirmpassword" class="ds-form-label">Confirm new password</label>
                <input id="confirmpassword" name="confirmpassword" type="password" required minlength="8" autocomplete="new-password"
                       class="ds-form-input @error('confirmpassword') ds-form-input-error @enderror">
            </div>
            <div class="flex flex-wrap gap-3">
                <x-button type="submit" variant="primary">Change password</x-button>
                <x-button variant="ghost" :href="url($links['profile'] ?? '/')">Back to profile</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
