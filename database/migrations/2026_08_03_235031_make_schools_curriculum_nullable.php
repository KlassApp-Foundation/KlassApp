<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow curriculum to be unset at SaaS signup so Toshi asks for it.
     * Fresh installs get nullable curriculum from the create migration;
     * this alters existing MySQL/MariaDB deployments.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'curriculum')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            // SQLite test schema is created nullable via the create migration.
            // Avoid brittle drop/rebuild against schools_curriculum_index.
            return;
        }

        DB::statement('ALTER TABLE schools MODIFY curriculum VARCHAR(50) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('schools', 'curriculum')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            return;
        }

        DB::statement("UPDATE schools SET curriculum = 'uneb' WHERE curriculum IS NULL");
        DB::statement("ALTER TABLE schools MODIFY curriculum VARCHAR(50) NOT NULL DEFAULT 'uneb'");
    }
};
