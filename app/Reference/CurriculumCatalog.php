<?php

namespace App\Reference;

/**
 * Curricula a school may choose.
 *
 * The allow-list in OnboardingEngine::saveCurriculum is uneb,
 * cambridge, montessori, and other. That file is owned by the
 * marks stack (PR #1244) and is not edited here. Keep this
 * catalog aligned with that allow-list.
 */
final class CurriculumCatalog
{
    /**
     * @return list<array{code: string, name: string}>
     */
    public static function all(): array
    {
        return [
            ['code' => 'uneb', 'name' => 'UNEB'],
            ['code' => 'cambridge', 'name' => 'Cambridge'],
            ['code' => 'montessori', 'name' => 'Montessori'],
            ['code' => 'other', 'name' => 'Other'],
        ];
    }
}
