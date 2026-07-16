<?php

namespace App\Services;

/**
 * Detects multi-step patterns in user queries and generates structured plans.
 *
 * Phase 3 — Multi-Step Plan Display
 * ==================================
 * Heuristic planner: recognises common batch patterns like
 * "add students A, B, C" or "record attendance for X and Y and Z"
 * and decomposes them into individual tool-step arrays.
 *
 * Single-item queries return null — the normal flow is unaffected.
 */
class ToshiPlanService
{
    /**
     * Known tool signatures: which arg keys carry the "item" that
     * gets iterated in a batch, and which args are shared across all steps.
     */
    private const BATCH_TOOLS = [
        'toolAddStudent' => [
            'multi_key'    => 'name',
            'share_keys'   => ['class', 'stream', 'type', 'parent'],
            'label_format' => 'Add student: {name}',
        ],
        'toolRecordAttendance' => [
            'multi_key'    => 'student',
            'share_keys'   => ['date', 'status', 'session'],
            'label_format' => 'Record attendance: {student}',
        ],
        'toolCreateSubject' => [
            'multi_key'    => 'name',
            'share_keys'   => ['code'],
            'label_format' => 'Create subject: {name}',
        ],
        'toolCreateExam' => [
            'multi_key'    => 'name',
            'share_keys'   => ['subject', 'class', 'type', 'term', 'scheduled_at'],
            'label_format' => 'Create exam: {name}',
        ],
        'toolAddTeacher' => [
            'multi_key'    => 'name',
            'share_keys'   => ['email', 'phone'],
            'label_format' => 'Add teacher: {name}',
        ],
        'toolCreateFee' => [
            'multi_key'    => 'name',
            'share_keys'   => ['amount', 'level', 'class', 'term_name'],
            'label_format' => 'Create fee: {name}',
        ],
        'toolCreateTerm' => [
            'multi_key'    => 'name',
            'share_keys'   => ['start_date', 'end_date'],
            'label_format' => 'Create term: {name}',
        ],
    ];

    /**
     * Try to generate a plan from a user's free-text query.
     *
     * Returns null if the query doesn't look like a multi-step batch
     * (the caller should proceed with normal single-tool flow).
     *
     * @return array{steps: array, tool: string, baseArgs: array}|null
     */
    public function generatePlan(string $text): ?array
    {
        $lower = trim($text);
        if ($lower === '') {
            return null;
        }

        // Try each known batch tool pattern
        foreach (self::BATCH_TOOLS as $tool => $sig) {
            $result = $this->matchToolPattern($tool, $sig, $text);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Attempt to match a single tool's batch pattern against the query.
     * Returns null if this tool doesn't match.
     */
    private function matchToolPattern(string $tool, array $sig, string $text): ?array
    {
        $actionWords = $this->actionWords($tool);
        $multiKey = $sig['multi_key'];

        $items = $this->extractItems($text, $actionWords, $multiKey);
        if ($items === null || count($items) < 2) {
            return null;
        }

        // Extract shared base arguments from the remainder of the text
        $baseArgs = $this->extractSharedArgs($text, $tool, $items);

        $steps = [];
        foreach ($items as $item) {
            $args = $baseArgs;
            $args[$multiKey] = $item;

            $label = str_replace('{' . $multiKey . '}', $item, $sig['label_format']);

            $steps[] = [
                'tool'   => $tool,
                'args'   => $args,
                'label'  => $label,
                'status' => 'pending',
            ];
        }

        return [
            'steps'     => $steps,
            'tool'      => $tool,
            'baseArgs'  => $baseArgs,
        ];
    }

    /**
     * Action words that signal the tool intent (e.g. "add student", "record attendance").
     */
    private function actionWords(string $tool): array
    {
        $map = [
            'toolAddStudent'       => ['add student', 'add students', 'enroll student', 'enroll students'],
            'toolRecordAttendance' => ['record attendance', 'mark attendance', 'take attendance'],
            'toolCreateSubject'    => ['create subject', 'create subjects', 'add subject', 'add subjects'],
            'toolCreateExam'       => ['create exam', 'create exams', 'add exam', 'add exams'],
            'toolAddTeacher'       => ['add teacher', 'add teachers', 'hire teacher', 'hire teachers'],
            'toolCreateFee'        => ['create fee', 'create fees', 'add fee', 'add fees'],
            'toolCreateTerm'       => ['create term', 'create terms', 'add term', 'add terms'],
        ];
        return $map[$tool] ?? [];
    }

    /**
     * Extract individual item values from the query.
     *
     * Looks for comma-separated items, "and"-separated items,
     * or numbered lists after the action phrase.
     * Returns null if no batch items found.
     */
    private function extractItems(string $text, array $actionWords, string $multiKey): ?array
    {
        // Find the portion of text after the action word.
        // Sort by descending length so longer phrases ("add students")
        // match before shorter substrings ("add student").
        $sorted = $actionWords;
        usort($sorted, fn($a, $b) => strlen($b) <=> strlen($a));

        $after = null;
        foreach ($sorted as $word) {
            $pos = mb_stripos($text, $word);
            if ($pos !== false) {
                $after = trim(substr($text, $pos + strlen($word)));
                break;
            }
        }

        if ($after === null || $after === '') {
            return null;
        }

        // Strip common filler words that sit between the action phrase
        // and the actual item list (e.g. "record attendance for Alice").
        $after = preg_replace('/^(?:for|of|the|a|an)\s+/i', '', $after);

        // Strip common shared details like dates, statuses, class names
        // that might follow the list — they'd be picked up as baseArgs
        $candidates = $this->splitItems($after);

        if (count($candidates) < 2) {
            return null;
        }

        // Clean each candidate: remove leading numbers/bullets, trim
        $items = [];
        foreach ($candidates as $c) {
            $cleaned = trim(preg_replace('/^[\d\.\-\)\*]+/', '', $c));
            $cleaned = trim($cleaned, " \t\n\r\0\x0B,");
            if ($cleaned !== '' && strlen($cleaned) > 1) {
                $items[] = $cleaned;
            }
        }

        return count($items) >= 2 ? $items : null;
    }

    /**
     * Split a text fragment into candidate items.
     */
    private function splitItems(string $text): array
    {
        // Try comma separation first, then further split each item by "and"/"&".
        // This handles "Alice, Bob and Charlie" → ["Alice", "Bob", "Charlie"].
        if (mb_strpos($text, ',') !== false) {
            $parts = explode(',', $text);
            $result = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                $sub = preg_split('/\s+(and|&)\s+/i', $trimmed);
                foreach ($sub as $s) {
                    $t = trim($s);
                    if ($t !== '') {
                        $result[] = $t;
                    }
                }
            }
            return $result;
        }

        // Try " and " separation
        $parts = preg_split('/\s+(and|&)\s+/i', $text);
        if (count($parts) >= 2) {
            return $parts;
        }

        // Try numbered list (one per line)
        $lines = array_filter(explode("\n", $text), fn($l) => preg_match('/^\s*[\d\.\)\-]/', $l));
        if (count($lines) >= 2) {
            return array_values($lines);
        }

        // Fallback: split by whitespace and check if it looks like a list
        $words = preg_split('/\s+/', trim($text));
        if (count($words) >= 3 && count($words) <= 20) {
            return $words;
        }

        return [$text];
    }

    /**
     * Extract shared arguments from the text that apply to all steps.
     *
     * For example, in "record attendance for Alice, Bob on 2026-07-15 as present",
     * the date and status apply to all steps.
     */
    private function extractSharedArgs(string $text, string $tool, array $items): array
    {
        $args = [];

        switch ($tool) {
            case 'toolRecordAttendance':
                if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $m)) {
                    $args['date'] = $m[1];
                }
                foreach (['present', 'absent', 'late', 'excused'] as $s) {
                    if (mb_stripos($text, $s) !== false) {
                        $args['status'] = $s;
                        break;
                    }
                }
                break;

            case 'toolAddStudent':
                // Try to extract class name
                if (preg_match('/(?:in|to|for)\s+(class\s+)?(.+?)(?:[,.]|$)/i', $text, $m)) {
                    $classCandidate = trim($m[2]);
                    // Only use as class if it's not one of the found items
                    if (!in_array($classCandidate, $items)) {
                        $args['class'] = $classCandidate;
                    }
                }
                break;
        }

        return $args;
    }
}
