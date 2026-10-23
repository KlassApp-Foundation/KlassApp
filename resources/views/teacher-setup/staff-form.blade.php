{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Staff link — ' . ($school->name ?? 'KlassApp'))

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-testid="staff-link-form">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'Staff link for ' . ($school->name ?? 'KlassApp'),
      'support' => 'Only teachers already on the school list get in immediately.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card">
        @if($sent)
          <h1 class="ap-title">Waiting for your admin</h1>
          <p class="ap-sub">
            You’re not on the teacher list yet. Your school admin has been asked to approve you;
            once they do, they’ll send you your own setup link.
          </p>
          <a href="{{ url('/') }}" class="ds-btn ds-btn-outline ds-btn-md">Back to KlassApp</a>
        @else
          <h1 class="ap-title">Find your name</h1>
          <p class="ap-sub">Use the email or phone number your school has for you.</p>

          @if($errors->any())
            <div class="ap-alert ap-alert--error" role="alert">
              @foreach($errors->all() as $error)<p style="margin:0 0 4px;">{{ $error }}</p>@endforeach
            </div>
          @endif

          <form method="POST" action="{{ route('school.staff.submit', $token) }}">
            @csrf
            <div class="ap-field">
              <label for="name" class="ap-label">Your name (optional)</label>
              <input id="name" name="name" type="text" class="ap-input" value="{{ old('name') }}" autocomplete="name">
            </div>
            <div class="ap-field">
              <label for="email" class="ap-label">Email</label>
              <input id="email" name="email" type="email" class="ap-input" value="{{ old('email') }}" autocomplete="email">
            </div>
            <div class="ap-field">
              <label for="phone" class="ap-label">Phone</label>
              <input id="phone" name="phone" type="tel" class="ap-input" value="{{ old('phone') }}" autocomplete="tel">
            </div>
            <button type="submit" class="ds-btn ds-btn-primary ds-btn-md w-full">Continue</button>
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
