{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')
  <div style="width:100%;max-width:28rem;background:#ffffff;color:#0F172A;border:1px solid #E2E8F0;border-radius:12px;padding:32px;">
    <h1 style="font-size:1.5rem;font-weight:700;margin:0 0 8px;">Confirm your email</h1>
    <p style="margin:0 0 24px;color:#334155;">This link does not verify your address until you confirm.</p>
    <form method="POST" action="{{ route('emailverification.confirm', ['token' => $token]) }}" onsubmit="return confirm('Verify this email address?');">
      @csrf
      <button type="submit" style="background:#15803D;color:#ffffff;border:0;border-radius:8px;padding:10px 16px;font-weight:600;cursor:pointer;">
        Verify email
      </button>
    </form>
  </div>
@endsection
