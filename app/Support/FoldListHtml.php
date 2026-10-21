<?php

/**
 * SPDX-License-Identifier: MIT
 */

namespace App\Support;

/**
 * Counts the entries a fold-list should hide. The markup stays as rendered;
 * this only decides the "Show N more" number.
 */
final class FoldListHtml
{
    /** @var list<string> */
    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    /**
     * Element children at the top of an HTML fragment.
     * Text, comments, and nested tags are not counted.
     */
    public static function elementCount(string $html): int
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        $length = strlen($html);
        $index = 0;
        $depth = 0;
        $count = 0;

        while ($index < $length) {
            $start = strpos($html, '<', $index);
            if ($start === false) {
                break;
            }

            $next = $html[$start + 1] ?? '';
            if ($next === '/') {
                $end = strpos($html, '>', $start);
                if ($end === false) {
                    break;
                }
                $depth = max(0, $depth - 1);
                $index = $end + 1;

                continue;
            }

            if ($next === '!' || $next === '?' || ! preg_match('/[A-Za-z]/', $next)) {
                $index = $start + 1;

                continue;
            }

            $cursor = $start + 1;
            while ($cursor < $length && (ctype_alnum($html[$cursor]) || $html[$cursor] === ':' || $html[$cursor] === '-' || $html[$cursor] === '_')) {
                $cursor++;
            }
            $element = strtolower(substr($html, $start + 1, $cursor - $start - 1));
            $quote = null;
            $selfClosing = false;
            while ($cursor < $length) {
                $character = $html[$cursor];
                if ($quote !== null) {
                    if ($character === $quote) {
                        $quote = null;
                    }
                    $cursor++;

                    continue;
                }
                if ($character === '"' || $character === "'") {
                    $quote = $character;
                    $cursor++;

                    continue;
                }
                if ($character === '/' && ($html[$cursor + 1] ?? '') === '>') {
                    $selfClosing = true;
                    $cursor += 2;
                    break;
                }
                if ($character === '>') {
                    $cursor++;
                    break;
                }
                $cursor++;
            }

            if ($depth === 0) {
                $count++;
            }
            if (! $selfClosing && ! in_array($element, self::VOID_ELEMENTS, true)) {
                $depth++;
            }
            $index = $cursor;
        }

        return $count;
    }

    public static function tbodyRowCount(string $html): int
    {
        if (! preg_match('/<tbody\b[^>]*>/i', $html, $open, PREG_OFFSET_CAPTURE)) {
            return 0;
        }
        $start = $open[0][1] + strlen($open[0][0]);
        $end = stripos($html, '</tbody>', $start);
        if ($end === false) {
            return 0;
        }

        return self::elementCount(substr($html, $start, $end - $start));
    }
}
