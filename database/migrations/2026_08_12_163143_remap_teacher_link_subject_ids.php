<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remap class_teacher_links from old subject IDs to current canonical
     * subjects for the same section, matching by canonical name.
     */
    public function up(): void
    {
        $links = DB::table('class_teacher_links')
            ->join('standards_link', 'class_teacher_links.standardLink_id', '=', 'standards_link.id')
            ->join('subjects as old_sub', 'class_teacher_links.subject_id', '=', 'old_sub.id')
            ->where('class_teacher_links.school_id', 104)
            ->select('class_teacher_links.id', 'class_teacher_links.subject_id as old_id',
                     'old_sub.name as old_name', 'standards_link.section_id')
            ->get();

        $updated = 0;
        foreach ($links as $link) {
            $normalized = $this->resolveMapping($link->old_name);
            $newSubject = DB::table('subjects')
                ->where('school_id', 104)
                ->where('section_id', $link->section_id)
                ->where('name', 'like', $normalized)
                ->first();

            if (!$newSubject) continue;

            if ((int) $newSubject->id !== (int) $link->old_id) {
                DB::table('class_teacher_links')->where('id', $link->id)
                    ->update(['subject_id' => $newSubject->id]);
                $updated++;
            }
        }
    }

    private function resolveMapping(string $oldName): string
    {
        $map = [
            'Mathematics'    => 'MTC',
            'English'        => 'ENGLISH',
            'Science'        => 'SCIENCE',
            'Social Studies' => 'SST',
            'Religious Education' => 'RELIGIOUS EDUCATION',
            'Literacy I'     => 'LITERACY I',
            'Literacy II'    => 'LITERACY II',
            'Reading and Response' => 'READING AND RESPONSE',
        ];

        $normalized = str_replace('  ', ' ', trim($oldName));
        return $map[$normalized] ?? $oldName;
    }

    public function down(): void {}
};
