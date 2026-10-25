<?php

namespace App\Services\Toshi;

use App\Models\StandardLink;
use Illuminate\Support\Collection;

/**
 * Current-year class streams (standards_link) for one school.
 * A stream is one section of one class. Lookups never leave $schoolId.
 */
final class SchoolStreamLookup
{
    /**
     * @return Collection<int, StandardLink>
     */
    public static function currentLinks(int $schoolId, ?string $class = null, ?string $stream = null): Collection
    {
        $classNeedle = self::needle($class);
        $streamNeedle = self::needle($stream);

        $query = StandardLink::query()
            ->with(['standard', 'section'])
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->whereIn('academic_year_id', function ($q) use ($schoolId) {
                $q->select('id')
                    ->from('academic_years')
                    ->where('school_id', $schoolId)
                    ->where('status', 1);
            });

        if ($classNeedle !== null) {
            $query->where(function ($q) use ($schoolId, $classNeedle) {
                $q->whereHas('section', function ($section) use ($schoolId, $classNeedle) {
                    $section->where('school_id', $schoolId)
                        ->whereRaw('lower(name) = ?', [$classNeedle]);
                })->orWhereHas('standard', function ($standard) use ($schoolId, $classNeedle) {
                    $standard->where('school_id', $schoolId)
                        ->whereRaw('lower(name) = ?', [$classNeedle]);
                });
            });
        }

        if ($streamNeedle !== null) {
            $query->where(function ($q) use ($schoolId, $streamNeedle) {
                $q->whereRaw('lower(standards_link.stream) = ?', [$streamNeedle])
                    ->orWhereHas('section', function ($section) use ($schoolId, $streamNeedle) {
                        $section->where('school_id', $schoolId)
                            ->whereRaw('lower(name) = ?', [$streamNeedle]);
                    });
            });
        }

        return $query->orderBy('id')->get();
    }

    public static function label(StandardLink $link): string
    {
        $standard = trim((string) ($link->standard?->name ?? ''));
        $section = trim((string) ($link->section?->name ?? ''));
        $stream = trim((string) ($link->stream ?? ''));

        $label = trim($standard.' '.$section);
        if ($stream !== '' && strcasecmp($stream, $section) !== 0) {
            $label = trim($label.' · '.$stream);
        }

        return $label !== '' ? $label : 'stream #'.$link->id;
    }

    private static function needle(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? mb_strtolower($trimmed) : null;
    }
}
