{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')
@section('content')
<div class="max-w-2xl">
    <h1 class="my-3 admin-h1">WhatsApp Phone Number</h1>

    @if(session('success'))
        <div class="font-muller alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="font-muller alert alert-danger">
            <ul class="mb-0 pl-4">@foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="bg-white shadow border border-grey px-5 py-6 rounded-lg">
        @if($waUser && $waUser->phone)
            {{-- Already linked --}}
            <div class="flex items-center gap-4 mb-6 p-4 rounded-lg" style="background: #f0fdf4;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                <div>
                    <div class="font-semibold text-gray-900">WhatsApp Linked</div>
                    <div class="text-sm text-gray-600">
                        Your number <strong>{{ $waUser->phone }}</strong> is linked
                        @if($waUser->isVerified())
                            <span class="text-green-600 font-semibold">✓ Verified</span>
                        @else
                            <span class="text-amber-600">(Pending verification)</span>
                        @endif
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('whatsapp.phone') }}" onsubmit="return confirm('Unlink your WhatsApp number?');">
                @csrf
                <input type="hidden" name="unlink" value="1" />
                <button type="submit" class="ds-btn ds-btn-outline" data-testid="wa-unlink">
                    Unlink Number
                </button>
            </form>

        @else
            {{-- Not linked: scan the QR or open WhatsApp with the code ready. --}}
            <div class="flex items-center gap-4 mb-6 p-4 rounded-lg" style="background: #fff7ed;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="#9CA3AF"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                <div>
                    <div class="font-semibold text-gray-900">No WhatsApp Number Linked</div>
                    <div class="text-sm text-gray-600">Link your phone number to receive WhatsApp notifications from KlassApp.</div>
                </div>
            </div>

            @if(($link['ok'] ?? false) && ($link['code'] ?? '') !== '')
                <div id="wa-link-panel" data-testid="wa-link-panel" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:16px;">
                    <p class="text-sm font-medium text-gray-800 mb-3">Scan this with your phone, or open WhatsApp — the message is ready to send.</p>

                    @if($qrSvg)
                        <div class="mb-4 flex justify-center" data-testid="wa-link-qr">
                            {!! $qrSvg !!}
                        </div>
                    @endif

                    <p class="font-bold text-gray-900 mb-1">Your one-time code</p>
                    <p class="font-bold text-gray-900 mb-3" style="font-size:1.5rem;letter-spacing:0.2em;" data-testid="wa-link-code">{{ $link['code'] }}</p>

                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                           class="ds-btn ds-btn-primary"
                           style="background:#15803D;color:#FFFFFF;border-color:#15803D;"
                           data-testid="wa-link-open">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:6px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.272-.198z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.977.546 3.826 1.494 5.404L2 22l4.667-1.463A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.182c-1.736 0-3.37-.535-4.738-1.528l-.339-.234-2.77.868.918-2.686-.22-.352A8.164 8.164 0 013.818 12c0-4.509 3.673-8.182 8.182-8.182s8.182 3.673 8.182 8.182-3.673 8.182-8.182 8.182z"/></svg>
                            Open WhatsApp
                        </a>
                        <form method="POST" action="{{ route('whatsapp.phone.code') }}">
                            @csrf
                            <button type="submit" class="ds-btn ds-btn-outline" data-testid="wa-link-new-code">Get a new code</button>
                        </form>
                    </div>

                    <p class="text-sm text-gray-600 mb-1">Prefer to type it yourself? Message <strong data-testid="wa-link-number">{{ $link['business_number'] }}</strong> on WhatsApp and send:</p>
                    <p class="text-sm text-gray-800" style="font-family:monospace;" data-testid="wa-link-message">Link KlassApp: {{ $link['code'] }}</p>

                    <p class="text-sm mt-4" style="color:#0F766E;" data-testid="wa-link-waiting">This page updates by itself once your message arrives.</p>
                </div>
                <script>
                    (function () {
                        var statusUrl = @json(route('whatsapp.phone.status'));
                        function poll() {
                            fetch(statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                                .then(function (r) { return r.ok ? r.json() : null; })
                                .then(function (data) { if (data && data.linked) { window.location.reload(); } })
                                .catch(function () {});
                        }
                        setInterval(poll, 5000);
                    })();
                </script>
            @elseif(($link['message'] ?? '') !== '')
                <div class="font-muller alert alert-danger" role="alert" data-testid="wa-link-error">{{ $link['message'] }}</div>
            @endif
        @endif

        <hr class="my-6" />

        <div class="text-sm text-gray-500 space-y-1">
            <p>🔹 <strong>Why link?</strong> Receive important notifications, alerts, and updates via WhatsApp.</p>
            <p>🔹 Use the <strong>same phone number</strong> you use on WhatsApp.</p>
            <p>🔹 Any international number works — the message you send links it for you.</p>
        </div>
    </div>
</div>
@endsection
