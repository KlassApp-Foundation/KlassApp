<?php

namespace App\Services\Marks;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Fills column A of a marks workbook with a learner's KLS number.
 *
 * A name matches only after trim, collapsed spaces and case-fold, and only
 * inside that class. No match, more than one match, or a learner with no
 * KLS number leaves the cell empty and is listed. Nothing is guessed.
 */
class KlsFiller
{
    /** @var list<int> */
    private const ALLOWED_GROUPS = [1, 3, 4];

    /**
     * @return array{filled: int, unchanged: int, rows: list<array<string, mixed>>}
     */
    public function fill(User $actor, string $path): array
    {
        $this->assertAllowed($actor);

        $schoolId = (int) $actor->school_id;
        $yearId = $this->currentYearId($schoolId);
        $sections = $this->sectionsByKey($schoolId);
        $spreadsheet = IOFactory::load($path);
        $pending = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (! $this->isClassSheet($sheet)) {
                continue;
            }

            $key = $this->key($sheet->getTitle());
            $matched = $sections[$key] ?? null;

            if ($matched === null) {
                $pending[] = $this->issue($sheet->getTitle(), null, 'unknown_class', '');
                continue;
            }

            if (count($matched) !== 1) {
                $pending[] = $this->issue($sheet->getTitle(), null, 'ambiguous_class', '');
                continue;
            }

            $learners = $this->learners($schoolId, $yearId, (int) $matched[0]->id);
            $lastRow = (int) $sheet->getHighestDataRow();

            for ($row = 3; $row <= $lastRow; $row++) {
                $kls = trim((string) $sheet->getCell('A'.$row)->getCalculatedValue());
                $name = trim((string) $sheet->getCell('B'.$row)->getCalculatedValue());

                if ($kls === '' && $name === '') {
                    continue;
                }

                if ($name === '') {
                    $pending[] = $this->issue($sheet->getTitle(), $row, 'name_missing', '');
                    continue;
                }

                $pending[] = $this->resolve($sheet->getTitle(), $row, $name, $kls, $learners);
            }
        }

        $pending = $this->flagDuplicates($pending);
        $filled = 0;
        $unchanged = 0;

        foreach ($pending as $row) {
            if ($row['outcome'] === 'unchanged') {
                $unchanged++;
            }

            if ($row['outcome'] !== 'filled') {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName((string) $row['sheet']);
            if ($sheet === null) {
                continue;
            }

            $sheet->setCellValue('A'.$row['row'], $row['kls']);
            $filled++;
        }

        if ($filled > 0) {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        }

        return [
            'filled' => $filled,
            'unchanged' => $unchanged,
            'rows' => $pending,
        ];
    }

    private function assertAllowed(User $actor): void
    {
        if ((int) $actor->school_id <= 0 || ! in_array((int) $actor->usergroup_id, self::ALLOWED_GROUPS, true)) {
            abort(403, 'Only an admin or a head teacher can fill KLS numbers for their own school.');
        }
    }

    private function currentYearId(int $schoolId): int
    {
        $ids = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('id')
            ->pluck('id');

        if ($ids->count() === 1) {
            return (int) $ids->first();
        }

        throw ValidationException::withMessages([
            'academic_year' => $ids->isEmpty()
                ? 'This school has no current academic year, so no KLS number was written.'
                : 'This school has more than one current academic year, so no KLS number was written.',
        ]);
    }

    /**
     * @return array<string, list<Section>>
     */
    private function sectionsByKey(int $schoolId): array
    {
        $grouped = [];

        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->where(function ($query): void {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->get();

        foreach ($sections as $section) {
            $grouped[$this->key((string) $section->name)][] = $section;
        }

        return $grouped;
    }

    /**
     * @return list<array{id: int, name: string, kls: ?string, key: string}>
     */
    private function learners(int $schoolId, int $yearId, int $sectionId): array
    {
        $linkIds = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->where('section_id', $sectionId)
            ->where('status', 1)
            ->pluck('id');

        if ($linkIds->isEmpty()) {
            return [];
        }

        $userIds = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->whereIn('standardLink_id', $linkIds)
            ->pluck('user_id');

        $users = User::query()
            ->with('userprofile')
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereIn('id', $userIds)
            ->get();

        $learners = [];

        foreach ($users as $user) {
            $name = $this->displayName($user);
            $kls = trim((string) $user->registration_number);

            $learners[] = [
                'id' => (int) $user->id,
                'name' => $name,
                'kls' => $kls !== '' ? $kls : null,
                'key' => $this->key($name),
            ];
        }

        return $learners;
    }

    /**
     * @param  list<array{id: int, name: string, kls: ?string, key: string}>  $learners
     * @return array<string, mixed>
     */
    private function resolve(string $sheet, int $row, string $name, string $kls, array $learners): array
    {
        $matches = array_values(array_filter(
            $learners,
            fn (array $learner): bool => $learner['key'] === $this->key($name),
        ));

        $base = [
            'sheet' => $sheet,
            'row' => $row,
            'name' => $name,
            'learner_id' => null,
            'kls' => null,
            'candidates' => [],
        ];

        if ($matches === []) {
            return array_merge($base, ['outcome' => 'no_match']);
        }

        if (count($matches) > 1) {
            return array_merge($base, [
                'outcome' => 'ambiguous',
                'candidates' => array_map(fn (array $learner): array => [
                    'name' => $learner['name'],
                    'kls' => $learner['kls'],
                ], $matches),
            ]);
        }

        $learner = $matches[0];
        $base['learner_id'] = $learner['id'];
        $base['kls'] = $learner['kls'];

        if ($kls !== '') {
            if ($learner['kls'] !== null && strcasecmp($kls, $learner['kls']) === 0) {
                return array_merge($base, ['outcome' => 'unchanged']);
            }

            return array_merge($base, ['outcome' => 'already_has_different_kls']);
        }

        if ($learner['kls'] === null) {
            return array_merge($base, ['outcome' => 'learner_has_no_kls']);
        }

        return array_merge($base, ['outcome' => 'filled', 'kls' => $learner['kls']]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function flagDuplicates(array $rows): array
    {
        $counts = [];

        foreach ($rows as $row) {
            $id = $row['learner_id'] ?? null;
            if ($id === null) {
                continue;
            }
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        foreach ($rows as $index => $row) {
            $id = $row['learner_id'] ?? null;
            if ($id !== null && ($counts[$id] ?? 0) > 1 && in_array($row['outcome'], ['filled', 'unchanged'], true)) {
                $rows[$index]['outcome'] = 'duplicate_learner';
                $rows[$index]['kls'] = null;
            }
        }

        return $rows;
    }

    private function isClassSheet(Worksheet $sheet): bool
    {
        $header = strtolower(trim((string) $sheet->getCell('A2')->getCalculatedValue()));

        return str_contains($header, 'kls');
    }

    private function displayName(User $user): string
    {
        $profile = $user->userprofile;
        $fromProfile = trim((string) ($profile?->firstname ?? '').' '.(string) ($profile?->lastname ?? ''));

        return $fromProfile !== '' ? $fromProfile : trim((string) $user->name);
    }

    private function key(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value);
    }

    /**
     * @return array<string, mixed>
     */
    private function issue(string $sheet, ?int $row, string $outcome, string $name): array
    {
        return [
            'sheet' => $sheet,
            'row' => $row,
            'name' => $name,
            'outcome' => $outcome,
            'learner_id' => null,
            'kls' => null,
            'candidates' => [],
        ];
    }
}
