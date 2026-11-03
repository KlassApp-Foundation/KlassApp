<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite keeps cities.state_id (NOT NULL) because the earlier drop
 * skipped SQLite. MySQL dropped the column. Put the column back on
 * MySQL so every city can point at a state on both engines.
 *
 * Also stores the curriculum list and the grading bands from
 * config/grading_uganda.php. Those catalogs are not school rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cities') && ! Schema::hasColumn('cities', 'state_id')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->unsignedInteger('state_id')->nullable();
            });
        }

        if ($this->shouldAddStateForeignKey()) {
            Schema::table('cities', function (Blueprint $table) {
                $table->foreign('state_id', 'cities_state_id_foreign')
                    ->references('id')
                    ->on('states');
            });
        }

        if (! Schema::hasTable('reference_curricula')) {
            Schema::create('reference_curricula', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 32)->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reference_grading_bands')) {
            Schema::create('reference_grading_bands', function (Blueprint $table) {
                $table->increments('id');
                $table->string('level', 32);
                $table->unsignedSmallInteger('position');
                $table->string('grade', 64);
                $table->integer('points')->nullable();
                $table->unsignedTinyInteger('min_score')->nullable();
                $table->unsignedTinyInteger('max_score')->nullable();
                $table->string('remark')->nullable();
                $table->timestamps();
                $table->unique(['level', 'position']);
                $table->unique(['level', 'grade']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_grading_bands');
        Schema::dropIfExists('reference_curricula');

        // The original cities table owns state_id on SQLite. Only drop
        // the foreign key this migration added on MySQL.
        if (Schema::hasTable('cities') && $this->foreignKeyExists('cities', 'cities_state_id_foreign')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->dropForeign('cities_state_id_foreign');
            });
        }
    }

    private function shouldAddStateForeignKey(): bool
    {
        if (! Schema::hasTable('cities') || ! Schema::hasColumn('cities', 'state_id')) {
            return false;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return false;
        }

        return ! $this->foreignKeyExists('cities', 'cities_state_id_foreign');
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $row = DB::selectOne(
            'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ? and CONSTRAINT_NAME = ? and CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};
