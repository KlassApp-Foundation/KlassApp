<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Each entry: [teacher_name, class_base, subject_code]
    private const ASSIGNMENTS = [
        ['NIWANDINDA PAUSON',   'P.4', 'SCIENCE'],
        ['NIWANDINDA PAUSON',   'P.5', 'SCIENCE'],
        ['AINEBYOONA EMILY',    'P.2', 'MTC'],
        ['AINEBYOONA EMILY',    'P.2', 'MTC'],
        ['AINEBYOONA EMILY',    'P.2', 'LITERACY II'],
        ['NKURUNUNGI ALEXANDER','P.4', 'SCIENCE'],
        ['NKURUNUNGI ALEXANDER','P.3', 'MTC'],
        ['NIWASASIRA R. SANDRA','P.5', 'ENGLISH'],
        ['NIWASASIRA R. SANDRA','P.5', 'ENGLISH'],
        ['NSHABOHURIRA BOAZ',   'P.5', 'MTC'],
        ['NSHABOHURIRA BOAZ',   'P.6', 'MTC'],
        ['OWARUHANGA BRIAN',    'P.6', 'ENGLISH'],
        ['OWARUHANGA BRIAN',    'P.6', 'ENGLISH'],
        ['NKUNZI MONIC',        'P.4', 'ENGLISH'],
        ['NKUNZI MONIC',        'P.4', 'ENGLISH'],
        ['AYEBARE JOVAN',       'P.5', 'MTC'],
        ['NTANGANZWA FRED',     'P.3', 'LITERACY I'],
        ['ABAHO DELIGHT',       'P.1', 'RELIGIOUS EDUCATION'],
        ['ABAHO DELIGHT',       'P.2', 'LITERACY I'],
        ['KYOSHABIRE MERCY',    'P.1', 'LITERACY II'],
        ['KYOSHABIRE MERCY',    'P.3', 'LITERACY II'],
        ['AKELLO FIONA OCAN',   'P.2', 'RELIGIOUS EDUCATION'],
        ['AKELLO FIONA OCAN',   'P.2', 'RELIGIOUS EDUCATION'],
        ['AKELLO FIONA OCAN',   'P.3', 'RELIGIOUS EDUCATION'],
        ['NUWAGIRA DARIUS',     'P.7', 'ENGLISH'],
        ['NUWAGIRA DARIUS',     'P.7', 'ENGLISH'],
        ['ORISHABA ONESMUS',    'P.4', 'SST'],
        ['ORISHABA ONESMUS',    'P.5', 'SST'],
        ['TUMUKUNDE BERNADES',  'P.1', 'ENGLISH'],
        ['NUWAGIRA JULIET',     'P.1', 'MTC'],
        ['NUWAGIRA JULIET',     'P.2', 'ENGLISH'],
        ['KACHERA ABUNERI',     'P.5', 'SCIENCE'],
        ['KACHERA ABUNERI',     'P.7', 'SCIENCE'],
        ['AGABA SIDNEY',        'P.4', 'MTC'],
        ['AGABA SIDNEY',        'P.4', 'MTC'],
        ['AHUMUZA DOREEN',      'P.1', 'LITERACY I'],
        ['AHUMUZA DOREEN',      'P.2', 'ENGLISH'],
        ['KABAGAMBE ZAVERIO',   'P.6', 'SCIENCE'],
        ['KABAGAMBE ZAVERIO',   'P.6', 'SCIENCE'],
        ['MWESIGYE BRIAN',      'P.4', 'SST'],
        ['MWESIGYE BRIAN',      'P.5', 'SST'],
        ['MWESIGYE BRIAN',      'P.5', 'ENGLISH'],
        ['AKANDWANAHO DASAN',   'P.6', 'SST'],
        ['AKANDWANAHO DASAN',   'P.6', 'SST'],
        ['BYAMUKAMA GERALD',    'P.7', 'SCIENCE'],
        ['AKAMPURIRA JUSTUS',   'P.7', 'SST'],
        ['AKAMPURIRA JUSTUS',   'P.7', 'SST'],
        ['AMATSIKO NICHOLAS',   'P.7', 'MTC'],
        ['AMATSIKO NICHOLAS',   'P.7', 'MTC'],
        ['BYEKWASO JOTHAM',     'P.7', 'ENGLISH'],
    ];

    public function up(): void
    {
        $inserted = 0; $skipped = 0;

        foreach (self::ASSIGNMENTS as $a) {
            $rawName = str_replace(['[',']'], '', trim($a[0]));
            $parts = explode(' ', $rawName);
            $first = $parts[0]; $last = $parts[1] ?? '';

            $teacher = DB::table('users')->where('school_id', 104)
                ->where('usergroup_id', 5)
                ->where('name', 'like', $first . '%')
                ->where('name', 'like', '%' . $last . '%')
                ->first();

            if (!$teacher) { echo "  SKIP teacher: {$rawName}\n"; $skipped++; continue; }

            $section = $a[1];
            $num = (int) substr($section, 2);
            $std = $num <= 3 ? 'primary_lower' : ($num <= 6 ? 'primary' : 'primary_upper');

            $stdLink = DB::table('standards_link')
                ->join('sections', 'standards_link.section_id', '=', 'sections.id')
                ->join('standards', 'standards_link.standard_id', '=', 'standards.id')
                ->where('standards_link.school_id', 104)
                ->where('sections.name', $section)
                ->where('standards.name', $std)
                ->value('standards_link.id');

            if (!$stdLink) { echo "  SKIP stdLink: {$section}/{$std}\n"; $skipped++; continue; }

            $sn = $this->resolveSubject($a[2]);
            $secId = DB::table('standards_link')->where('id', $stdLink)->value('section_id');
            $subject = DB::table('subjects')->where('school_id', 104)->where('section_id', $secId)
                ->where('name', 'like', $sn)->first();

            if (!$subject) { echo "  SKIP subject: {$a[2]} ({$sn}) sec {$secId}\n"; $skipped++; continue; }

            $exists = DB::table('class_teacher_links')
                ->where('school_id', 104)->where('standardLink_id', $stdLink)
                ->where('subject_id', $subject->id)->where('teacher_id', $teacher->id)->exists();
            if ($exists) continue;

            DB::table('class_teacher_links')->insert([
                'school_id' => 104, 'academic_year_id' => 51,
                'standardLink_id' => $stdLink, 'subject_id' => $subject->id,
                'teacher_id' => $teacher->id, 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $inserted++;
        }
        echo "Inserted {$inserted}, skipped {$skipped}\n";
    }

    private function resolveSubject(string $name): string
    {
        return match (strtoupper(trim($name))) {
            'SCIENCE' => 'SCIENCE', 'ENGLISH' => 'ENGLISH', 'MTC' => 'MTC',
            'SST' => 'SST', 'RELIGIOUS EDUCATION' => 'RE',
            'LITERACY I' => 'LITERACY I', 'LITERACY II' => 'LITERACY II',
            default => strtoupper(trim($name)),
        };
    }

    public function down(): void {}
};
