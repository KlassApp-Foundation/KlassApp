<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Materialized EOT KPI snapshots.
 *
 * Pattern reference: Academico's `cached_reports` + `academico:build-report`
 * (truncate + rebuild, config anchored), re-implemented against KlassApp's own
 * data model. Before this table, ReportCardsController::computeEotKpis() ran
 * three raw-SQL aggregates on every dashboard load and every report-cards load.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            // null = the school's all-terms aggregate (what the dashboard shows).
            $table->unsignedBigInteger('academic_term_id')->nullable();
            // class | subject | gender
            $table->string('metric', 16);
            $table->string('label');
            $table->decimal('value', 8, 1)->nullable();
            // Preserves the original ordering (perClass/perSubject by name, perGender by gender).
            $table->unsignedInteger('sort_order')->default(0);
            // Rebuild stamp; the read path treats an old value as "stale" and recomputes live.
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_term_id', 'metric', 'sort_order'], 'report_kpi_snapshots_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_kpi_snapshots');
    }
};
