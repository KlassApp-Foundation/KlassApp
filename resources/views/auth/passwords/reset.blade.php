{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  *, *::before, *::after { box-sizing: border-box; }

  .klass-auth-wrap {
    width: 100%;
    min-height: 100vh;
    display: grid;
    place-items: center;
    font-family: "DM Sans", sans-serif;
    background: #FAFAF5;
    position: relative;
    overflow: hidden;
    padding: 24px;
  }

  .klass-auth-wrap::before {
    content: "";
    position: absolute;
    inset: -20% -10%;
    background:
      radial-gradient(40% 35% at 15% 20%, rgba(34, 197, 94, 0.08) 0%, transparent 70%),
      radial-gradient(35% 30% at 85% 15%, rgba(30, 111, 217, 0.06) 0%, transparent 70%),
      radial-gradient(30% 25% at 75% 85%, rgba(34, 197, 94, 0.04) 0%, transparent 60%),
      radial-gradient(35% 30% at 20% 85%, rgba(30, 111, 217, 0.04) 0%, transparent 60%);
    pointer-events: none;
  }

  .klass-auth-inner {
    width: 100%;
    max-width: 420px;
    position: relative;
  }

  .klass-auth-intro {
    text-align: center;
    margin-bottom: 32px;
  }

  .klass-auth-logo-frame {
    display: block;
    margin: 0 auto 16px;
    width: 160px;
  }

  .klass-auth-logo {
    width: 100%;
    height: auto;
    display: block;
  }

  .klass-auth-title {
    margin: 0;
    font-family: 'Sora', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    letter-spacing: -0.02em;
  }

  .klass-auth-sub {
    margin: 6px 0 0;
    color: #64748B;
    font-size: 14px;
  }

  .klass-field { margin-bottom: 16px; }
  .klass-label { display: block; margin-bottom: 6px; color: #0F172A; font-size: 13px; font-weight: 600; }

  .klass-input {
    width: 100%; background: #F8FAFC; border: 1.5px solid #E2E8F0;
    border-radius: 10px; padding: 12px 16px; font-size: 15px; color: #1E293B;
    outline: none; min-height: 48px; font-family: 'DM Sans', sans-serif;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-input:focus { border-color: #22C55E; box-shadow: 0 0 0 3px rgba(34,197,94,0.12); }
  .klass-input::placeholder { color: #94A3B8; }
  .klass-input.is-invalid { border-color: #EF4444; }

  .klass-error {
    margin-top: 6px; display: block; color: #EF4444;
    font-size: 12px; line-height: 1.4; font-weight: 600;
  }

  .klass-submit {
    margin-top: 6px; width: 100%; border: 0; border-radius: 10px;
    background: #22C55E; color: #fff; font-size: 16px; font-weight: 700;
    padding: 14px; cursor: pointer; font-family: 'DM Sans', sans-serif;
    transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-submit:hover { background: #16A34A; transform: translateY(-1px); box-shadow: 0 10px 18px rgba(34,197,94,0.2); }
  .klass-submit:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.18); }

  .klass-back-link {
    display: block; margin-top: 16px; text-align: center;
    color: #64748B; font-size: 14px; text-decoration: none; font-weight: 500;
    transition: color 0.15s ease;
  }

  .klass-back-link:hover { color: #22C55E; }

  .klass-password-wrap { position: relative; }
  .klass-password-wrap .klass-input { padding-right: 46px; }

  .klass-password-toggle {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px; border-radius: 8px; border: none;
    background: transparent; cursor: pointer; display: flex;
    align-items: center; justify-content: center; color: #94A3B8;
    transition: color 0.15s ease;
  }

  .klass-password-toggle:hover { color: #0F172A; }
  .klass-password-toggle:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.12); }
  .klass-password-toggle svg { width: 20px; height: 20px; }

  @media (max-width: 640px) { .klass-auth-wrap { padding: 14px; } }
</style>
@endpush

@section('content')
<div class="klass-auth-wrap">
  <div class="klass-auth-inner">
    <div class="klass-auth-intro">
      <span class="klass-auth-logo-frame">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-auth-logo" alt="KlassApp">
      </span>
      <h1 class="klass-auth-title">{{ __('Set New Password') }}</h1>
      <p class="klass-auth-sub">Choose a new password for your account.</p>
    </div>

    <form method="POST" action="{{ route('password.reset') }}" aria-label="{{ __('Reset Password') }}">
      @csrf

      <input type="hidden" name="token" value="{{ $token }}">

      <div class="klass-field">
        <label class="klass-label" for="email">{{ __('E-Mail Address') }}</label>
        <input id="email" type="email" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" placeholder="you@school.edu" required>
        @if ($errors->has('email'))
          <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
        @endif
      </div>

      <div class="klass-field">
        <label class="klass-label" for="password">{{ __('New Password') }}</label>
        <div class="klass-password-wrap">
          <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
          <button class="klass-password-toggle" type="button" data-target="password" aria-label="Show or hide password">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"></path>
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"></circle>
            </svg>
          </button>
        </div>
        @if ($errors->has('password'))
          <span class="klass-error" role="alert">{{ $errors->first('password') }}</span>
        @endif
      </div>

      <div class="klass-field">
        <label class="klass-label" for="password-confirm">{{ __('Confirm New Password') }}</label>
        <div class="klass-password-wrap">
          <input id="password-confirm" type="password" class="klass-input" name="password_confirmation" required>
          <button class="klass-password-toggle" type="button" data-target="password-confirm" aria-label="Show or hide confirm password">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"></path>
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"></circle>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="klass-submit">{{ __('Reset Password') }}</button>
    </form>

    <a href="{{ url('/login') }}" class="klass-back-link">← Back to sign in</a>
  </div>
</div>
@endsection
@push('scripts')
<script>
(function() {
  'use strict';
  var toggles = document.querySelectorAll('.klass-password-toggle');
  for (var i = 0; i < toggles.length; i++) {
    toggles[i].addEventListener('click', function(e) {
      e.preventDefault();
      var input = document.getElementById(this.getAttribute('data-target'));
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  }
})();
</script>
@endpush
