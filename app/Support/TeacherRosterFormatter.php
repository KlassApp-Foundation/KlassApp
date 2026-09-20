<?php

namespace App\Support;

use Illuminate\Support\Collection;

class TeacherRosterFormatter
{
    public static function formatStreams(Collection $streams): string
    {
        $formatted = $streams
            ->map(fn ($stream) => is_object($stream) ? ($stream->stream ?? null) : ($stream['stream'] ?? null))
            ->filter(fn ($stream) => is_string($stream) && trim($stream) !== '')
            ->map(fn ($stream) => trim($stream))
            ->unique()
            ->values()
            ->all();

        return $formatted === [] ? '—' : implode(', ', $formatted);
    }
}
