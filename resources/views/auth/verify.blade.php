{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')
<style>

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

    .klass-auth-text {
        margin: 10px 0 0;
        color: #334155;
        font-size: 0.92rem;
        line-height: 1.6;
        text-align: center;
    }

    .klass-status {
        margin-top: 14px;
        border-radius: 10px;
        padding: 10px 12px;
        background: #ecfdf3;
        border: 1px solid #86efac;
        color: #166534;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .klass-link {
        color: #1E6FD9;
        font-weight: 700;
        text-decoration: none;
    }

    .klass-link:hover {
        text-decoration: underline;
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
            <h1 class="klass-auth-title">{{ __('Verify Your Email Address') }}</h1>

            @if (session('resent'))
                <div class="klass-status" role="status">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            @endif

            <p class="klass-auth-text">
                {{ __('Before proceeding, please check your email for a verification link.') }}
            </p>
            <p class="klass-auth-text">
                {{ __('If you did not receive the email, please contact your school administrator to request a new verification link.') }}
            </p>
        </div>
    </div>
</div>
@endsection
