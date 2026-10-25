<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fee identity is school + level + class + term + name.
 *
 * The original unique index omitted the term, so "Tuition" in Term 2 could
 * not be stored beside "Tuition" in Term 1 for the same class. This adds a
 * composite unique index that includes academic_term_id, then drops the old
 * index. Existing rows that already collide on the new key stop the migration
 * before any index changes. down() restores the old index only when no fee
 * name is shared across terms.
 */
return new class extends Migration
{
    private const NEW_INDEX = 'fees_categories_school_std_section_term_name_uq';

    /**
     * @var list<string>
     */
    private const NEW_COLUMNS = ['school_id', 'standard_id', 'section_id', 'academic_term_id', 'name'];

    /**
     * @var list<string>
     */
    private const OLD_COLUMNS = ['school_id', 'standard_id', 'section_id', 'name'];

    public function up(): void
    {
        $this->assertNoDuplicateGroups(
            self::NEW_COLUMNS,
            'fees_categories already has duplicate rows on school, level, class, term and name. Resolve them before adding the term to fee uniqueness.'
        );

        if ($this->indexNameForColumns(self::NEW_COLUMNS) === null) {
            Schema::table('fees_categories', function (Blueprint $table) {
                $table->unique(self::NEW_COLUMNS, self::NEW_INDEX);
            });
        }

        $old = $this->indexNameForColumns(self::OLD_COLUMNS);
        if ($old !== null) {
            Schema::table('fees_categories', function (Blueprint $table) use ($old) {
                $table->dropUnique($old);
            });
        }
    }

    public function down(): void
    {
        $this->assertNoDuplicateGroups(
            self::OLD_COLUMNS,
            'fees_categories has the same fee name in more than one term. Those rows have to be merged before name-only uniqueness can be restored.'
        );

        if ($this->indexNameForColumns(self::OLD_COLUMNS) === null) {
            Schema::table('fees_categories', function (Blueprint $table) {
                $table->unique(self::OLD_COLUMNS, 'fees_categories_school_id_standard_id_section_id_name_unique');
            });
        }

        $new = $this->indexNameForColumns(self::NEW_COLUMNS);
        if ($new !== null) {
            Schema::table('fees_categories', function (Blueprint $table) use ($new) {
                $table->dropUnique($new);
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertNoDuplicateGroups(array $columns, string $message): void
    {
        $groups = DB::table('fees_categories')
            ->select($columns)
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($groups->isNotEmpty()) {
            throw new RuntimeException($message.' ('.$groups->count().' duplicate group(s)).');
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function indexNameForColumns(array $columns): ?string
    {
        $want = array_map(static fn (string $column): string => strtolower($column), $columns);

        foreach (Schema::getIndexes('fees_categories') as $index) {
            if (! ($index['unique'] ?? false) || ($index['primary'] ?? false)) {
                continue;
            }

            $have = array_map(
                static fn ($column): string => strtolower((string) $column),
                $index['columns'] ?? []
            );

            if ($have === $want) {
                return (string) $index['name'];
            }
        }

        return null;
    }
};
