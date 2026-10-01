{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Invite Link — ' . config('app.name'))

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="invite-link" data-testid="invite-invalid-page">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'An open education protocol for humans and agents.',
      'support' => 'Classes, fees, and parent updates from one connected system.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card">
        @if($reason === 'expired')
          <div class="err-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
          <h1 class="ap-title">This invite has expired</h1>
          <p class="ap-sub">
            @if(isset($invite))
              It expired on {{ $invite->expires_at->format('j M Y') }} at {{ $invite->expires_at->format('g:i A') }}.
            @endif
            Invite links last {{ (int) config('invites.expiry_hours', 72) }} hours.
          </p>
          <div class="ap-actions">
            <a href="{{ route('login') }}" class="ap-submit" data-testid="invite-goto-login">Go to sign in</a>
          </div>
          <p class="ap-meta">Ask your school admin to send a new invite. It will arrive at the same email address.</p>
        @elseif($reason === 'claimed')
          <div class="err-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#15803D" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
          </div>
          <h1 class="ap-title">This invite has already been used</h1>
          <p class="ap-sub">An account was created with it. Sign in with that email and the password you chose.</p>
          <div class="ap-actions">
            <a href="{{ route('login') }}" class="ap-submit" data-testid="invite-goto-login">Sign in</a>
            <a href="{{ route('password.email') }}" class="ap-secondary">Forgot password?</a>
          </div>
        @else
          <div class="err-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
          </div>
          <h1 class="ap-title">This invite link doesn't work</h1>
          <p class="ap-sub">It may be incomplete. Open the link straight from the email rather than copying it.</p>
          <div class="ap-actions">
            <a href="{{ route('login') }}" class="ap-submit" data-testid="invite-goto-login">Go to sign in</a>
          </div>
          <p class="ap-meta">Still stuck? Ask your school admin to send a new invite.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
