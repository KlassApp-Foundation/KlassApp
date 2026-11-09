{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="family-page" data-testid="parent-dashboard">
    <main class="family-main" style="width:100% !important;margin-left:0 !important;margin-right:0 !important;">
        <p class="family-kicker">Parent Portal</p>
        @include('partials.message')
        @if ($linkedChildCount === 0)
            <section class="family-empty" data-testid="parent-empty-children">
                <h2>No child linked to your account yet</h2>
                <p>{{ $emptyMessage ?? 'Ask the school to link your child, or send your child\'s KlassApp number to the school on WhatsApp.' }}</p>
                <a class="ds-btn ds-btn-primary" href="{{ route('parent.children') }}">Link a child</a>
            </section>
        @else
            @include('portal.partials.child-switcher', ['children' => $children, 'selectedChild' => $selectedChild])
            @if($loading)
                @include('portal.partials.family-states', ['loading' => true, 'childName' => $selectedChild['name'] ?? 'your child', 'bundle' => $bundle])
            @elseif($bundle)
                @include('portal.partials.family-states', ['loading' => false, 'panel' => 'home', 'childName' => $selectedChild['name'] ?? '', 'bundle' => $bundle])
                @if(empty($bundle['errors']['fees']))
                    @include('portal.partials.family-cards', ['bundle' => $bundle, 'links' => $links])
                @else
                    @include('portal.partials.family-cards', ['bundle' => array_merge($bundle, ['fees' => ['total_balance' => null, 'total_paid' => 0, 'total_fees' => 0]]), 'links' => $links])
                @endif
                <section class="family-whatsapp">
                    <strong>You also get these on WhatsApp.</strong>
                    Released results, a weekly attendance summary and fee receipts come to {{ $whatsappPhone ?: 'your WhatsApp number' }}.
                </section>
            @endif
        @endif
    </main>
</div>
@endsection
