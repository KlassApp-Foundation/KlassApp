<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.id is increments() — INT UNSIGNED — in
     * 2020_02_18_000000_create_users_table.php. foreignId() is BIGINT UNSIGNED,
     * and MySQL rejects that foreign key. This migration is the only foreign
     * key here; schools.id is BIGINT and is not referenced.
     *
     * Production can already have an empty school_invites table from a failed
     * run (columns present, no foreign key, migration not recorded). Drop that
     * empty table and create it again. A table that already has rows is altered
     * in place and is never dropped.
     */
    public function up(): void
    {
        if (Schema::hasTable('school_invites')) {
            if (DB::table('school_invites')->count() > 0) {
                $this->alignCreatedByWithUsersId();

                return;
            }

            Schema::dropIfExists('school_invites');
        }

        $this->createSchoolInvitesTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('school_invites');
    }

    private function createSchoolInvitesTable(): void
    {
        Schema::create('school_invites', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('school_name');
            $table->string('contact_name')->nullable();
            $table->string('country')->nullable();
            $table->unsignedInteger('approx_students')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function alignCreatedByWithUsersId(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $usersId = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
        $createdBy = collect(Schema::getColumns('school_invites'))->firstWhere('name', 'created_by');
        $usersIdType = strtolower((string) ($usersId['type'] ?? ''));

        if ($usersId === null || $createdBy === null || $usersIdType !== 'int unsigned') {
            throw new RuntimeException(
                'school_invites has rows, and created_by cannot be aligned to users.id '
                ."(users.id is '{$usersIdType}'). The table was not dropped."
            );
        }

        $createdByType = strtolower((string) $createdBy['type']);
        $needsChange = $createdByType !== 'int unsigned' || ! ($createdBy['nullable'] ?? false);

        if ($needsChange) {
            if (DB::table('school_invites')->where('created_by', '>', 4294967295)->exists()) {
                throw new RuntimeException(
                    'school_invites.created_by has a value that does not fit users.id. The table was not altered.'
                );
            }

            $this->dropCreatedByForeignKey();

            Schema::table('school_invites', function (Blueprint $table) {
                $table->unsignedInteger('created_by')->nullable()->change();
            });
        }

        if ($this->createdByForeignKey() === null) {
            Schema::table('school_invites', function (Blueprint $table) {
                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    private function dropCreatedByForeignKey(): void
    {
        if ($this->createdByForeignKey() === null) {
            return;
        }

        Schema::table('school_invites', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function createdByForeignKey(): ?array
    {
        return collect(Schema::getForeignKeys('school_invites'))->first(
            fn (array $fk) => in_array('created_by', $fk['columns'] ?? [], true)
                && ($fk['foreign_table'] ?? null) === 'users'
                && in_array('id', $fk['foreign_columns'] ?? [], true)
        );
    }
};
