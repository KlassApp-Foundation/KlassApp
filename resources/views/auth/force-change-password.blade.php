{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  .klass-force-form { width: 100%; max-width: 420px; }
  .klass-login-intro { text-align: center; margin-bottom: 32px; }
  .klass-login-logo-frame { display: block; margin: 0 auto 16px; width: 160px; }
  .klass-login-logo { width: 100%; height: auto; display: block; }
  .klass-intro-title {
    margin: 0; font-family: 'Sora', sans-serif;
    font-size: 22px; font-weight: 700; color: #0F172A; letter-spacing: -0.02em;
  }
  .klass-intro-sub { margin: 6px 0 0; color: #64748B; font-size: 14px; }
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
  .klass-submit {
    margin-top: 6px; width: 100%; border: 0; border-radius: 10px;
    background: #22C55E; color: #fff; font-size: 16px; font-weight: 700;
    padding: 14px; cursor: pointer; font-family: 'DM Sans', sans-serif;
    transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
  }
  .klass-submit:hover { background: #16A34A; transform: translateY(-1px); box-shadow: 0 10px 18px rgba(34,197,94,0.2); }
  .klass-submit:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.18); }
  .klass-error { font-size: 12px; color: #DC2626; margin-top: 4px; display: block; }
  .klass-flash {
    margin-bottom: 16px; padding: 12px 16px; border-radius: 10px; font-size: 13px; line-height: 1.4;
  }
  .klass-flash-error {
    border: 1px solid rgba(239, 68, 68, 0.18); background: #FEF2F2; color: #991B1B;
  }
  .klass-requirements {
    background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 16px; margin-bottom: 16px;
  }
  .klass-requirements-title { font-size: 12px; font-weight: 700; color: #0F172A; margin-bottom: 8px; }
  .klass-requirements ul { margin: 0; padding-left: 18px; font-size: 12px; color: #475569; }
  .klass-requirements li { margin-bottom: 4px; }
  @media (max-width: 480px) { .klass-force-form { padding: 0 16px; } }
</style>
@endpush

@section('content')
<div class="klass-force-form">
  <div class="klass-login-intro">
    <span class="klass-login-logo-frame">
      <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-login-logo" alt="KlassApp">
    </span>
    <p class="klass-intro-title">Update your password</p>
    <p class="klass-intro-sub">Your account was created with a temporary password. Please set a new one to continue.</p>
  </div>

  @if(session('successmessage'))
    <div class="klass-flash klass-flash-error" role="alert">{{ session('successmessage') }}</div>
  @endif

  <form method="POST" action="{{ route('password.force-change') }}">
    @csrf

    <div class="klass-requirements">
      <p class="klass-requirements-title">Password requirements</p>
      <ul>
        <li>At least 8 characters</li>
        <li>One uppercase letter</li>
        <li>One lowercase letter</li>
        <li>One number</li>
        <li>One special character</li>
      </ul>
    </div>

    <div class="klass-field">
      <label class="klass-label" for="current_password">Current password</label>
      <div class="klass-password-wrap">
        <input id="current_password" type="password" class="klass-input{{ $errors->has('current_password') ? ' is-invalid' : '' }}" name="current_password" required>
        <button class="klass-password-toggle" type="button" onclick="var i=document.getElementById('current_password');if(i)i.type=i.type==='password'?'text':'password';" aria-label="Show or hide password">
          <svg viewBox="0 0 24 24" fill="none"><path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/></svg>
        </button>
      </div>
      @error('current_password')
        <span class="klass-error" role="alert">{{ $message }}</span>
      @enderror
    </div>

    <div class="klass-field">
      <label class="klass-label" for="password">New password</label>
      <div class="klass-password-wrap">
        <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
        <button class="klass-password-toggle" type="button" onclick="var i=document.getElementById('password');if(i)i.type=i.type==='password'?'text':'password';" aria-label="Show or hide password">
          <svg viewBox="0 0 24 24" fill="none"><path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/></svg>
        </button>
      </div>
      @error('password')
        <span class="klass-error" role="alert">{{ $message }}</span>
      @enderror
    </div>

    <div class="klass-field">
      <label class="klass-label" for="password_confirmation">Confirm new password</label>
      <div class="klass-password-wrap">
        <input id="password_confirmation" type="password" class="klass-input" name="password_confirmation" required>
        <button class="klass-password-toggle" type="button" onclick="var i=document.getElementById('password_confirmation');if(i)i.type=i.type==='password'?'text':'password';" aria-label="Show or hide password">
          <svg viewBox="0 0 24 24" fill="none"><path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/></svg>
        </button>
      </div>
    </div>

    <button type="submit" class="klass-submit">Update password</button>
  </form>
</div>
@endsection
