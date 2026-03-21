{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')
  <style>
    .klass-otp-page {
      width: 100%;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1.5rem;
      background: #0D1526;
    }

    .klass-otp-shell {
      width: 90%;
      max-width: 400px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .klass-otp-logo-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 1rem;
    }

    .klass-otp-logo {
      width: min(86vw, 300px);
      height: auto;
      display: block;
    }

    .klass-otp-card {
      background: #eef1f5;
      border-radius: 16px;
      box-shadow: 0 22px 42px rgba(5, 11, 27, 0.4);
      padding: 1.5rem;
      border: 1px solid rgba(255, 255, 255, 0.16);
    }

    @media (max-width: 640px) {
      .klass-otp-page {
        padding: 0.875rem;
      }

      .klass-otp-card {
        padding: 1.25rem;
      }
    }
  </style>

  <div class="klass-otp-page">
    <div class="klass-otp-shell">
      <a href="{{ url('/') }}" class="klass-otp-logo-wrap" aria-label="KlassApp Home">
        <img src="{{ asset('images/klassapp-logo-dark.png') }}" class="klass-otp-logo" alt="KlassApp Logo">
      </a>
      <div class="klass-otp-card">
        @include('admin.otp.create_form')
      </div>
    </div>
  </div>
@endsection
