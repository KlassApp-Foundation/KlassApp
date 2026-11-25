<?php

namespace App\Services\Reports;

use App\Services\Results\ResultCell;

/**
 * The words a progressive card prints in a mark cell.
 * A counted blank prints a dash; it never shows 0 and never says ABS.
 */
final class MarkCellText
{
    public static function isCountedBlank(?ResultCell $cell, bool $countMissingAsZero): bool
    {
        return $countMissingAsZero
            && $cell instanceof ResultCell
            && $cell->mark === null
            && ($cell->status === ResultCell::MISSING || $cell->status === ResultCell::ABSENT);
    }

    public static function word(?ResultCell $cell): string
    {
        return ResultCell::DASH;
    }

    /**
     * @param list<array{cells?: array<int, ResultCell>}> $sittings
     */
    public static function columnForBlank(array $sittings, int $subjectId, bool $countMissingAsZero): bool
    {
        foreach ($sittings as $sitting) {
            $cell = $sitting['cells'][$subjectId] ?? null;
            if (self::isCountedBlank($cell, $countMissingAsZero)) {
                return true;
            }
        }

        return false;
    }
}
