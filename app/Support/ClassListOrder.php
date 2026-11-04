<?php

namespace App\Support;

use App\Models\Standard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class and stream lists follow the level (standards.order, reached through
 * standards_link) and then the stream name. No extra column: the level
 * already lives on the standard the link points at.
 */
class ClassListOrder
{
    /**
     * @param  Builder<\App\Models\StandardLink>  $query
     * @return Builder<\App\Models\StandardLink>
     */
    public static function orderLinks(Builder $query): Builder
    {
        return $query
            ->leftJoin('standards as class_levels', function ($join): void {
                $join->on('class_levels.id', '=', 'standards_link.standard_id')
                    ->whereNull('class_levels.deleted_at');
            })
            ->leftJoin('sections as class_sections', 'class_sections.id', '=', 'standards_link.section_id')
            // The join would otherwise overwrite standards_link.id with the
            // standard's id. Callers that filter by the link id need the link's.
            ->select('standards_link.*')
            ->orderBy('class_levels.order')
            ->orderBy('standards_link.stream')
            ->orderBy('class_sections.name')
            ->orderBy('standards_link.id');
    }

    /**
     * @param  Builder<\App\Models\Section>  $query
     * @return Builder<\App\Models\Section>
     */
    public static function orderSections(Builder $query): Builder
    {
        return $query
            ->orderBy(
                Standard::query()
                    ->select('standards.order')
                    ->join('standards_link as section_level_links', 'standards.id', '=', 'section_level_links.standard_id')
                    ->whereColumn('section_level_links.section_id', 'sections.id')
                    ->whereColumn('section_level_links.school_id', 'sections.school_id')
                    ->where(function ($level) {
                        $level->where('section_level_links.status', 1)
                            ->orWhere('section_level_links.status', '1');
                    })
                    ->orderBy('standards.order')
                    ->limit(1)
            )
            ->orderBy('sections.name')
            ->orderBy('sections.id');
    }

    /**
     * @param  Collection<int, \App\Models\StandardLink>  $links
     * @return Collection<int, \App\Models\StandardLink>
     */
    public static function sortLinks(Collection $links): Collection
    {
        return $links->sortBy(function ($link): string {
            $order = $link->standard?->order ?? 999999;
            $stream = mb_strtolower(trim((string) ($link->stream ?? '')));
            $name = mb_strtolower(trim((string) ($link->section?->name ?? '')));

            return sprintf('%08d-%s-%s-%08d', (int) $order, $stream, $name, (int) $link->id);
        })->values();
    }
}
