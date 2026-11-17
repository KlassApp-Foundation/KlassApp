@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" style="background:#f8fafc;color:#0f172a;min-width:0;max-width:100%;">
    @include('layouts.partials.page-header', [
        'title' => 'Make '.$term->name.' the current term',
        'subtitle' => 'The current term is the one reports and exams start from.',
    ])
    @include('partials.message')

    <form method="POST" action="{{ route('admin.academic-term.make-current.store', $term->id) }}" data-testid="make-current-form" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
        @csrf
        @if ($alreadyCurrent)
            <p data-testid="make-current-already">{{ $term->name }} is already the current term. Nothing needs doing.</p>
        @else
            <p data-testid="make-current-sentence">Make <strong>{{ $term->name }}</strong> the current term?
                @foreach ($demote as $row)
                    <span data-testid="make-current-demote">{{ $row['name'] }} stops being the current term and becomes {{ $row['to'] === 'last' ? 'a past term' : 'a coming term' }}.</span>
                @endforeach
            </p>
            <label style="display:block;margin:0 0 8px;"><input type="checkbox" name="confirm" value="1" data-testid="make-current-confirm"> Yes, make this the current term</label>
            <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px;" data-testid="make-current-submit">Make this the current term</button>
        @endif
        <a href="{{ route('admin.academic-term') }}" class="ds-btn" style="min-height:44px;display:inline-flex;align-items:center;">Cancel</a>
    </form>
</div>
@endsection
