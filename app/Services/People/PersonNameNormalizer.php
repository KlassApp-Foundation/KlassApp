<?php

namespace App\Services\People;

/**
 * Splits and proper-cases a person's name for imported staff and parents (T-105).
 *
 * Two problems are fixed:
 *  1. The whole name sits in the first-name field and the last name is empty ("AGNES KALIISA").
 *  2. A part is all capitals ("KALIISA").
 *
 * It never guesses: a lone word, or three or more words with no last name, is reported as
 * ambiguous and left as it is. Nothing here touches a name that already has mixed case.
 */
final class PersonNameNormalizer
{
    public const OK = 'ok';

    public const SPLIT = 'split';

    public const CASED = 'cased';

    public const AMBIGUOUS = 'ambiguous';

    /** Surname particles that stay lower case when they are not the first word of the surname. */
    private const PARTICLES = ['van', 'von', 'der', 'den', 'de', 'del', 'da', 'di', 'du', 'la', 'le', 'bin', 'ibn', 'bint'];

    /**
     * @return array{status: string, first: ?string, last: ?string, reason: ?string}
     */
    public function normalize(?string $first, ?string $last): array
    {
        $first = $this->squash((string) $first);
        $last = $this->squash((string) $last);

        if ($first === '' && $last === '') {
            return $this->result(self::OK, $first, $last);
        }

        if ($last === '') {
            $words = preg_split('/\s+/u', $first, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($words) === 1) {
                // One word and no last name: nothing to split. Not an error unless it is all capitals.
                return $this->ambiguous($first, '', 'one word and no last name');
            }
            if (count($words) === 2) {
                return $this->result(self::SPLIT, $this->properCase($words[0], false), $this->properCase($words[1], true));
            }

            return $this->ambiguous($first, '', count($words).' words in the first name and no last name');
        }

        $newFirst = $this->isAllCaps($first) ? $this->properCase($first, false) : $first;
        $newLast = $this->isAllCaps($last) ? $this->properCase($last, true) : $last;
        if ($newFirst === $first && $newLast === $last) {
            return $this->result(self::OK, $first, $last);
        }

        return $this->result(self::CASED, $newFirst, $newLast);
    }

    private function isAllCaps(string $value): bool
    {
        return preg_match('/\p{L}/u', $value) === 1
            && mb_strlen(preg_replace('/[^\p{L}]/u', '', $value) ?? '') >= 2
            && mb_strtoupper($value) === $value;
    }

    /**
     * "KALIISA" becomes "Kaliisa"; hyphens and apostrophes start a new capital ("KATO-NAKAMYA"
     * becomes "Kato-Nakamya", "O'BRIEN" becomes "O'Brien"); "MCDONALD" becomes "McDonald".
     */
    private function properCase(string $value, bool $surname): string
    {
        $words = preg_split('/\s+/u', mb_strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($words as $i => $word) {
            if ($surname && $i > 0 && in_array($word, self::PARTICLES, true)) {
                $out[] = $word;

                continue;
            }
            $out[] = $this->capitalWord($word);
        }

        return implode(' ', $out);
    }

    private function capitalWord(string $word): string
    {
        $parts = preg_split("/([-'’])/u", $word, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$word];
        $joined = '';
        foreach ($parts as $part) {
            $joined .= in_array($part, ['-', "'", '’'], true) ? $part : mb_strtoupper(mb_substr($part, 0, 1)).mb_substr($part, 1);
        }

        // Mc + capital: only when long enough to be a real name, so "Mcm" is left alone.
        if (preg_match('/^Mc([a-z])(.{2,})$/u', $joined, $m) === 1) {
            return 'Mc'.mb_strtoupper($m[1]).$m[2];
        }

        return $joined;
    }

    private function squash(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /**
     * @return array{status: string, first: ?string, last: ?string, reason: ?string}
     */
    private function result(string $status, string $first, string $last): array
    {
        return ['status' => $status, 'first' => $first, 'last' => $last, 'reason' => null];
    }

    /**
     * @return array{status: string, first: ?string, last: ?string, reason: ?string}
     */
    private function ambiguous(string $first, string $last, string $reason): array
    {
        return ['status' => self::AMBIGUOUS, 'first' => $first, 'last' => $last, 'reason' => $reason];
    }
}
