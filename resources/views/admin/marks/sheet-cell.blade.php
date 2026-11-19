@if($cell && $cell->status === \App\Services\Results\ResultCell::ABSENT)
    <span data-testid="marks-cell-absent">ABS</span>
@elseif($cell && $cell->status === \App\Services\Results\ResultCell::WITHHELD)
    <span data-testid="marks-cell-withheld">Withheld</span>
@elseif($cell && $cell->status === \App\Services\Results\ResultCell::UNAPPROVED)
    <span data-testid="marks-cell-dash">—</span>
@elseif($cell && $cell->status === \App\Services\Results\ResultCell::MARKED && $cell->mark !== null)
    {{ $cell->sheetLabel() }}
    @if($cell->grade)
        <span data-testid="marks-cell-grade">{{ $cell->grade }}</span>
    @endif
@elseif($cell && $cell->status === \App\Services\Results\ResultCell::MISSING)
    <span data-testid="marks-cell-missing">Missing</span>
@else
    <span data-testid="marks-cell-missing">Missing</span>
@endif
