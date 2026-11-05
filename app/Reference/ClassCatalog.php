<?php

namespace App\Reference;

/**
 * Class names, the standard (grading band) each class belongs to,
 * and the core subjects taught in that class.
 *
 * Sections, standards, subjects, and the classes table all read
 * this catalog so a class is not crossed with every standard.
 */
final class ClassCatalog
{
    /**
     * @return list<array{name: string, order: int}>
     */
    public static function standards(): array
    {
        return [
            ['name' => 'nursery', 'order' => 1],
            ['name' => 'primary', 'order' => 2],
            ['name' => 'o-level', 'order' => 3],
            ['name' => 'a-level', 'order' => 4],
        ];
    }

    /**
     * @return list<array{name: string, standard: string, position: int, subjects: list<string>}>
     */
    public static function classes(): array
    {
        return [
            self::row('Baby Class', 'nursery', 1, ['English Rhymes & Stories', 'Numbers & Counting', 'Colour & Shapes', 'Creative Play', 'Religious & Moral Stories']),
            self::row('Middle Class', 'nursery', 2, ['English Language Basics', 'Numeracy', 'Environmental Awareness', 'Art & Craft', 'Physical Play & Health']),
            self::row('Top Class', 'nursery', 3, ['Pre-Literacy (English)', 'Pre-Numeracy', 'Social Habits', 'Creative Arts', 'Religious Education']),
            self::row('Primary One', 'primary', 4, ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education']),
            self::row('Primary Two', 'primary', 5, ['English Language', 'Mathematics', 'Literacy II', 'Numeracy II', 'Religious Education']),
            self::row('Primary Three', 'primary', 6, ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education']),
            self::row('Primary Four', 'primary', 7, ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language', 'Kiswahili']),
            self::row('Primary Five', 'primary', 8, ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language', 'Kiswahili']),
            self::row('Primary Six', 'primary', 9, ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language', 'Kiswahili']),
            self::row('Primary Seven', 'primary', 10, ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language', 'Kiswahili']),
            self::row('Senior One', 'o-level', 11, ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education', 'Kiswahili']),
            self::row('Senior Two', 'o-level', 12, ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education', 'Kiswahili']),
            self::row('Senior Three', 'o-level', 13, ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education', 'Kiswahili']),
            self::row('Senior Four', 'o-level', 14, ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education', 'Kiswahili']),
            self::row('Senior Five', 'a-level', 15, ['General Paper', 'Mathematics', 'Literature in English', 'Biology', 'Chemistry', 'Physics', 'Kiswahili']),
            self::row('Senior Six', 'a-level', 16, ['General Paper', 'Mathematics', 'Literature in English', 'Biology', 'Chemistry', 'Physics', 'Kiswahili']),
        ];
    }

    /**
     * @param  list<string>  $subjects
     * @return array{name: string, standard: string, position: int, subjects: list<string>}
     */
    private static function row(string $name, string $standard, int $position, array $subjects): array
    {
        return [
            'name' => $name,
            'standard' => $standard,
            'position' => $position,
            'subjects' => $subjects,
        ];
    }
}
