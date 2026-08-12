<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROSTER = [
        ['NIWANDINDA PAUSON',   'P.4 WEST P.5 EAST',    'SCIENCE'],
        ['AINEBYOONA EMILY',     'P.2 EAST P.2 WEST P.2','MTC MTC LITERACY II'],
        ['NKURUNUNGI ALEXANDER', 'P.4 EAST P.3',         'SCIENCE MTC'],
        ['NIWASASIRA R. SANDRA', 'P.5 WEST P.5 EAST',    'ENGLISH ENGLISH'],
        ['NSHABOHURIRA BOAZ',    'P.5 WEST P.6 EAST',    'MTC MTC'],
        ['OWARUHANGA BRIAN',     'P.6 EAST P.6 WEST',    'ENGLISH ENGLISH'],
        ['NKUNZI MONIC',         'P.4 WEST P.4 EAST',    'ENGLISH ENGLISH'],
        ['AYEBARE JOVAN',        'P.5 EAST',             'MTC'],
        ['NTANGANZWA FRED',      'P.3',                  'LITERACY I'],
        ['ABAHO DELIGHT',        'P.1 P.2 WEST P.2 EAST','RELIGIOUS EDUCATION LITERACY I'],
        ['KYOSHABIRE MERCY',     'P.1 P.3',              'LITERACY II LITERACY II'],
        ['AKELLO FIONA OCAN',    'P.2 EAST P.2 WEST P.3','RELIGIOUS EDUCATION RELIGIOUS EDUCATION RELIGIOUS EDUCATION'],
        ['NUWAGIRA DARIUS',      'P.7 EAST P.7 WEST',    'ENGLISH ENGLISH'],
        ['ORISHABA ONESMUS',     'P.4 EAST P.5 EAST',    'SST SST'],
        ['TUMUKUNDE BERNADES',   'P.1',                  'ENGLISH'],
        ['NUWAGIRA JULIET',      'P.1 P.2',              'MTC ENGLISH'],
        ['KACHERA ABUNERI',      'P.5 WEST P.7 EAST',    'SCIENCE SCIENCE'],
        ['AGABA SIDNEY',         'P.4 WEST P.4 EAST',    'MTC MTC'],
        ['AHUMUZA DOREEN',       'P.1 P.2',              'LITERACY I ENGLISH'],
        ['KABAGAMBE ZAVERIO',    'P.6 WEST P.6 EAST',    'SCIENCE SCIENCE'],
        ['MWESIGYE BRIAN',       'P.4 WEST P.5 WEST',    'SST SST'],
        ['AKANDWANAHO DASAN',    'P.6 WEST P.6 EAST',    'SST SST'],
        ['BYAMUKAMA GERALD',     'P.7 WEST',             'SCIENCE'],
        ['AKAMPURIRA JUSTUS',    'P.7 EAST P.7 WEST',    'SST'],
        ['AMATSIKO NICHOLAS',    'P.7 EAST P.7 WEST',    'MTC'],
        ['BYEKWASO JOTHAM',      'P.7 EAST',             'ENGLISH'],
        ['MWESIGYE BRIAN',       'P.5 EAST',             'ENGLISH'],
    ];

    public function up(): void
    {
        $inserted = 0; $skipped = 0;

        foreach (self::ROSTER as $row) {
            $rawName = str_replace(['[',']'], '', trim($row[0]));
            $parts = explode(' ', $rawName);
            $first = $parts[0]; $last = $parts[1] ?? '';

            $teacher = DB::table('users')->where('school_id', 104)
                ->where('usergroup_id', 5)
                ->where('name', 'like', $first . '%')
                ->where('name', 'like', '%' . $last . '%')
                ->first();

            if (!$teacher) { echo "  SKIP teacher: {$rawName}\n"; $skipped++; continue; }

            $classNames = preg_split('/\s+(?=P\.\d)/', trim($row[1]));
            $subjects = array_map('trim', explode(' ', trim($row[2])));

            if (count($classNames) !== count($subjects)) {
                echo "  SKIP mismatch: {$rawName} (" . count($classNames) . " vs " . count($subjects) . ")\n";
                $skipped++; continue;
            }

            foreach ($classNames as $i => $cn) {
                $parsed = $this->parseClass($cn);
                if (!$parsed) { echo "  SKIP class parse: {$cn}\n"; $skipped++; continue; }

                $stdLink = DB::table('standards_link')
                    ->join('sections', 'standards_link.section_id', '=', 'sections.id')
                    ->join('standards', 'standards_link.standard_id', '=', 'standards.id')
                    ->where('standards_link.school_id', 104)
                    ->where('sections.name', $parsed['section'])
                    ->where('standards.name', $parsed['standard'])
                    ->value('standards_link.id');

                if (!$stdLink) { echo "  SKIP stdLink: {$cn} ({$parsed['section']}/{$parsed['standard']})\n"; $skipped++; continue; }

                $sn = $this->resolveSubject($subjects[$i]);
                $secId = DB::table('standards_link')->where('id', $stdLink)->value('section_id');
                $subject = DB::table('subjects')->where('school_id', 104)->where('section_id', $secId)
                    ->where('name', 'like', $sn)->first();

                if (!$subject) { echo "  SKIP subject: {$subjects[$i]} ({$sn}) in section {$secId}\n"; $skipped++; continue; }

                $exists = DB::table('class_teacher_links')
                    ->where('school_id', 104)->where('standardLink_id', $stdLink)
                    ->where('subject_id', $subject->id)->where('teacher_id', $teacher->id)->exists();

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
        $name = str_replace('WEST & EAST', '', $name);
        $name = preg_replace('/\s+(EAST|WEST)/i', '', $name);
        $name = str_replace('  ', ' ', trim($name));

        if (preg_match('/^P\.\s*(\d)$/', $name, $m)) {
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
                'LITERACY I' => 'LITERACY I', 'LITERACY II' => 'LITERACY II'];
        return $map[strtoupper(trim($name))] ?? strtoupper(trim($name));
    }

    public function down(): void {}
};
