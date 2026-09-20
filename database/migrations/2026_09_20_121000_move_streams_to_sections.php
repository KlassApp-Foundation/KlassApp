<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sections', 'stream')) {
            Schema::table('sections', function (Blueprint $table): void {
                $table->string('stream')->nullable()->after('name');
            });
        }

        DB::table('standards_link')
            ->whereNotNull('stream')
            ->where('stream', '!=', '')
            ->orderBy('id')
            ->get()
            ->each(function (object $link): void {
                $section = DB::table('sections')->where('id', $link->section_id)->first();
                if (! $section) {
                    return;
                }

                $stream = trim((string) $link->stream);
                $sectionName = trim((string) $section->name);
                $targetName = str_ends_with(strtolower($sectionName), ' ' . strtolower($stream))
                    ? $sectionName
                    : $sectionName . ' ' . $stream;

                $target = DB::table('sections')
                    ->where('school_id', $section->school_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($targetName)])
                    ->first();

                if (! $target) {
                    $targetId = DB::table('sections')->insertGetId([
                        'school_id' => $section->school_id,
                        'name' => $targetName,
                        'stream' => $stream,
                        'status' => $section->status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $target = DB::table('sections')->where('id', $targetId)->first();
                } elseif ((string) $target->stream !== $stream) {
                    DB::table('sections')->where('id', $target->id)->update(['stream' => $stream]);
                }

                DB::table('standards_link')->where('id', $link->id)->update([
                    'section_id' => $target->id,
                ]);
            });

        $indexNames = collect(Schema::getIndexes('standards_link'))->pluck('name')->all();
        if (in_array('standards_link_unique_class_stream', $indexNames, true)) {
            Schema::table('standards_link', function (Blueprint $table): void {
                $table->dropUnique('standards_link_unique_class_stream');
            });
        }

        $indexNames = collect(Schema::getIndexes('standards_link'))->pluck('name')->all();
        if (! in_array('standards_link_unique_class_year_standard', $indexNames, true)) {
            Schema::table('standards_link', function (Blueprint $table): void {
                $table->unique(['school_id', 'section_id', 'academic_year_id', 'standard_id'], 'standards_link_unique_class_year_standard');
            });
        }
    }

    public function down(): void
    {
        Schema::table('standards_link', function (Blueprint $table): void {
            $table->dropUnique('standards_link_unique_class_year_standard');
        });

        Schema::table('sections', function (Blueprint $table): void {
            $table->dropColumn('stream');
        });
    }
};
