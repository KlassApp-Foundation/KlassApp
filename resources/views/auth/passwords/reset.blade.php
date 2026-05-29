{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')
<style>
    @import url("https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap");

    .klass-auth-page {
        width: 100%;
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 24px;
        font-family: "Plus Jakarta Sans", sans-serif;
        background: #0D1526;
    }

    .klass-auth-card {
        width: 100%;
        max-width: 460px;
        background: #EEF1F5;
        border-radius: 16px;
        box-shadow: 0 28px 60px rgba(5, 11, 27, 0.45), 0 8px 18px rgba(5, 11, 27, 0.28);
        padding: 36px;
    }

    .klass-auth-intro {
        text-align: center;
    }

.klass-auth-logo-frame {
    width: 180px;
    margin: 0 auto 4px;
    display: block;
}

.klass-auth-logo {
    width: 100%;
    height: auto;
    display: block;
}

    .klass-auth-logo {
        width: 236px;
        max-width: none;
        margin-left: -40px;
        height: auto;
        display: block;
    }

    .klass-auth-title {
        margin: 14px 0 0;
        color: #0d1526;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .klass-auth-sub {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .klass-field {
        margin-top: 14px;
    }

    .klass-label {
        display: block;
        margin-bottom: 8px;
        color: #0d1526;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .klass-input {
        width: 100%;
        min-height: 48px;
        border-radius: 8px;
        border: 1.5px solid #e2e8f0;
        background: #F6F8F8;
        color: #0d1526;
        font-size: 0.95rem;
        padding: 12px 14px;
        outline: none;
    }

    .klass-input:focus {
        border-color: #1e6fd9;
        box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.12);
    }

    .klass-error {
        margin-top: 6px;
        color: #dc2626;
        font-size: 0.75rem;
        font-weight: 600;
        display: block;
    }

    .klass-submit {
        margin-top: 18px;
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: #1E6FD9;
        color: #ffffff;
        font-size: 1rem;
        font-weight: 700;
        padding: 13px;
        cursor: pointer;
    }

    .klass-submit:hover {
        background: #1a5fc4;
    }

    @media (max-width: 640px) {
        .klass-auth-page { padding: 14px; }
        .klass-auth-card { padding: 24px; }
    }
</style>

<div class="klass-auth-page">
    <div class="klass-auth-card">
        <div class="klass-auth-intro">
            <span class="klass-auth-logo-frame">
                <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-auth-logo" alt="KlassApp">
            </span>
            <h1 class="klass-auth-title">{{ __('Create New Password') }}</h1>
            <p class="klass-auth-sub">Set a secure password to complete your account recovery.</p>
        </div>

        <form method="POST" action="{{ route('password.reset') }}" aria-label="{{ __('Reset Password') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="klass-field">
                <label for="email" class="klass-label">{{ __('E-Mail Address') }}</label>
                <input id="email" type="email" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" required autofocus>
                @if ($errors->has('email'))
                    <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
                @endif
            </div>

            <div class="klass-field">
                <label for="password" class="klass-label">{{ __('Password') }}</label>
                <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
                @if ($errors->has('password'))
                    <span class="klass-error" role="alert">{{ $errors->first('password') }}</span>
                @endif
            </div>

            <div class="klass-field">
                <label for="password-confirm" class="klass-label">{{ __('Confirm Password') }}</label>
                <input id="password-confirm" type="password" class="klass-input" name="password_confirmation" required>
            </div>

            <button type="submit" class="klass-submit">{{ __('Reset Password') }}</button>
        </form>
    </div>
</div>
@endsection
