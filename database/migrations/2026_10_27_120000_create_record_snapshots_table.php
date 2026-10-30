<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verifiable records, step 1: one immutable snapshot per learner per term.
 *
 * A release writes these rows; nothing ever updates or deletes one. A
 * correction inserts a new version and marks the old row superseded. Hashing,
 * signing and QR codes are later steps and are not in this table.
 *
 * Foreign keys use ON DELETE RESTRICT. Deleting a student, school, year or
 * term must not cascade into a released snapshot. The parent delete is
 * refused while a snapshot still points at it. Soft deletes (users,
 * academic years) do not remove the row, so they do not touch snapshots.
 *
 * Rollback drops the table, which deletes every released snapshot. That is
 * refused unless RECORD_SNAPSHOTS_ALLOW_DROP=true for this process.
 * The migration reads that env var first, then config('klassapp.records.allow_snapshot_drop').
 * A cached config does not see a newly exported env var until it is rebuilt;
 * exporting RECORD_SNAPSHOTS_ALLOW_DROP=true still allows this one rollback.
 *
 * Safe to run twice. If the table already exists with ON DELETE CASCADE
 * (an earlier draft of this migration), the foreign keys are rewritten to
 * RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('record_snapshots')) {
            Schema::create('record_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
                $table->unsignedInteger('student_id');
                $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
                $table->unsignedInteger('academic_year_id');
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
                $table->unsignedBigInteger('academic_term_id');
                $table->foreign('academic_term_id')->references('id')->on('academic_terms')->restrictOnDelete();

                $table->unsignedInteger('version')->default(1);
                $table->unsignedBigInteger('supersedes_id')->nullable();
                $table->string('reason', 500)->nullable();
                $table->json('payload');
                $table->timestamp('released_at')->nullable();
                $table->unsignedInteger('released_by')->nullable();
                $table->unsignedBigInteger('release_id')->nullable();
                // current | superseded | revoked
                $table->string('state', 16)->default('current');
                $table->timestamps();

                $table->unique(
                    ['school_id', 'student_id', 'academic_year_id', 'academic_term_id', 'version'],
                    'record_snapshot_version_unique',
                );
                $table->index(['school_id', 'academic_term_id', 'state']);
                $table->index(['student_id', 'academic_year_id']);
            });
        }

        $this->keepReleasedRows();
    }

    public function down(): void
    {
        if (! $this->dropReleasedRecordsAllowed()) {
            throw new \RuntimeException(
                'Refusing to drop record_snapshots. That would delete released records. '
                . 'Set RECORD_SNAPSHOTS_ALLOW_DROP=true to allow this rollback.'
            );
        }

        Schema::dropIfExists('record_snapshots');
    }

    /**
     * Rewrite any ON DELETE CASCADE foreign key to RESTRICT. A fresh create
     * already uses RESTRICT, so this is a no-op there.
     */
    private function keepReleasedRows(): void
    {
        $parents = [
            'school_id' => ['schools', 'id'],
            'student_id' => ['users', 'id'],
            'academic_year_id' => ['academic_years', 'id'],
            'academic_term_id' => ['academic_terms', 'id'],
        ];

        foreach ($parents as $column => [$table, $referenced]) {
            $rule = $this->onDeleteRule($column);
            if ($rule !== 'CASCADE') {
                continue;
            }

            Schema::table('record_snapshots', function (Blueprint $blueprint) use ($column, $table, $referenced): void {
                $blueprint->dropForeign([$column]);
                $blueprint->foreign($column)->references($referenced)->on($table)->restrictOnDelete();
            });
        }
    }

    private function onDeleteRule(string $column): ?string
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA foreign_key_list('record_snapshots')") as $fk) {
                if (($fk->from ?? null) === $column) {
                    return strtoupper((string) ($fk->on_delete ?? ''));
                }
            }

            return null;
        }

        $row = DB::selectOne(
            'select rc.DELETE_RULE as rule
             from information_schema.KEY_COLUMN_USAGE kcu
             join information_schema.REFERENTIAL_CONSTRAINTS rc
               on rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
              and rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
              and rc.TABLE_NAME = kcu.TABLE_NAME
             where kcu.TABLE_SCHEMA = database()
               and kcu.TABLE_NAME = ?
               and kcu.COLUMN_NAME = ?
               and kcu.REFERENCED_TABLE_NAME is not null
             limit 1',
            ['record_snapshots', $column]
        );

        return $row ? strtoupper((string) $row->rule) : null;
    }

    /**
     * RECORD_SNAPSHOTS_ALLOW_DROP=true for this process, else the config flag.
     * The env var wins so a rollback can be allowed without rebuilding a
     * cached config.
     */
    private function dropReleasedRecordsAllowed(): bool
    {
        $fromEnv = env('RECORD_SNAPSHOTS_ALLOW_DROP');
        if ($fromEnv !== null && $fromEnv !== '') {
            return filter_var($fromEnv, FILTER_VALIDATE_BOOLEAN);
        }

        return filter_var(config('klassapp.records.allow_snapshot_drop'), FILTER_VALIDATE_BOOLEAN);
    }
};
