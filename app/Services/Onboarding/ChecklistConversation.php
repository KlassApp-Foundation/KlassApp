<?php

namespace App\Services\Onboarding;

use App\Models\OnboardingPlan;

/**
 * Spoken checklist replies. Every number and name comes from the saved plan.
 * This class does not call a model and does not write.
 */
class ChecklistConversation
{
    public function __construct(private ChecklistOnboardingService $plans) {}

    /**
     * @return array{say: string, open: bool}|null
     */
    public function hear(string $text, ?OnboardingPlan $plan, string $schoolName): ?array
    {
        $lower = $this->normalize($text);
        if ($lower === '') {
            return null;
        }

        $school = trim($schoolName) !== '' ? trim($schoolName) : 'this school';
        $brief = $this->readable($plan) ? $this->plans->conversationBrief($plan) : null;

        if ($this->wantsSetup($lower)) {
            if ($brief !== null && $brief['active'] && $brief['step'] !== null) {
                return [
                    'say' => $this->stepLine($brief),
                    'open' => false,
                ];
            }

            return [
                'say' => "I'll set up {$school} from your checklist. Please confirm the data agreement on the card. I won't read the file before that.",
                'open' => true,
            ];
        }

        if ($this->isGreeting($lower)) {
            if ($brief !== null && $brief['step'] !== null) {
                return [
                    'say' => 'Hi. '.$this->stepLine($brief),
                    'open' => false,
                ];
            }

            return [
                'say' => "Hi. I'm Toshi. I can set up {$school} from your checklist. Say “set up my school”, or use the button.",
                'open' => false,
            ];
        }

        if ($brief === null) {
            if ($this->isQuestion($lower)) {
                return [
                    'say' => "I don't have a checklist yet, so I don't know that. Say “set up my school”, or use the button.",
                    'open' => false,
                ];
            }

            return null;
        }

        if ($this->isDecision($lower)) {
            return ['say' => $this->handoff($brief), 'open' => false];
        }

        $answer = $this->fromPlan($lower, $brief);
        if ($answer !== null) {
            return ['say' => $answer, 'open' => false];
        }

        if ($this->isQuestion($lower) || $brief['active']) {
            $line = "I don't know that from this checklist.";
            if ($brief['active']) {
                $line .= ' '.$this->handoff($brief);
            }

            return ['say' => $line, 'open' => false];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function fromPlan(string $lower, array $brief): ?string
    {
        $named = $this->named($lower, $brief);
        if ($named !== [] && ! str_starts_with($lower, 'how many')) {
            return implode(' ', array_slice($named, 0, 3));
        }

        $classCount = $this->classCount($lower, $brief);
        if ($classCount !== null) {
            return $classCount;
        }

        $counts = $this->counts($lower, $brief);
        if ($counts !== []) {
            return implode(' ', $counts);
        }

        if ($this->asks($lower, ['what is wrong', "what's wrong", 'flag', 'problem', 'error'])) {
            return $this->flags($brief);
        }

        if ($this->asks($lower, ['invite'])) {
            return 'Invites ready: '.$brief['invites_ready'].'. Nothing was sent.';
        }

        if ($this->asks($lower, ['why', 'proposal', 'what will you', 'what are you'])) {
            return $brief['step'] === null
                ? 'This checklist has no step waiting. Nothing new is proposed.'
                : $this->stepLine($brief);
        }

        if ($this->asks($lower, ['term'])) {
            return $this->listed('Terms', $brief['terms']);
        }

        if ($this->asks($lower, ['subject'])) {
            return $this->listed('Subjects', $brief['subjects']);
        }

        if ($this->asks($lower, ['class teacher', 'homeroom'])) {
            return $this->classTeachers($brief);
        }

        if ($this->asks($lower, ['class', 'stream'])) {
            return $this->listed('Classes', $brief['classes']);
        }

        if ($this->asks($lower, ['teacher'])) {
            return $this->listed('Teachers', $brief['teachers']);
        }

        if ($named !== []) {
            return implode(' ', array_slice($named, 0, 3));
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $brief
     * @return list<string>
     */
    private function named(string $lower, array $brief): array
    {
        $lines = [];
        foreach ($brief['teachers'] as $name) {
            if ($this->mentions($lower, $name)) {
                $lines[] = $name.' is a teacher on this checklist.';
            }
        }
        foreach ($brief['subjects'] as $name) {
            if ($this->mentions($lower, $name)) {
                $teachers = $this->teachersOf($name, $brief);
                $lines[] = $teachers === []
                    ? $name.' is a subject on this checklist.'
                    : $name.' is taught by '.$this->join($teachers).'.';
            }
        }
        foreach ($brief['classes'] as $name) {
            if ($this->mentions($lower, $name)) {
                $lines[] = $name.' is a class on this checklist.';
            }
        }
        foreach ($brief['terms'] as $line) {
            $name = explode(' ', $line, 2)[0] ?? '';
            if ($name !== '' && $this->mentions($lower, $name)) {
                $lines[] = $line.'.';
            }
        }
        foreach ($brief['class_teachers'] as $row) {
            if ($this->mentions($lower, $row['name']) || $this->mentions($lower, trim($row['class'].' '.$row['stream']))) {
                $where = trim($row['class'].' '.$row['stream']);
                $lines[] = $row['name'].' is listed as the class teacher for '.$where.'. That is not written.';
            }
        }
        foreach ($brief['learners'] as $row) {
            if ($this->mentions($lower, $row['name'])) {
                $where = trim($row['class'].' '.$row['stream']);
                $lines[] = $row['name'].' is a learner in '.$where.'.';
            } elseif ($row['parent'] !== '' && $this->mentions($lower, $row['parent'])) {
                $lines[] = $row['parent'].' is listed as a parent on this checklist.';
            }
        }

        return array_values(array_unique($lines));
    }

    /**
     * @param  array<string, mixed>  $brief
     * @return list<string>
     */
    private function teachersOf(string $subject, array $brief): array
    {
        $names = [];
        foreach ($brief['assignments'] as $row) {
            if (mb_strtolower($row['subject']) === mb_strtolower($subject)) {
                $names[$row['teacher']] = $row['teacher'];
            }
        }

        return array_values($names);
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function classCount(string $lower, array $brief): ?string
    {
        if (! str_contains($lower, 'how many')) {
            return null;
        }

        foreach ($brief['classes'] as $name) {
            if (! $this->mentions($lower, $name)) {
                continue;
            }
            $count = 0;
            foreach ($brief['learners'] as $row) {
                if (mb_strtolower($row['class']) === mb_strtolower($name)) {
                    $count++;
                }
            }

            return $name.' has '.$count.' learners on this checklist.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $brief
     * @return list<string>
     */
    private function counts(string $lower, array $brief): array
    {
        if (! str_contains($lower, 'how many') && ! str_contains($lower, 'how much') && ! str_starts_with($lower, 'is it')) {
            return [];
        }

        $lines = [];
        $stats = [];
        foreach ($brief['stats'] as $tile) {
            $stats[mb_strtolower($tile['label'])] = $tile;
        }

        $pairs = [
            'class teacher' => null,
            'homeroom' => null,
            'learner' => 'learners',
            'student' => 'learners',
            'pupil' => 'learners',
            'parent' => 'parents',
            'stream' => 'streams',
            'subject' => 'subjects',
            'term' => 'terms',
            'teacher' => 'teachers',
            'class' => 'classes',
        ];

        foreach ($pairs as $needle => $label) {
            if (! str_contains($lower, $needle)) {
                continue;
            }
            if ($needle === 'class teacher' || $needle === 'homeroom') {
                $lines[] = 'Class teachers listed: '.$brief['class_teachers_listed'].'. They are not written.';
                continue;
            }
            if ($needle === 'teacher' && str_contains($lower, 'class teacher')) {
                continue;
            }
            if ($needle === 'class' && (str_contains($lower, 'class teacher') || str_contains($lower, 'how many learners') || str_contains($lower, 'how many students'))) {
                continue;
            }
            $tile = $stats[$label] ?? null;
            if ($tile === null) {
                continue;
            }
            $line = $tile['label'].': '.$tile['value'].'.';
            if ($tile['detail'] !== '') {
                $line .= ' '.$tile['detail'].'.';
            }
            $lines[] = $line;
        }

        return array_values(array_unique($lines));
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function flags(array $brief): string
    {
        if ($brief['flags'] === []) {
            return 'Nothing on this checklist is flagged.';
        }

        $lines = [];
        foreach (array_slice($brief['flags'], 0, 3) as $flag) {
            $where = $flag['tab'];
            if ($flag['row'] !== null) {
                $where .= ' row '.$flag['row'];
            }
            $lines[] = $where.'. '.$flag['message'].' '.$flag['fix'];
        }
        $extra = count($brief['flags']) - count($lines);
        $text = implode(' ', $lines);
        if ($extra > 0) {
            $text .= ' And '.$extra.' more on the card.';
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function classTeachers(array $brief): string
    {
        if ($brief['class_teachers'] === []) {
            return 'Class teachers listed: '.$brief['class_teachers_listed'].'. I do not have their names in this plan.';
        }

        $lines = [];
        foreach (array_slice($brief['class_teachers'], 0, 8) as $row) {
            $lines[] = trim($row['class'].' '.$row['stream']).': '.$row['name'];
        }
        $text = 'Class teachers listed: '.$brief['class_teachers_listed'].'. '.implode(', ', $lines).'.';
        $text .= ' They are not written.';

        return $text;
    }

    /**
     * @param  list<string>  $items
     */
    private function listed(string $label, array $items): string
    {
        if ($items === []) {
            return 'I don\'t know any '.$label.' from this checklist.';
        }

        $shown = array_slice($items, 0, 8);
        $text = $label.': '.$this->join($shown).'.';
        $extra = count($items) - count($shown);
        if ($extra > 0) {
            $text .= ' And '.$extra.' more on the sheet.';
        }

        return $text;
    }

    /**
     * @param  list<string>  $items
     */
    private function join(array $items): string
    {
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }
        $last = array_pop($items);

        return implode(', ', $items).' and '.$last;
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function stepLine(array $brief): string
    {
        $step = $brief['step'];
        if ($step === null) {
            return 'This checklist has no step waiting.';
        }

        return $step['proposal'].' The button says “'.$step['approve_label'].'”. Use that button. I won\'t write anything from a typed message.';
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function handoff(array $brief): string
    {
        if ($brief['step'] === null) {
            return 'There is no step waiting for a button.';
        }

        return 'Use the button that says “'.$brief['step']['approve_label'].'”. I won\'t write anything from a typed message.';
    }

    private function readable(?OnboardingPlan $plan): bool
    {
        return $plan !== null && in_array($plan->status, ['previewed', 'applying', 'applied'], true);
    }

    private function wantsSetup(string $lower): bool
    {
        if (preg_match('/\bset ?up\b.{0,40}\bschool\b/u', $lower) === 1) {
            return true;
        }

        return str_contains($lower, 'checklist')
            && preg_match('/\b(onboard|import|upload|read|here\'s|here is)\b/u', $lower) === 1;
    }

    private function isGreeting(string $lower): bool
    {
        return preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening)( toshi)?[.! ]*$/u', $lower) === 1;
    }

    private function isDecision(string $lower): bool
    {
        return preg_match('/^(yes|yep|yeah|ok|okay|sure|approve|confirm|do it|go ahead|next|create them|skip)[.! ]*$/u', $lower) === 1;
    }

    private function isQuestion(string $lower): bool
    {
        return str_contains($lower, '?')
            || preg_match('/^(how|what|who|which|why|when|where|is|are|do|does|can|tell me)\b/u', $lower) === 1;
    }

    /**
     * @param  list<string>  $needles
     */
    private function asks(string $lower, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function mentions(string $lower, string $name): bool
    {
        $name = mb_strtolower(trim($name));
        if (mb_strlen($name) < 3) {
            return false;
        }

        return str_contains($lower, $name);
    }

    private function normalize(string $text): string
    {
        $text = str_replace(['’', '`'], "'", trim($text));

        return mb_strtolower($text);
    }
}
