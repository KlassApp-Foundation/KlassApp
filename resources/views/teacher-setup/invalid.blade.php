{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Setup link not valid — KlassApp')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-testid="teacher-setup-invalid">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'Class setup',
      'support' => 'Ask your school admin for a fresh link.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card">
        <h1 class="ap-title">This link doesn’t work</h1>
        <p class="ap-sub">{{ $reason ?? 'This link is invalid or has expired.' }}</p>
        <a href="{{ url('/login') }}" class="ds-btn ds-btn-primary ds-btn-md">Go to sign in</a>
      </div>
    </div>
  </div>
</div>
@endsection
