{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="family-page" data-testid="fee-structure-page">
    <main class="family-main" style="width:100% !important;margin-left:0 !important;margin-right:0 !important;">
        @include('partials.message')
        <div class="family-page-head">
            <div>
                <h1>Fees</h1>
                <p>Amounts in UGX, from the school's country</p>
            </div>
            <a href="{{ route('admin.fees-categories.create') }}" class="ds-btn ds-btn-primary">Add a fee</a>
        </div>
        <nav class="teacher-actions" aria-label="Fees">
            <a href="{{ route('admin.fees-categories') }}" aria-current="page">Fee structure</a>
            <a href="{{ route('admin.fee-payments') }}">Balances and payments</a>
        </nav>

        @if(($state ?? '') === 'loading')
            <section data-testid="fee-loading" aria-busy="true">
                <div class="family-sk" style="height:24px;width:40%;"></div>
                <div class="family-sk" style="height:48px;"></div>
                <p>Loading the fee structure…</p>
            </section>
        @elseif(($state ?? '') === 'error')
            <section data-testid="fee-error" role="alert" class="family-alert">
                <strong>The fee structure wasn't loaded</strong>
                <p>Nothing was changed. Try again.</p>
                <a class="ds-btn ds-btn-primary" href="{{ url()->current() }}">Try again</a>
            </section>
        @elseif($fees === [])
            <section class="family-empty" data-testid="fee-structure-empty">
                <h2>No fees set up yet</h2>
                <p>Add each fee once, with its amount, duration and the classes it applies to. Totals per class are worked out for you.</p>
                <a class="ds-btn ds-btn-primary" href="{{ route('admin.fees-categories.create') }}">Add the first fee</a>
            </section>
        @else
            <section class="family-table-card">
                <h2>Fee structure</h2>
                <div class="family-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Fee</th><th>Amount</th><th>Duration</th><th>Applies to</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fees as $fee)
                                <tr data-testid="fee-structure-row" data-name="{{ $fee['name'] }}" data-duration="{{ $fee['duration'] }}" data-classes="{{ $fee['classes'] }}" data-due="{{ $fee['due'] ?? '' }}">
                                    <td>{{ $fee['name'] }}</td>
                                    <td>UGX {{ number_format($fee['amount'], 0) }}</td>
                                    <td>{{ $fee['duration'] }}</td>
                                    <td>{{ $fee['classes'] }}</td>
                                    <td><a href="{{ route('admin.fees-categories.edit', $fee['id']) }}">Edit</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="family-table-card" data-testid="fee-class-totals">
                <h2>Total per student</h2>
                @if($classTotals === [])
                    <p>Add classes to see a total for each one.</p>
                @else
                    <div class="family-scroll"><table>
                        <thead><tr><th>Classes</th><th>Term total</th><th>Year total</th></tr></thead>
                        <tbody>
                            @foreach($classTotals as $row)
                                <tr data-testid="fee-class-total" data-class="{{ $row['class'] }}" data-total="{{ number_format($row['total'], 2, '.', '') }}" data-year-total="{{ number_format($row['year_total'] ?? $row['total'], 2, '.', '') }}">
                                    <td>{{ $row['class'] }}</td>
                                    <td>UGX {{ number_format($row['total'], 0) }}</td>
                                    <td>UGX {{ number_format($row['year_total'] ?? $row['total'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                @endif
                <p>Termly fees count every term. Yearly fees count in Term 1 only. One-off fees count once, in the term they're charged.</p>
            </section>
        @endif
    </main>
</div>
@endsection
