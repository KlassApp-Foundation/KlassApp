{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Set Your Password — ' . ($school->name ?? 'KlassApp'))

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="invite-set-password" data-testid="invite-password-form">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => "You're invited to " . ($school->name ?? 'KlassApp'),
      'support' => 'Set a password once, then sign in from any device.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card">
        <h1 class="ap-title">Set your password</h1>
        <p class="ap-sub">
          You've been invited to join <strong>{{ $school->name ?? 'KlassApp' }}</strong>
          @if($className)
            as class teacher for <strong>{{ $className }}</strong>
          @elseif(!empty($roleLabel))
            {{ $roleLabel }}
          @endif
          .
        </p>

        @if(session('error'))
          <div class="ap-alert ap-alert--error" role="alert" data-testid="invite-error">
            {{ session('error') }}
          </div>
        @endif

        @if($errors->any())
          <div class="ap-alert ap-alert--error" role="alert" data-testid="invite-validation-errors">
            @foreach($errors->all() as $error)
              <p style="margin:0 0 4px;">{{ $error }}</p>
            @endforeach
          </div>
        @endif

        <form method="POST" action="{{ route($claimRoute, $token) }}" class="ap-form" data-testid="invite-password-form-el">
          @csrf

          <div class="ap-field">
            <label class="ap-label" for="email">Email</label>
            <input type="email" id="email" class="ap-input" value="{{ $invite->email }}" readonly>
            <p class="ap-hint">The invite was sent to this address.</p>
          </div>

          <div class="ap-field">
            <label class="ap-label" for="password">Choose a password</label>
            <input type="password" id="password" name="password" class="ap-input" required autocomplete="new-password" minlength="8" autofocus data-testid="invite-password-input">
            <p class="ap-hint">At least 8 characters, with a lowercase letter, an uppercase letter and a number.</p>
            @error('password')
              <span class="ap-error" role="alert">{{ $message }}</span>
            @enderror
          </div>

          <div class="ap-field">
            <label class="ap-label" for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="ap-input" required autocomplete="new-password" minlength="8" data-testid="invite-password-confirm-input">
          </div>

          <button type="submit" class="ap-submit" data-testid="invite-submit-btn">Create account</button>
        </form>

        <div class="ap-note">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          <span>This link works once and expires in {{ (int) config('invites.expiry_hours', 72) }} hours, on {{ $invite->expires_at->format('j M Y') }} at {{ $invite->expires_at->format('g:i A') }}. If it expires, ask your school admin for a new invite.</span>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
