<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROSTER = [
        ['teacher' => 'NIWANDINDA PAUSON',   'classes' => 'P.4 WEST P.5 EAST',    'subjects' => 'SCIENCE'],
        ['teacher' => 'AINEBYOONA EMILY',     'classes' => 'P.2 EAST P.2 WEST P.2', 'subjects' => 'MTC MTC LITERACY II'],
        ['teacher' => 'NKURUNUNGI ALEXANDER',  'classes' => 'P.4 EAST P.3',          'subjects' => 'SCIENCE MTC'],
        ['teacher' => 'NIWASASIRA R. SANDRA',  'classes' => 'P.5 WEST P.5 EAST',     'subjects' => 'ENGLISH ENGLISH'],
        ['teacher' => 'NSHABOHURIRA BOAZ',     'classes' => 'P.5 WEST P.6 EAST',     'subjects' => 'MTC MTC'],
        ['teacher' => 'OWARUHANGA BRIAN',      'classes' => 'P.6 EAST P.6 WEST',     'subjects' => 'ENGLISH ENGLISH'],
        ['teacher' => 'NKUNZI MONIC',          'classes' => 'P.4 WEST P.4 EAST',     'subjects' => 'ENGLISH ENGLISH'],
        ['teacher' => 'AYEBARE JOVAN',         'classes' => 'P.5 EAST',              'subjects' => 'MTC'],
        ['teacher' => 'NTANGANZWA FRED',       'classes' => 'P.3',                   'subjects' => 'LITERACY I'],
        ['teacher' => 'ABAHO DELIGHT',         'classes' => 'P.1 P.2 WEST P.2 EAST', 'subjects' => 'RELIGIOUS EDUCATION LITERACY I'],
        ['teacher' => 'KYOSHABIRE MERCY',      'classes' => 'P.1 P.3',               'subjects' => 'LITERACY II LITERACY II'],
        ['teacher' => 'AKELLO FIONA OCAN',     'classes' => 'P.2 EAST P.2 WEST P.3', 'subjects' => 'RELIGIOUS EDUCATION RELIGIOUS EDUCATION RELIGIOUS EDUCATION'],
        ['teacher' => 'NUWAGIRA DARIUS',       'classes' => 'P.7 EAST P.7 WEST',     'subjects' => 'ENGLISH ENGLISH'],
        ['teacher' => 'ORISHABA ONESMUS',      'classes' => 'P.4 EAST P.5 EAST',     'subjects' => 'SST SST'],
        ['teacher' => 'TUMUKUNDE BERNADES',    'classes' => 'P.1',                   'subjects' => 'ENGLISH'],
        ['teacher' => 'NUWAGIRA JULIET',       'classes' => 'P.1 P.2',               'subjects' => 'MTC ENGLISH'],
        ['teacher' => 'KACHERA ABUNERI',       'classes' => 'P.5 WEST P.7 EAST',     'subjects' => 'SCIENCE SCIENCE'],
        ['teacher' => 'AGABA SIDNEY',          'classes' => 'P.4 WEST P.4 EAST',     'subjects' => 'MTC MTC'],
        ['teacher' => 'AHUMUZA DOREEN',        'classes' => 'P.1 P.2',               'subjects' => 'LITERACY I ENGLISH'],
        ['teacher' => 'KABAGAMBE ZAVERIO',     'classes' => 'P.6 WEST P.6 EAST',     'subjects' => 'SCIENCE SCIENCE'],
        ['teacher' => 'MWESIGYE BRIAN',        'classes' => 'P.4 WEST P.5 WEST',     'subjects' => 'SST SST'],
        ['teacher' => 'AKANDWANAHO DASAN',     'classes' => 'P.6 WEST P.6 EAST',     'subjects' => 'SST SST'],
        ['teacher' => 'BYAMUKAMA GERALD',      'classes' => 'P.7 WEST',              'subjects' => 'SCIENCE'],
        ['teacher' => 'AKAMPURIRA JUSTUS',     'classes' => 'P.7 EAST P.7 WEST',     'subjects' => 'SST'],
        ['teacher' => 'AMATSIKO NICHOLAS',     'classes' => 'P.7 EAST P.7 WEST',     'subjects' => 'MTC'],
        ['teacher' => 'BYEKWASO JOTHAM',       'classes' => 'P.7 EAST',              'subjects' => 'ENGLISH'],
        ['teacher' => 'MWESIGYE BRIAN',        'classes' => 'P.5 EAST',              'subjects' => 'ENGLISH'],
    ];

    public function up(): void
    {
        $inserted = 0;
        $skipped = 0;

        foreach (self::ROSTER as $row) {
            $teacherName = str_replace(['[', ']'], '', trim($row['teacher']));
            $teacher = DB::table('users')->where('school_id', 104)
                ->where('usergroup_id', 5)
                ->where('name', 'like', $teacherName . '%')
                ->first();

            if (!$teacher) { $skipped++; continue; }

            $classNames = preg_split('/\s+(?=P\.\d)/', trim($row['classes']));
            $subjects = array_map('trim', explode(' ', trim($row['subjects'])));

            if (count($classNames) !== count($subjects)) { $skipped++; continue; }

            foreach ($classNames as $i => $className) {
                $parsed = $this->parseClass($className);
                if (!$parsed) { $skipped++; continue; }

                $stdLink = DB::table('standards_link')
                    ->join('sections', 'standards_link.section_id', '=', 'sections.id')
                    ->join('standards', 'standards_link.standard_id', '=', 'standards.id')
                    ->where('standards_link.school_id', 104)
                    ->where('sections.name', $parsed['section'])
                    ->where('standards.name', $parsed['standard'])
                    ->value('standards_link.id');

                if (!$stdLink) { $skipped++; continue; }

                $subjectName = $this->resolveSubject($subjects[$i]);
                $sectionId = DB::table('standards_link')->where('id', $stdLink)->value('section_id');
                $subject = DB::table('subjects')
                    ->where('school_id', 104)
                    ->where('section_id', $sectionId)
                    ->where('name', 'like', $subjectName)
                    ->first();

                if (!$subject) { $skipped++; continue; }

                $exists = DB::table('class_teacher_links')
                    ->where('school_id', 104)->where('standardLink_id', $stdLink)
                    ->where('subject_id', $subject->id)->where('teacher_id', $teacher->id)
                    ->exists();

                if (!$exists) {
                    DB::table('class_teacher_links')->insert([
                        'school_id' => 104, 'academic_year_id' => 51,
                        'standardLink_id' => $stdLink, 'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id, 'status' => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $inserted++;
                }
            }
        }
        echo "Inserted {$inserted}, skipped {$skipped}\n";
    }

    private function parseClass(string $name): ?array
    {
        $name = str_replace('WEST & EAST', 'WEST', $name);
        $name = str_replace('  ', ' ', trim($name));
        if (preg_match('/^P\.\s*(\d)\s*(EAST|WEST)?$/i', $name, $m)) {
            $n = $m[1]; $s = strtoupper($m[2] ?? '');
            $sec = 'P.' . $n . ($s ? ' ' . $s : '');
            $std = $n <= 3 ? 'primary_lower' : ($n <= 6 ? 'primary' : 'primary_upper');
            return ['section' => $sec, 'standard' => $std];
        }
        if (preg_match('/^P\.(\d)$/', $name, $m)) {
            $n = $m[1];
            $std = $n <= 3 ? 'primary_lower' : ($n <= 6 ? 'primary' : 'primary_upper');
            return ['section' => 'P.' . $n, 'standard' => $std];
        }
        return null;
    }

    private function resolveSubject(string $name): string
    {
        $map = ['LIETRACY II' => 'LITERACY II', 'SCEINCE' => 'SCIENCE',
                'RELIGIOUS EDUCATION' => 'RELIGIOUS EDUCATION', 'MTC' => 'MTC',
                'ENGLISH' => 'ENGLISH', 'SCIENCE' => 'SCIENCE', 'SST' => 'SST',
                'LITERACY I' => 'LITERACY I', 'LITERACY II' => 'LITERACY II', 'RE' => 'RELIGIOUS EDUCATION'];
        return $map[strtoupper(trim($name))] ?? strtoupper(trim($name));
    }

    public function down(): void
    {
        $names = array_unique(array_map(fn($r) => str_replace(['[',']'], '', trim($r['teacher'])), self::ROSTER));
        $ids = DB::table('users')->where('school_id', 104)->whereIn('name', $names)->pluck('id');
        DB::table('class_teacher_links')->where('school_id', 104)->whereIn('teacher_id', $ids)->delete();
    }
};
