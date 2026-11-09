<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Ugandan class order: nursery, then P.1–P.7, then S.1–S.6.
 * Short names (P.1, P1, Primary One) and long names share one rank.
 */
class ClassLevelOrder
{
    /** @var array<string, int> */
    private const WORDS = [
        'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4,
        'five' => 5, 'six' => 6, 'seven' => 7,
    ];

    /**
     * @param  Collection<int, mixed>  $rows
     * @param  callable(mixed): string  $name
     * @return Collection<int, mixed>
     */
    public static function sort(Collection $rows, ?callable $name = null): Collection
    {
        $name ??= fn ($row) => (string) (is_object($row) ? ($row->name ?? '') : ($row['name'] ?? $row['label'] ?? ''));

        return $rows->sortBy(fn ($row) => self::sortKey($name($row)))->values();
    }

    /** @return array{0: int, 1: int, 2: string} */
    public static function sortKey(string $name): array
    {
        $lower = strtolower(trim($name));
        $nursery = ['baby class' => 1, 'middle class' => 2, 'top class' => 3];
        if (isset($nursery[$lower])) {
            return [0, $nursery[$lower], $lower];
        }

        if (preg_match('/^(?:p\.?\s*|primary\s+)(\d+|one|two|three|four|five|six|seven)\b/i', $lower, $m)) {
            return [1, self::number($m[1]), $lower];
        }

        if (preg_match('/^(?:s\.?\s*|senior\s+)(\d+|one|two|three|four|five|six)\b/i', $lower, $m)) {
            return [2, self::number($m[1]), $lower];
        }

        return [9, 0, $lower];
    }

    private static function number(string $token): int
    {
        if (ctype_digit($token)) {
            return (int) $token;
        }

        return self::WORDS[strtolower($token)] ?? 99;
    }
}
