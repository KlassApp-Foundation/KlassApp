<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Services\Toshi\SchoolStreamLookup;
use App\Services\ToshiActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Current-year class streams (standards_link) with no class teacher.
 */
class ListStreamsWithoutClassTeacherTool implements Tool
{
    use AuthorizesToshiAction;

    private const MAX_ROWS = 40;

    public function description(): string
    {
        return 'List classes and streams that have no class teacher assigned. Optional filters: class (section or standard name) and stream. Read only.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'class' => $schema->string()->description('Optional class or section name')->nullable(),
            'stream' => $schema->string()->description('Optional stream name')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return '❌ You are not assigned to a school.';
        }

        $class = $this->text($request->get('class'));
        $stream = $this->text($request->get('stream'));
        $links = SchoolStreamLookup::currentLinks((int) $schoolId, $class, $stream);

        if ($links->isEmpty()) {
            return ($class !== null || $stream !== null)
                ? 'No class matched that filter in your school.'
                : 'No class streams are configured for this school.';
        }

        $links = $links->filter(fn ($link) => $link->class_teacher_id === null)->values();

        if ($links->isEmpty()) {
            return 'Every matching class stream has a class teacher.';
        }

        $total = $links->count();
        $shown = $links->take(self::MAX_ROWS);
        $lines = $shown->map(fn ($link) => '• '.SchoolStreamLookup::label($link))->implode("\n");
        $header = '**Classes without a class teacher ('.$total.'):**';
        if ($total > self::MAX_ROWS) {
            $header .= ' Showing the first '.self::MAX_ROWS.'.';
        }

        return $header."\n".$lines;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
