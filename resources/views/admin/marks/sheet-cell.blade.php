@if($cell && $cell->status === \App\Services\Results\ResultCell::ABSENT)
    <span data-testid="marks-cell-absent">ABS</span>
@elseif($cell && $cell->status === \App\Services\Results\ResultCell::MARKED && $cell->mark !== null)
    {{ rtrim(rtrim(number_format($cell->mark, 2, '.', ''), '0'), '.') }}
    @if($cell->grade)
        <span data-testid="marks-cell-grade">{{ $cell->grade }}</span>
    @endif
@else
    —
@endif
