{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Set Your Password — ' . ($school->name ?? 'KlassApp'))

@section('content')
<div class="auth-shell" data-testid="invite-password-form">
    <div class="auth-card" style="max-width: 480px;">
        <div class="auth-card-header">
            <h1 class="auth-card-title">Set your password</h1>
            <p class="auth-card-subtitle">
                You've been invited to join
                <strong>{{ $school->name ?? 'KlassApp' }}</strong>
                @if($className)
                as class teacher for <strong>{{ $className }}</strong>
                @endif
                .
            </p>
        </div>

        @if(session('error'))
            <div class="alert alert-error" role="alert" data-testid="invite-error">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error" role="alert" data-testid="invite-validation-errors">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('teacher.invite.claim', $token) }}" data-testid="invite-password-form-el">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    id="email"
                    class="form-input"
                    value="{{ $invite->email }}"
                    disabled
                    readonly
                >
                <p class="form-hint">This is the email the invite was sent to.</p>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Choose a password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input @error('password') form-input-error @enderror"
                    required
                    autocomplete="new-password"
                    minlength="8"
                    autofocus
                    data-testid="invite-password-input"
                >
                <p class="form-hint">At least 8 characters with one lowercase, one uppercase, and one number.</p>
                @error('password')
                    <p class="form-error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-input"
                    required
                    autocomplete="new-password"
                    minlength="8"
                    data-testid="invite-password-confirm-input"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-full" data-testid="invite-submit-btn">
                    Create account &amp; set password
                </button>
            </div>
        </form>

        <p class="auth-card-footer-text">
            This link expires {{ $invite->expires_at->diffForHumans() }}.
            If it has already expired, ask your school admin to send a new invitation.
        </p>
    </div>
</div>
</div>
@endsection
