{{-- SPDX-License-Identifier: MIT --}}
{{-- Part D2: pages behind the emailed "Confirm email" link.
     $state: confirm (GET, button POSTs) | confirmed (other device) | expired (410) | invalid (403).
     Never shows a school name or account data. --}}
@extends('layouts.auth-preview')

@php
  $titles = [
    'confirm' => 'Confirm your email',
    'confirmed' => 'Email confirmed — continue where you signed up',
    'expired' => 'This link has expired',
    'invalid' => "This link doesn't work",
  ];
@endphp

@section('title', $titles[$state] ?? 'Confirm your email')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="verify-link" data-state="{{ $state }}">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'Confirm your email',
      'support' => 'One tap, and your sign-up can continue.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card" data-testid="verify-link-{{ $state }}">

        @if ($state === 'confirm')
          <h1 class="ap-title">Confirm your email</h1>
          <p class="ap-sub">Tap the button to confirm the email address you signed up with.</p>

          <form method="POST" action="{{ $postUrl }}" class="ap-form" data-testid="verify-link-form">
            @csrf
            <button type="submit" class="ap-submit" data-testid="ap-primary-submit" style="margin-top: 20px;">Confirm email</button>
          </form>

        @elseif ($state === 'confirmed')
          <p class="ap-alert ap-alert--success" role="status" style="display:flex;align-items:center;gap:10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m8 12 3 3 5-6"></path></svg>
            Email confirmed
          </p>
          <h1 class="ap-title">Email confirmed — continue where you signed up</h1>
          <p class="ap-sub"><strong>{{ $email }}</strong> is confirmed.</p>
          <p class="ap-sub">Go back to the device where you signed up. That page continues on its own.</p>

          <div class="ap-actions">
            <a href="{{ route('login') }}" class="ap-secondary" data-testid="verify-link-signin">Sign in on this device instead</a>
          </div>
          <p class="ap-hint">You can close this page.</p>

        @elseif ($state === 'expired')
          <h1 class="ap-title">This link has expired</h1>
          <p class="ap-sub">Confirmation links work once, for a limited time. Ask for a new code and we'll send another message.</p>

          <div class="ap-actions">
            <a href="{{ route('register.verify') }}" class="ap-submit" style="text-decoration:none;" data-testid="verify-link-new-code">Send a new code</a>
            <a href="{{ route('login') }}" class="ap-secondary">Sign in</a>
          </div>
          <p class="ap-hint">Already confirmed? Just sign in.</p>

        @else
          <h1 class="ap-title">This link doesn't work</h1>
          <p class="ap-sub">It may have been cut off when copied. Open the email again and tap <strong>Confirm email</strong>, or enter the 6-digit code instead.</p>

          <div class="ap-actions">
            <a href="{{ route('register.verify') }}" class="ap-submit" style="text-decoration:none;" data-testid="verify-link-enter-code">Enter the code</a>
          </div>
        @endif

      </div>
    </div>
  </div>
</div>
@endsection
