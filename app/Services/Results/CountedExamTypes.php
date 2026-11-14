<?php

namespace App\Services\Results;

use Illuminate\Support\Collection;

/**
 * An exam set counts once any learner in the class has a numeric mark or an
 * absence on it. A set that nobody has started is left out.
 */
final class CountedExamTypes
{
    /**
     * @return array<int, true> exam type id => true
     */
    public static function typeIds(Collection $exams, Collection $marks): array
    {
        $examsById = $exams->keyBy('id');
        $active = [];
        foreach ($marks as $mark) {
            $exam = $examsById->get($mark->exam_id);
            if ($exam === null) {
                continue;
            }
            $typeId = (int) ($exam->exam_type_id ?? 0);
            if ($typeId < 1) {
                continue;
            }
            $absent = (bool) ($mark->absent ?? false) || strcasecmp(trim((string) ($mark->grade ?? '')), 'AB') === 0;
            if ($absent || $mark->marks !== null) {
                $active[$typeId] = true;
            }
        }

        return $active;
    }
}
