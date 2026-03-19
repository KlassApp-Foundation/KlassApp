<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LessonPlanTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $lessonPlans = [
            [
                'teacher_link_id'     => 1,
                'unit_no'             => 1,
                'unit_name'           => 'Numbers',
                'title'               => 'Counting Backwards (20-0)',
                'duration'            => '00:40:00',
                'description'         => 'Fun interactive lesson teaching students to count backwards from 20 to 0 using hopping and stickers.',
                'objective'           => '<p>Students will orally count backwards from 20-0 and write numbers in reverse order.</p>',
                'materials_required'  => 'Pencils, markers, stickers, numbered construction paper (0-20), index cards.',
                'introduction'        => '<p>Start with sticker counting activity to introduce counting down from 10 to 0.</p>',
                'procedure'           => '<p>Model with number line → Guided hopping game on floor numbers → Independent fill-in-the-blank worksheet.</p>',
                'conclusion'          => '<p>Review counting backwards and discuss what was learned.</p>',
                'assessment'          => 'Worksheet completion (fill in missing numbers backwards).',
                'modification'        => 'Small group support, partner work, verbal/physical cues as needed.',
                'notes'               => null,
                'status'              => 'approved',
                'deleted_at'          => null,
            ],

            [
                'teacher_link_id'     => 1,
                'unit_no'             => 1,
                'unit_name'           => 'Numbers',
                'title'               => 'Big and Small',
                'duration'            => '00:40:00',
                'description'         => 'Hands-on lesson to help students compare and describe object sizes (big vs small).',
                'objective'           => '<p>Students will compare objects and describe their sizes using "big" and "small".</p>',
                'materials_required'  => 'Paper, crayons, large/small paper plates, beads/strings for patterns.',
                'introduction'        => '<p>Teacher wears oversized shoes → class discusses big vs small using their own shoes.</p>',
                'procedure'           => '<p>Brainstorm big/small items → Draw on large/small plates → Create big-small pattern bracelets.</p>',
                'conclusion'          => '<p>Discuss what was learned; fun question: "How does an ant feel in a big world?"</p>',
                'assessment'          => 'Evaluate drawings and pattern bracelets.',
                'modification'        => null,
                'notes'               => null,
                'status'              => 'approved',
                'deleted_at'          => null,
            ],

            [
                'teacher_link_id'     => 1,
                'unit_no'             => 1,
                'unit_name'           => 'Numbers',
                'title'               => 'Even or Odd Nature Walk',
                'duration'            => '01:40:00',
                'description'         => 'Outdoor activity to find even/odd groups in nature (leaves, flowers, etc.).',
                'objective'           => '<p>Students will determine if a group of objects (up to 20) is even or odd.</p>',
                'materials_required'  => 'Garden/playground access, worksheet, pencil.',
                'introduction'        => '<p>Brief rug discussion on even vs odd numbers.</p>',
                'procedure'           => '<p>Partner nature walk → count features (petals/lines) → determine even/odd → complete worksheet.</p>',
                'conclusion'          => '<p>Class discussion on findings and plant types observed.</p>',
                'assessment'          => 'Worksheet completion.',
                'modification'        => 'Small group support, partner work, cues for participation.',
                'notes'               => null,
                'status'              => 'pending',
                'deleted_at'          => null,
            ],

            [
                'teacher_link_id'     => 82,
                'unit_no'             => 1,
                'unit_name'           => 'Adaptation for Survival in Animals',
                'title'               => 'Camouflage and Environment',
                'duration'            => '00:30:00',
                'description'         => 'Activity showing how camouflage helps animals hide from predators using colored butterflies.',
                'objective'           => '<p>Students will identify animals that use camouflage as a defense.</p>',
                'materials_required'  => 'Colored construction paper (red/blue/orange), scissors, markers, large red sheets.',
                'introduction'        => '<p>Students cut out flat butterflies in chosen colors (no markings).</p>',
                'procedure'           => '<p>Spread butterflies on red paper → students act as predators → count survivors → discuss why one color "survived" best (camouflage).</p>',
                'conclusion'          => '<p>Show animal camouflage examples (video/book) and discuss benefits.</p>',
                'assessment'          => 'Draw & write summary about a camouflaged animal.',
                'modification'        => 'Small group help, clear up misconceptions.',
                'notes'               => null,
                'status'              => 'approved',
                'deleted_at'          => null,
            ],
        ];

        foreach ($lessonPlans as $plan) {
            // Unique key: teacher_link_id + unit_no + title (adjust if needed)
            DB::table('lesson_plans')->updateOrInsert(
                [
                    'teacher_link_id' => $plan['teacher_link_id'],
                    'unit_no'         => $plan['unit_no'],
                    'title'           => $plan['title'],
                ],
                [
                    ...$plan,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}