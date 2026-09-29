{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Invite Link — ' . config('app.name'))

@section('content')
<div class="auth-shell" data-testid="invite-invalid-page">
    <div class="auth-card" style="max-width: 480px;">
        <div class="auth-card-header">
            @if($reason === 'expired')
                <h1 class="auth-card-title">Link expired</h1>
                <p class="auth-card-subtitle">
                    This invite link has expired
                    @if(isset($invite))
                    on {{ $invite->expires_at->format('j M Y, g:i A') }}.
                    @endif
                </p>
            @elseif($reason === 'claimed')
                <h1 class="auth-card-title">Already used</h1>
                <p class="auth-card-subtitle">
                    This invite link has already been used to create an account.
                    You can log in with your email and password.
                </p>
            @else
                <h1 class="auth-card-title">Invalid link</h1>
                <p class="auth-card-subtitle">
                    This invite link is not valid. It may have been mistyped or already used.
                </p>
            @endif
        </div>

        <div class="form-actions">
            <a href="{{ route('login') }}" class="btn btn-primary btn-full" data-testid="invite-goto-login">
                Go to login
            </a>
        </div>

        <p class="auth-card-footer-text">
            If you need a new invitation, please contact your school administrator.
        </p>
    </div>
</div>
</div>
@endsection
